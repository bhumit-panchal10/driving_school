<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StateMaster;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
//use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\PushNotificationController;
use App\Models\Customer;
use App\Models\DriverTags;
use App\Models\AreaMaster;
use App\Models\CMSMaster;
use App\Models\CardPayment;
use GuzzleHttp\Client;
use App\Models\Driver;
use Carbon\Carbon;
use App\Models\Packageorder;
use App\Models\DrivingSchool;
use App\Models\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;



class SchoolOwnerApiController extends Controller

// class DriverApiController extends PushNotificationController
{

    public function statelist(Request $request)
    {
        try {

            $listOfStates = StateMaster::select(
                "stateId",
                "stateName"
            )->orderBy('stateName', 'asc')->where(['iStatus' => 1, 'isDelete' => 0])->get();

            return response()->json([
                'success' => true,
                'message' => "successfully fetched StateList...",
                'data' => $listOfStates,
            ], 200);
        } catch (\Throwable $th) {

            // If there's an error, rollback any database transactions and return an error response.

            DB::rollBack();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    private function uploadFileUsingRoot(Request $request, $file)
    {

        $root = $_SERVER['DOCUMENT_ROOT'];
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $Certificatefolder = "";
        if ($request->Certificate) {
            $Certificatefolder = '/driving_school/upload/documents/';
        }
        $logofolder = "";
        if ($request->Logo) {
            $logofolder = '/driving_school/upload/logo/';
        }


        $certificatedestinationPath = $root . $Certificatefolder;
        $logodestinationPath = $root . $logofolder;

        // Ensure the directory exists
        if (!file_exists($certificatedestinationPath)) {
            mkdir($certificatedestinationPath, 0755, true);
        }

        if (!file_exists($logodestinationPath)) {
            mkdir($logodestinationPath, 0755, true);
        }

        // Move the file to the destination path
        $file->move($certificatedestinationPath, $filename);
        $file->move($logodestinationPath, $filename);

        // Return the relative path for storage in the database
        return ltrim($folder, '/') . $filename;
    }

    public function schoolowner_new_registration(Request $request)
    {
        try {

            // Validate the request inputs
            $request->validate([
                "name" => 'required',
                "mobile_number" => 'required|digits:10|unique:drivingschool,mobile_number',
                "BrandName" => 'required',
                "Logo" => 'required',
                "Ac_no" => 'required',
                "Ac_holdername" => 'required',
                // "email" => 'required|email|unique:drivingschool,email',
                "email" => 'required|email',
                "ifsc_code" => 'required',
                "Certificate" => 'required|file|mimes:pdf,jpg,png,doc,docx',
                "bank_name" => 'required',
                "City" => 'required',
                "state_id" => 'required',
                "Address" => 'required',
                "GST_No" => 'required',
                "password" => 'required',
                "confirm_password" => 'required|same:password'
            ]);

            // Check if a school owner already exists
            $existingschoolowner = DrivingSchool::where(function ($query) use ($request) {
                $query->where('email', $request->email)
                    ->orWhere('mobile_number', $request->mobile_number);
            })->first();

            if ($existingschoolowner && $existingschoolowner->isOtpVerified == 0) {
                $existingschoolowner->delete();
            } elseif ($existingschoolowner) {
                return response()->json([
                    'success' => false,
                    'message' => 'A School Owner with this email or mobile number already exists.',
                ], 409);
            }
            // Generate OTP and expiry time
            $otp = mt_rand(100000, 999999);
            $expiry_date = now()->addMinutes(3);

            // Prepare data for insertion
            $DrivingSchooldata = [
                "name" => $request->name,
                "mobile_number" => $request->mobile_number,
                "BrandName" => $request->BrandName,
                "Logo" => $request->Logo,
                "Ac_no" => $request->Ac_no,
                "Ac_holdername" => $request->Ac_holdername,
                "email" => $request->email,
                "password" => Hash::make($request->confirm_password),
                "latitude" => $request->latitude,
                "longitude" => $request->longitude,
                'ifsc_code' => $request->ifsc_code,
                'Certificate' => '', // Placeholder for file path
                'bank_name' => $request->bank_name,
                'City' => $request->City,
                'state_id' => $request->state_id,
                'Address' => $request->Address,
                'GST_No' => $request->GST_No,
                'otp' => $otp,
                'is_login' => 1,
                'expiry_time' => $expiry_date,
                'strIP' => $request->ip(),
            ];

            // Create the DrivingSchool record
            $DrivingSchool = DrivingSchool::create($DrivingSchooldata);

            // Handle Certificate file upload

            if ($request->hasFile('Certificate')) {
                $root = $_SERVER['DOCUMENT_ROOT'];
                $image = $request->file('Certificate');

                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();

                $destinationPath = $root . '/driving_school/upload/document/';

                // Ensure the directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Move the uploaded image to the destination path
                $image->move($destinationPath, $img);
                $DrivingSchool->Certificate = $img;
            }
            // dd($DrivingSchooldata['Certificate']);


            // Handle Logo file upload
            if ($request->hasFile('Logo')) {
                $root = $_SERVER['DOCUMENT_ROOT'];
                $image = $request->file('Logo');
                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                $destinationPath = $root . '/driving_school/upload/logo/';

                // Ensure the directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Move the uploaded image to the destination path
                $image->move($destinationPath, $img);
                $DrivingSchool->Logo = $img;
            }
            // dd($DrivingSchool);
            $DrivingSchool->save();

            // Prepare OTP email data
            $data = [
                'otp' => $otp,
                "name" => $request->name
            ];

            $sendEmailDetails = DB::table('sendemaildetails')->where(['id' => 9])->first();

            $msg = [
                'FromMail' => $sendEmailDetails->strFromMail,
                'Title' => $sendEmailDetails->strTitle,
                'ToEmail' => $request->email,
                'Subject' => $sendEmailDetails->strSubject
            ];

            // Send OTP email
            Mail::send('emails.OTPmail', ['data' => $data], function ($message) use ($msg) {
                $message->from($msg['FromMail'], $msg['Title']);
                $message->to($msg['ToEmail'])->subject($msg['Subject']);
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. OTP sent to the email.',
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', Arr::flatten($e->errors())), // Collect and format validation errors
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required',
            ]);

            $password = mt_rand(100000, 999999);


            // Retrieve the vendor based on the email and OTP
            $schoolowner = DrivingSchool::where([
                'email' => $request->email,
                'otp' => $request->otp,
            ])->first();
            // dd($vendor);

            if (!$schoolowner) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP is invalid. Please enter a valid OTP.',
                ], 400);
            }

            // Check if the OTP has expired
            $expiryTime = Carbon::parse($schoolowner->expiry_time);
            if (now()->greaterThan($expiryTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired.',
                ], 400);
            }

            // Mark the OTP as verified and update the last login time
            $schoolowner->update([
                'isOtpVerified' => 1,
                'last_login' => now(),
            ]);
            $schoolownerDetails = $schoolowner->only(['SchoolId', 'name', 'phone_number', 'email', 'BrandName', 'Ac_no', 'Ac_holdername', 'ifsc_code', 'bank_name', 'City', 'Address', 'isOtpVerified', 'isAdminApproved']);

            return response()->json([
                'success' => true,
                'message' => 'OTP is valid.',
                'schoolowner_details' => $schoolownerDetails,

            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'mobile_number' => 'required',
                'password' => 'required',
            ]);

            $credentials = [
                'mobile_number' => $request->mobile_number,
                'password' => $request->password,
            ];

            // Check if the user is a school owner
            $schoolowner = DrivingSchool::where('mobile_number', $request->mobile_number)->first();
            if (!$schoolowner) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            if ($schoolowner) {
                // Attempt School Owner Login
                if (Auth::guard('schoolownerapi')->attempt($credentials)) {
                    $authenticatedschoolowner = Auth::guard('schoolownerapi')->user();

                    $data = [
                        "SchoolId" => $authenticatedschoolowner->SchoolId,
                        "name" => $authenticatedschoolowner->name,
                        "mobile_number" => $authenticatedschoolowner->mobile_number,
                        "email" => $authenticatedschoolowner->email,
                        "is_login" => $authenticatedschoolowner->is_login,
                        "BrandName" => $authenticatedschoolowner->BrandName,
                        "Ac_no" => $authenticatedschoolowner->Ac_no,
                        "Certificate" => $authenticatedschoolowner->Certificate
                            ? asset('upload/document/' . $authenticatedschoolowner->Certificate)
                            : null,
                        "Logo" => $authenticatedschoolowner->Logo
                            ? asset('upload/logo/' . $authenticatedschoolowner->Logo)
                            : null,
                        "Ac_holdername" => $authenticatedschoolowner->Ac_holdername,
                        "ifsc_code" => $authenticatedschoolowner->ifsc_code,
                        "isAdminApproved" => $authenticatedschoolowner->isAdminApproved,
                        "bank_name" => $authenticatedschoolowner->bank_name,
                        "City" => $authenticatedschoolowner->City,
                        "state_id" => $authenticatedschoolowner->state_id,
                        "Address" => $authenticatedschoolowner->Address,
                        "GST_No" => $authenticatedschoolowner->GST_No,
                        "License_No" => $authenticatedschoolowner->License_No,
                        "licencesexpiry_date" => $authenticatedschoolowner->licencesexpiry_date,
                        "gender" => $authenticatedschoolowner->gender,
                        "experience" => $authenticatedschoolowner->experience,
                        "women_allow_ride" => $authenticatedschoolowner->women_allow_ride,
                        "otp" => $authenticatedschoolowner->otp,
                        "is_changepasswordfirsttime" => $authenticatedschoolowner->is_changepasswordfirsttime,
                        "isOtpVerified" => $authenticatedschoolowner->isOtpVerified,
                        "expiry_time" => $authenticatedschoolowner->expiry_time,
                        "iStatus" => $authenticatedschoolowner->iStatus,
                        "strIP" => $authenticatedschoolowner->strIP,
                        "created_at" => $authenticatedschoolowner->created_at,
                        "updated_at" => $authenticatedschoolowner->updated_at,
                    ];

                    $token = JWTAuth::fromUser($authenticatedschoolowner);

                    return response()->json([
                        'success' => true,
                        'message' => 'School owner login successful.',
                        'schoolownerdetail' => $data,
                        'authorisation' => [
                            'token' => $token,
                            'type' => 'bearer',
                        ],
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid school owner credentials.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function change_password(Request $request)
    {
        try {

            if (Auth::guard('schoolownerapi')->check()) {

                $request->validate(
                    [
                        "SchoolId" => 'required',
                        "old_password" => 'required',
                        "new_password" => 'required',
                        "confirm_new_password" => 'required|same:new_password'
                    ],
                    [
                        'SchoolId.required' => 'School ID is required.',
                        'old_password.required' => 'Old Password is required.',
                        'new_password.required' => 'New Password is required.',
                        'new_password.same' => 'New password and confirmation password must match.'
                    ]
                );

                $schoolowner =  DrivingSchool::where(['iStatus' => 1, 'isDelete' => 0, 'SchoolId' => $request->SchoolId])->first();
                if (!$schoolowner) {
                    return response()->json([
                        'success' => false,
                        'message' => "school owner not found."
                    ]);
                }

                if (Hash::check($request->old_password, $schoolowner->password)) {

                    $newpassword = $request->new_password;
                    $confirmpassword = $request->confirm_new_password;

                    if ($newpassword == $confirmpassword) {

                        $schoolowner->update([
                            'password' => Hash::make($confirmpassword),
                            // 'is_changepasswordfirsttime' => 1
                        ]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Password updated successfully...',
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'password and confirm password does not match',
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current Password does not match',
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'school owner is not Authorised.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function forgot_password(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            // Find the vendor by email
            $schoolowner = DrivingSchool::where(['iStatus' => 1, 'isDelete' => 0])
                ->where('email', $request->email)
                ->first();

            if (!$schoolowner) {
                return response()->json([
                    'success' => false,
                    'message' => "schoolowner not found."
                ], 404);
            }

            $otp = rand(1000, 9999);
            $expiry_date = now()->addMinutes(3);

            // Update the OTP and expiry in the database
            $schoolowner->update([
                'otp' => $otp,
                'expiry_time' => $expiry_date,
            ]);

            // Send the email
            $sendEmailDetails = DB::table('sendemaildetails')->where(['id' => 9])->first();
            $msg = [
                'FromMail' => $sendEmailDetails->strFromMail,
                'Title' => $sendEmailDetails->strTitle,
                'ToEmail' => $request->email,
                'Subject' => $sendEmailDetails->strSubject,
            ];

            $data = array(
                'otp' => $otp,
                "name" => $schoolowner->name
            );


            Mail::send('emails.forgotPassword', ['data' => $data], function ($message) use ($msg) {
                $message->from($msg['FromMail'], $msg['Title']);
                $message->to($msg['ToEmail'])->subject($msg['Subject']);
            });

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function forgot_password_verifyOTP(Request $request)
    {
        try {
            $request->validate([

                'otp' => 'required'
            ]);

            $password = mt_rand(100000, 999999);


            // Retrieve the vendor based on the email and OTP
            $schoolowner = DrivingSchool::where([
                'otp' => $request->otp
            ])->first();
            // dd($vendor);

            if (!$schoolowner) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP is invalid. Please enter a valid OTP.',
                ], 400);
            }

            // Check if the OTP has expired
            $expiryTime = Carbon::parse($schoolowner->expiry_time);
            if (now()->greaterThan($expiryTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired.',
                ], 400);
            }

            // Mark the OTP as verified and update the last login time
            $schoolowner->update([
                'password' =>  Hash::make($password),
                'last_login' => now(),
            ]);

            $data = array(
                'password' => $password,
                "email" => $schoolowner->email,
                "name" =>  $schoolowner->name


            );

            $sendEmailDetails = DB::table('sendemaildetails')->where(['id' => 9])->first();
            $msg = array(
                'FromMail' => $sendEmailDetails->strFromMail,
                'Title' => $sendEmailDetails->strTitle,
                'ToEmail' => $schoolowner->email,
                'Subject' => $sendEmailDetails->strSubject
            );

            Mail::send('emails.forgotpasswordmail', ['data' => $data], function ($message) use ($msg) {
                $message->from($msg['FromMail'], $msg['Title']);
                $message->to($msg['ToEmail'])->subject($msg['Subject']);
            });
            // $vendorDetails = $vendor->only(['vendor_id','vendorname', 'isOtpVerified', 'login_id', 'vendormobile', 'email', 'businessname', 'businessaddress','vendorsocialpage','businesscategory','businessubcategory','is_changepasswordfirsttime']);
            return response()->json([
                'success' => true,
                'message' => 'OTP is valid.',
                // 'vendor_details' => $vendorDetails,

            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {

        try {
            // Validate the vendorid passed in the request
            $request->validate([
                'vendorid' => 'required|integer'
            ]);
            // Optionally, fetch the vendor by vendorid (if you need to check or log something)
            $vendor = Vendor::find($request->vendorid);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found.'
                ], 404);
            }
            Auth::logout();
            session()->flush();
            // Optional: If you want to send the vendor details in the response
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out.',
                'vendorid' => $vendor->vendor_id,  // Including the vendorid in the response
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token. Unable to logout.',
            ], 401);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function set_password(Request $request)
    {
        try {

            if (Auth::guard('vendorapi')->check()) {

                $request->validate(
                    [
                        "vendorid" => 'required',
                        "password" => 'required',
                        "confirm_password" => 'required|same:password'
                    ],
                    [
                        'vendorid.required' => 'Vendor ID is required.',
                        'password.required' => 'Password is required.',
                        'confirm_password.required' => 'Confirmation password is required.',
                        'confirm_password.same' => 'Password and confirmation password must match.'
                    ]
                );

                $Vendor =  Vendor::where(['iStatus' => 1, 'isDelete' => 0, 'vendor_id' => $request->vendorid])->first();
                if (!$Vendor) {
                    return response()->json([
                        'success' => false,
                        'message' => "Vendor not found."
                    ]);
                }

                Vendor::where(['iStatus' => 1, 'isDelete' => 0, 'vendor_id' => $request->vendorid])->update([
                    "password" => Hash::make($request->confirm_password)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Password set successfully...',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor is not Authorised.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    public function profiledetails(Request $request)
    {
        try {


            $request->validate([
                'SchoolId' => 'required|integer',
            ]);

            $schoolowner = DrivingSchool::with('state')->where('SchoolId', $request->SchoolId)
                ->where('iStatus', 1)
                ->where('isDelete', 0)
                ->first();
            //dd($schoolowner);

            if (!$schoolowner) {
                return response()->json([
                    'success' => false,
                    'message' => 'school owner not found.',
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => [
                    "SchoolId" => $schoolowner->SchoolId,
                    "name" => $schoolowner->name,
                    "BrandName" => $schoolowner->BrandName,
                    "email" => $schoolowner->email,
                    "phone_number" => $schoolowner->mobile_number,
                    "Logo" => asset('upload/logo/' . $schoolowner->Logo),
                    "Certificate" => asset('upload/document/' . $schoolowner->Certificate),
                    "Ac_no" => $schoolowner->Ac_no,
                    "Ac_holdername" => $schoolowner->Ac_holdername,
                    "ifsc_code" => $schoolowner->ifsc_code,
                    "bank_name" => $schoolowner->bank_name,
                    "state_id" => $schoolowner->state_id,
                    "state_name" => $schoolowner->state->stateName ?? '',
                    "City" => $schoolowner->City,
                    "schoolowner" => $schoolowner->state_id,
                    "Address" => $schoolowner->Address,
                    "GST_No" => $schoolowner->GST_No,
                    "latitude" => $schoolowner->latitude,
                    "longitude" => $schoolowner->longitude,
                    "otp" => $schoolowner->otp,
                    "isOtpVerified" => $schoolowner->isOtpVerified,
                    "expiry_time" => $schoolowner->expiry_time,
                    "iStatus" => $schoolowner->iStatus,
                    "strIP" => $schoolowner->strIP,
                    "created_at" => $schoolowner->created_at,
                    "updated_at" => $schoolowner->updated_at,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching profile details.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function profileUpdate(Request $request)
    {
        try {

            if (Auth::guard('schoolownerapi')->check()) {

                $schoolowner = Auth::guard('schoolownerapi')->user();

                $request->validate([
                    'SchoolId' => 'required'
                ]);

                $schoolowner = DrivingSchool::where(['iStatus' => 1, 'isDelete' => 0, 'SchoolId' => $request->SchoolId])->first();



                if (!$schoolowner) {
                    return response()->json([
                        'success' => false,
                        'message' => "school owner not found."
                    ]);
                }

                // Start building the Vendor data
                $SchoolownerData = [];

                // Add fields conditionally
                if ($request->has('name')) {
                    $SchoolownerData["name"] = $request->name;
                }
                if ($request->has('BrandName')) {
                    $SchoolownerData["BrandName"] = $request->BrandName;
                }

                if ($request->has('City')) {
                    $SchoolownerData["City"] = $request->City;
                }
                if ($request->has('state_id')) {
                    $SchoolownerData["state_id"] = $request->state_id;
                }
                if ($request->has('Address')) {
                    $SchoolownerData["Address"] = $request->Address;
                }
                if ($request->hasFile('Logo')) {
                    $root = $_SERVER['DOCUMENT_ROOT'];
                    $image = $request->file('Logo');
                    $imgName = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                    $destinationPath = $root . '/driving_school/upload/logo/';

                    // Ensure the directory exists
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Move the uploaded image to the destination path
                    $image->move($destinationPath, $imgName);

                    // Delete the old image if it exists
                    if ($schoolowner->Logo && file_exists($destinationPath . $schoolowner->Logo)) {
                        unlink($destinationPath . $schoolowner->Logo);
                    }

                    // Update the image name
                    $SchoolownerData['Logo'] = $imgName;
                }

                if ($request->hasFile('Certificate')) {
                    $root = $_SERVER['DOCUMENT_ROOT'];
                    $image = $request->file('Certificate');
                    $imgName = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                    $destinationPath = $root . '/driving_school/upload/document/';

                    // Ensure the directory exists
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Move the uploaded image to the destination path
                    $image->move($destinationPath, $imgName);

                    // Delete the old image if it exists
                    if ($schoolowner->Certificate && file_exists($destinationPath . $schoolowner->Certificate)) {
                        unlink($destinationPath . $schoolowner->Certificate);
                    }

                    // Update the image name
                    $SchoolownerData['Certificate'] = $imgName;
                }

                // Always update 'updated_at'
                $SchoolownerData['updated_at'] = now();

                DB::beginTransaction();

                try {

                    DrivingSchool::where(['SchoolId' => $request->SchoolId])->update($SchoolownerData);

                    $schoolownerdetail = DrivingSchool::where(['SchoolId' => $request->SchoolId])->first();


                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => "School owner Profile updated successfully.",
                        'data' => [
                            "SchoolId" => $schoolownerdetail->SchoolId,
                            "name" => $schoolownerdetail->name,
                            "BrandName" => $schoolownerdetail->BrandName,
                            "Logo" => asset('upload/logo/' . $schoolownerdetail->Logo),
                            "Certificate" => asset('upload/documents/' . $schoolownerdetail->Certificate),
                            "City" => $schoolownerdetail->City,
                            "state_id" => $schoolownerdetail->state_id,
                            "Address" => $schoolownerdetail->Address,
                            "updated_at" => $schoolowner->updated_at,
                        ],
                    ], 200);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    throw $th;
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'schoolowner is not authorized.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function customerschedule(Request $request)
    {
        //dd('hy');
        $request->validate([
            'SchoolId' => 'required'
        ]);


        try {
            $myorders = Packageorder::with('carname', 'package', 'schedulemaster')
                ->where('SchoolId', $request->SchoolId)
                ->whereIn('is_schedule', [0])
                ->whereIn('isPayment', [0,1])
                ->get();
            //dd($myorders);
            $myorder = [];
            foreach ($myorders as $myor) {
                $totalAmount = $myor->iNetAmount ?? 0;

                $paidAmount = CardPayment::where('oid', $myor->package_order_id)
                    ->where('status', 'Success')
                    ->sum('amount');
                $dueAmount = max(0, $totalAmount - $paidAmount);

                $myorder[] = [
                    "customer_name" => $myor->customer_name,
                    "start_date" => $myor->start_date,
                    "customer_id" => $myor->customer_id,
                    "package_order_id" => $myor->package_order_id,
                    "customer_phone" => $myor->customer_phone,
                    "pickup_drop" => $myor->pickup_drop ?: '',
                    "Address" => trim(($myor->landmark ?? '') . ' ' . ($myor->Address ?? '')),
                    "car_id" => $myor->car_id,
                    "package_id" => $myor->package_id,
                    "Total Amount" => $totalAmount,
                    "Paid Amount" => $paidAmount,
                    "Due Amount" => $dueAmount,
                    "fromtime" => $myor->schedulemaster[0]['fromtime'] ?? '',
                    "Totime" => $myor->schedulemaster[0]['Totime'] ?? '',
                    "Schedule_master_id" => $myor->schedulemaster[0]['Schedule_master_id'] ?? '',
                    "carname" => optional($myor->carname)->CarBrandName ?? '',
                    "packagename" => optional($myor->package->first())->name ?? '',
                    "session" => optional($myor->package->first())->session ?? '',


                ];
            }
            // Check if there are no orders
            if (empty($myorders)) {
                return response()->json([
                    'message' => 'Order not found.',
                    'success' => false,
                ], 404);
            }
            return response()->json([
                'message' => 'Customer Schedule Fetch Sucessfully',
                'success' => true,
                'data'    => $myorder,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function Driver(Request $request)
    {
        //dd('hy');
        $request->validate([
            'SchoolId' => 'required',
        ]);
        try {
            $assignedDrivers = Schedule::whereNotNull('driver_id')
                ->pluck('driver_id')
                ->toArray();
            $unassignedDrivers = DrivingSchool::where('is_login', 2)
                ->where('driver_schoolid', $request->SchoolId)
                ->whereNotIn('SchoolId', $assignedDrivers) // Filter out assigned drivers
                ->select('SchoolId', 'name')
                ->get();


            return response()->json([
                'message' => 'Available unassigned drivers fetched successfully!',
                'success' => true,
                'data' => $unassignedDrivers,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function DriverAssign(Request $request)
    {

        $request->validate([
            'SchoolId' => 'required',
            'car_id' => 'required',
            'fromtime' => 'required|date_format:H:i:s',
            'Totime' => 'required|date_format:H:i:s',
            'session' => 'required',
            'customer_id' => 'required',
            'driver_id' => 'required',
            'package_order_id' => 'required',
        ]);
       

        $startdate = Carbon::today(); // Get today's date
        $enddate = (clone $startdate)->addDays($request->session - 1)->toDateString(); // Clone before modification
      
        try {
           // DB::beginTransaction();
            $updated = Schedule::where('SchoolId', $request->SchoolId)
                ->where('fromtime', $request->fromtime)
                ->where('Totime', $request->Totime)
                ->where('car_id', $request->car_id)
                ->whereBetween('Schedule_date', [$startdate->toDateString(), $enddate]) // Correct whereBetween usage
                ->update(['driver_id' => $request->driver_id, 'package_order_id' => $request->package_order_id, 'Customer_id' => $request->customer_id]);
          

            // Check if there are no orders
            if ($updated == 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'No matching schedule found to assign driver.',
                    'success' => false,
                ], 404);
            }
            Packageorder::where('package_order_id', $request->package_order_id)
                ->update(['is_schedule' => 1]);

            //DB::commit();
            return response()->json([
                'message' => 'Driver assigned successfully!',
                'success' => true,

            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function ongoingcustomer(Request $request)
    {
        //dd('hy');
        $request->validate([
            'SchoolId' => 'required'
        ]);
        try {
            $ongoingorders = Packageorder::with('carname', 'package', 'schedulemaster')
                ->where('SchoolId', $request->SchoolId)
                ->whereIn('is_schedule', [1])
                ->get();
            $ongoingorder = [];
            foreach ($ongoingorders as $myor) {
                $package = $myor->package->first();
                $totalsession = $package->session;
                $consumesession = Schedule::where('package_order_id', $myor->package_order_id)
                    ->where('Customer_id', $myor->customer_id)
                    ->sum('attendance');
                $available = $totalsession - $consumesession;
                $ongoingorder[] = [
                    "customer_name" => $myor->customer_name,
                    "start_date" => $myor->start_date,
                    "package_order_id" => $myor->package_order_id,
                    "customer_phone" => $myor->customer_phone,
                    "pickup_drop" => $myor->pickup_drop ?: '',
                    "Address" => trim(($myor->landmark ?? '') . ' ' . ($myor->Address ?? '')),
                    "car_id" => $myor->car_id,
                    "package_id" => $myor->package_id,
                    "fromtime" => $myor->schedulemaster[0]['fromtime'] ?? '',
                    "Totime" => $myor->schedulemaster[0]['Totime'] ?? '',
                    "Schedule_master_id" => $myor->schedulemaster[0]['Schedule_master_id'] ?? '',
                    "carname" => optional($myor->carname)->CarBrandName ?? '',
                    "packagename" => $package->name ?? '',
                    "Total session" => $totalsession,
                    "Consume session" => $consumesession,
                    "Remain session" => $available,


                ];
            }
            // dd($ongoingorder);
            // Check if there are no orders
            if (empty($ongoingorders)) {
                return response()->json([
                    'message' => 'Order not found.',
                    'success' => false,
                ], 404);
            }
            return response()->json([
                'message' => 'Ongoing Customer Fetch Sucessfully',
                'success' => true,
                'data'    => $ongoingorder,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function schedule_pending_session(Request $request)
    {
        try {
            $request->validate([
                'car_id' => 'required|integer',
                'SchoolId' => 'required|integer',
                'fromtime' => 'required',
                'Totime' => 'required',
                'driver_id' => 'required|integer',
                'session' => 'required|integer',
                'startdate' => 'required|date',
                'customer_id' => 'required|integer',
            ]);

            $startDate = Carbon::parse($request->startdate)->format('Y-m-d');
            $endDate = Carbon::parse($request->startdate)->addDays($request->session - 1)->format('Y-m-d');

            // Check if the driver is available for the given sessions
            $availableSessions = Schedule::where('driver_id', $request->driver_id)
                ->where('SchoolId', $request->SchoolId)
                ->whereBetween('Schedule_date', [$startDate, $endDate])
                ->whereBetween('fromtime', [$request->fromtime, $request->Totime])
                ->whereBetween('Totime', [$request->fromtime, $request->Totime])
                ->where('Customer_id', 0)
                ->count();

            // If no available sessions, return an error
            if ($availableSessions < $request->session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not available for the requested sessions.',
                ], 400);
            }

            // Assign the driver to the sessions
            $updatedRows = Schedule::where('driver_id', $request->driver_id)
                ->where('SchoolId', $request->SchoolId)
                ->whereBetween('Schedule_date', [$startDate, $endDate])
                ->whereBetween('fromtime', [$request->fromtime, $request->Totime])
                ->whereBetween('Totime', [$request->fromtime, $request->Totime])
                ->where('Customer_id', 0)
                ->take($request->session)
                ->update(['Customer_id' => $request->customer_id]);

            if ($updatedRows > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Driver assigned successfully.',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No available sessions to assign.',
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function MarkAscomplete(Request $request)
    {

        $request->validate([
            'package_order_id' => 'required'


        ]);
        try {
            DB::beginTransaction();
            $updated = Packageorder::where('package_order_id', $request->package_order_id)
                ->update(['is_schedule' => 2]);
            if ($updated == 0) {
                throw new \Exception('No record found to update.');
            }
            DB::commit();
            return response()->json([
                'message' => 'order complete successfully!',
                'success' => true,

            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    public function cancelorder(Request $request)
    {

        $request->validate([
            'package_order_id' => 'required'


        ]);
        try {
            DB::beginTransaction();
            $updated = Packageorder::where('package_order_id', $request->package_order_id)
                ->update(['is_schedule' => -1]);
            if ($updated == 0) {
                throw new \Exception('No record found.');
            }
            DB::commit();
            return response()->json([
                'message' => 'Order Cancel successfully!',
                'success' => true,

            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function completeorder(Request $request)
    {
        //dd('hy');
        $request->validate([
            'SchoolId' => 'required'
        ]);
        try {
            $completeorders = Packageorder::with('carname', 'package', 'schedulemaster')
                ->where('SchoolId', $request->SchoolId)
                ->whereIn('is_schedule', [2])
                ->get();
            $completeorder = [];
            foreach ($completeorders as $myor) {

                $completeorder[] = [
                    "customer_name" => $myor->customer_name,
                    "package_order_id" => $myor->package_order_id,
                    "start_date" => $myor->start_date,
                    "customer_phone" => $myor->customer_phone,
                    "pickup_drop" => $myor->pickup_drop ?: '',
                    "Address" => trim(($myor->landmark ?? '') . ' ' . ($myor->Address ?? '')),
                    "car_id" => $myor->car_id,
                    "package_id" => $myor->package_id,
                    "fromtime" => $myor->schedulemaster[0]['fromtime'] ?? '',
                    "Totime" => $myor->schedulemaster[0]['Totime'] ?? '',
                    "Schedule_master_id" => $myor->schedulemaster[0]['Schedule_master_id'] ?? '',
                    "carname" => optional($myor->carname)->CarBrandName ?? '',
                    "packagename" => optional($myor->package->first())->name ?? '',
                    "session" => optional($myor->package->first())->session ?? '',


                ];
            }
            // dd($ongoingorder);
            // Check if there are no orders
            if (empty($completeorders)) {
                return response()->json([
                    'message' => 'Order not found.',
                    'success' => false,
                ], 404);
            }
            return response()->json([
                'message' => 'complete order Fetch Sucessfully',
                'success' => true,
                'data'    => $completeorder,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
