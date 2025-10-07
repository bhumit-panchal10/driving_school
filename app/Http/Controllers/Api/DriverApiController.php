<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StateMaster;
use App\Models\Car;
use App\Models\CarType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Models\Driver;
use App\Models\FuelType;
use GuzzleHttp\Client;
use App\Models\DrivingSchool;
use App\Models\Schedule;
use App\Models\Packageorder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


class DriverApiController extends Controller

{


    public function Todayride(Request $request)
    {
        try {
            $request->validate([
                'driver_id' => 'required|integer'
            ]);

            $today = now()->toDateString();
            $driverrides = Schedule::with(['package_Order.packagename'])
                ->where('Schedule_date', $today)
                ->where('driver_id', $request->driver_id)
                ->get();
            //dd($driverrides);
            $data = [];
            foreach ($driverrides as $driverride) {
                $packageOrder = $driverride->package_Order;
                $package = $packageOrder?->packagename;

                // Get total sessions from the package
                $totalSessions = $package?->session ?? 0;

                // Calculate consumed sessions (sum of attendance in Schedule table)
                $consumedSessions = Schedule::where('package_order_id', $packageOrder?->package_order_id)
                    ->where('Customer_id', $packageOrder?->customer_id)
                    ->sum('attendance');

                // Remaining sessions
                $remainingSessions = max($totalSessions - $consumedSessions, 0);

                $data[] = [
                    "Schedule_id" => $driverride->Schedule_id,
                    "package_order_id" => $packageOrder?->package_order_id ?? '',
                    "customer_id" => $packageOrder?->customer_id ?? '',
                    "customer_name" => $packageOrder?->customer_name ?? '',
                    "customer_phone" => $packageOrder?->customer_phone ?? '',
                    "Address" => trim(($packageOrder?->landmark ?? '') . ' ' . ($packageOrder?->Address ?? '')),
                    "fromtime" => $driverride->fromtime ?? '',
                    "Totime" => $driverride->Totime ?? '',
                    "Pickup Drop" => $packageOrder?->pickup_drop ?? '',
                    "Total session" => $totalSessions,
                    "Consume session" => $consumedSessions,
                    "Remain session" => $remainingSessions
                ];
            }

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Today ride not available.',
                    'data' => $data,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching today\'s rides.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function driver_startrideverifyOTP(Request $request)
    {
        try {
            $request->validate([
                'package_order_id' => 'required',
                'customer_id' => 'required',
                'Schedule_id' => 'required',
                'start_ride_otp' => 'required'
            ]);

            $customerorder = Packageorder::where('package_order_id', $request->package_order_id)
                ->where('start_ride_otp', $request->start_ride_otp)
                ->first();


            // Check if order exists and has an OTP
            if (!$customerorder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or OTP does not exist.',
                ], 400);
            }

            // Validate OTP
            if ($customerorder->start_ride_otp != $request->start_ride_otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP is invalid. Please enter a valid OTP.',
                ], 400);
            }

            $customerorder->update(['start_ride' => 1]);
            Schedule::where('package_order_id', $request->package_order_id)
                ->where('Schedule_id', $request->Schedule_id)
                ->where('Customer_id', $request->customer_id)
                ->update(['attendance' => 1]);
            return response()->json([
                'success' => true,
                'message' => 'OTP is valid.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function set_password(Request $request)
    {
        try {


            if (Auth::guard('driverapi')->check()) {

                $request->validate(
                    [
                        "Driver_id" => 'required',
                        "password" => 'required',
                        "confirm_password" => 'required|same:password'
                    ],
                    [
                        'Driver_id.required' => 'Driver ID is required.',
                        'password.required' => 'Password is required.',
                        'confirm_password.required' => 'Confirmation password is required.',
                        'confirm_password.same' => 'Password and confirmation password must match.'
                    ]
                );

                $Driver =  Driver::where(['iStatus' => 1, 'isDelete' => 0, 'Driver_id' => $request->Driver_id])->first();
                // dd($Driver);
                if (!$Driver) {
                    return response()->json([
                        'success' => false,
                        'message' => "Driver not found."
                    ]);
                }

                Driver::where(['iStatus' => 1, 'isDelete' => 0, 'Driver_id' => $request->Driver_id])->update([
                    "password" => Hash::make($request->confirm_password)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Password set successfully...',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver is not Authorised.',
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
            $Driver = Driver::where(['iStatus' => 1, 'isDelete' => 0])
                ->where('Driver_email', $request->email)
                ->first();

            if (!$Driver) {
                return response()->json([
                    'success' => false,
                    'message' => "Driver not found."
                ], 404);
            }

            $otp = rand(1000, 9999);
            $expiry_date = now()->addMinutes(3);

            // Update the OTP and expiry in the database
            $Driver->update([
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
                "name" => $Driver->Driver_name
            );



            Mail::send('emails.driverforgotPassword', ['data' => $data], function ($message) use ($msg) {
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
            $Driver = Driver::where([
                'otp' => $request->otp
            ])->first();
            // dd($vendor);

            if (!$Driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP is invalid. Please enter a valid OTP.',
                ], 400);
            }

            // Check if the OTP has expired
            $expiryTime = Carbon::parse($Driver->expiry_time);
            if (now()->greaterThan($expiryTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired.',
                ], 400);
            }

            // Mark the OTP as verified and update the last login time
            $Driver->update([
                'password' =>  Hash::make($password),
                'last_login' => now(),
            ]);

            $data = array(
                'password' => $password,
                "email" => $Driver->Driver_email,
                "name" =>  $Driver->Driver_name


            );

            $sendEmailDetails = DB::table('sendemaildetails')->where(['id' => 9])->first();
            $msg = array(
                'FromMail' => $sendEmailDetails->strFromMail,
                'Title' => $sendEmailDetails->strTitle,
                'ToEmail' => $Driver->Driver_email,
                'Subject' => $sendEmailDetails->strSubject
            );

            Mail::send('emails.forgotpasswordOTPmail', ['data' => $data], function ($message) use ($msg) {
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

    //driver registration
    public function DriverAdd(Request $request)
    {
        try {


            $request->validate([
                "Driver_name" => 'required',
                "Driver_email" => 'required',
                "Driver_address" => 'required',
                "License_No" => 'required',
                "mobile_number" => 'required|digits:10|unique:drivingschool,mobile_number',
                "licencesexpiry_date" => 'required',
                "SchoolId" => 'required',
                "password" => 'required',
                "confirm_password" => 'required',
                "gender" => 'required',
                "experience" => 'required',
                "women_allow_ride" => 'required',

            ]);

            $Driver = DrivingSchool::create([
                'name' => $request->Driver_name,
                'email' => $request->Driver_email,
                'Address' => $request->Driver_address,
                'License_No' => $request->License_No,
                'mobile_number' => $request->mobile_number,
                'licencesexpiry_date' => $request->licencesexpiry_date,
                'driver_schoolid' => $request->SchoolId,
                'password' => Hash::make($request->password),
                'confirm_password' => Hash::make($request->confirm_password),
                'gender' => $request->gender,
                'experience' => $request->experience,
                'is_login' => 2,
                'women_allow_ride' => $request->women_allow_ride,
                'strIP' => $request->ip(),
                'created_at' => now()

            ]);
            return response()->json([
                'success' => true,
                'data' => $Driver,
                'message' => 'Driver Added Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getschool(Request $request)
    {
        try {

            $schools = DrivingSchool::pluck('name');
            return response()->json([
                'success' => true,
                'data' => $schools,
                'message' => 'schoolname Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function DriverList(Request $request)
    {
        $request->validate(
            [
                'SchoolId' => 'required'

            ]
        );
        try {

            $Driver = DrivingSchool::where('driver_schoolid', $request->SchoolId)->get();
            return response()->json([
                'success' => true,
                'data' => $Driver,
                'message' => 'Driver Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Drivershow(Request $request)
    {
        $request->validate(
            [
                'SchoolId' => 'required'

            ]
        );
        try {

            $Driver = DrivingSchool::where('SchoolId', $request->SchoolId)->first();

            return response()->json([
                'success' => true,
                'data' => $Driver,
                'message' => 'Driver Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function DriverUpdate(Request $request)
    {


        $request->validate(
            [

                "Driver_name" => 'required',
                "Driver_email" => 'required',
                "Driver_address" => 'required',
                "SchoolId" => 'required',
                "gender" => 'required',
                "experience" => 'required',
                "women_allow_ride" => 'required',
                "licencesexpiry_date" => 'required',

            ]
        );
        try {

            $Driver = DrivingSchool::where('SchoolId', $request->SchoolId)->first();


            if ($Driver) {
                $Driver->update([
                    'name' => $request->Driver_name,
                    'email' => $request->Driver_email,
                    'Address' => $request->Driver_address,
                    'SchoolId' => $request->SchoolId,
                    'gender' => $request->gender,
                    'licencesexpiry_date' => $request->licencesexpiry_date,
                    'experience' => $request->experience,
                    'women_allow_ride' => $request->women_allow_ride,
                    'strIP' => $request->ip(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Driver updated successfully.',
                    'data' => $Driver,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function DriverDelete(Request $request)
    {
        try {
            $request->validate([

                "SchoolId" => 'required'
            ]);
            $Driver = DrivingSchool::find($request->SchoolId);
            if ($Driver) {
                $Driver->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Driver Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Profile(Request $request)
    {
        try {

            $request->validate([
                'driver_id' => 'required|integer',
            ]);

            $driver = DrivingSchool::where('SchoolId', $request->driver_id)
                ->where('is_login', 2)
                ->first();
            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not found.',
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => [
                    "name" => $driver->name,
                    "email" => $driver->email,
                    "phone_number" => $driver->mobile_number,
                    "Address" => $driver->Address,


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
}
