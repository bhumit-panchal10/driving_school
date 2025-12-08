<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StateMaster;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PushNotificationController;
use App\Models\Customer;
use App\Models\CompanyProfit;
use App\Models\AreaMaster;
use App\Models\CardPayment;
use App\Models\CMSMaster;
use App\Models\Driver;
use GuzzleHttp\Client;
use App\Models\DrivingSchool;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Schedule;
use App\Models\ScheduleMaster;
use App\Models\Packageorder;
use Razorpay\Api\Api;


class CustomerController extends Controller

// class DriverApiController extends PushNotificationController
{

    public function search(Request $request)
    {
        try {
            $request->validate([
                'lat' => 'required|numeric',
                'long' => 'required|numeric',
                'schoolname' => 'nullable|string',
                'starttime' => 'nullable',
                'endtime' => 'nullable',
            ]);
            $starttime = $request->starttime;
            $endtime = $request->endtime;
            $strUserLat = $request->lat;
            $strUserLong = $request->long;
            $maxRadius = 50000; // 50 km
            $batchSize = 25; // Google API limit

            $schools = DrivingSchool::join('Schedule', 'Schedule.schoolId', '=', 'drivingschool.SchoolId')
                ->select('drivingschool.*', 'Schedule.*')
                ->whereNotNull('drivingschool.latitude')
                ->whereNotNull('drivingschool.longitude')
                ->when(!empty($request->schoolname), function ($query) use ($request) {
                    $query->where('drivingschool.name', 'LIKE', '%' . $request->schoolname . '%');
                })
                ->when(!empty($starttime) || !empty($endtime), function ($query) use ($starttime, $endtime) {
                    if (!empty($starttime) && !empty($endtime)) {
                        // Fix for crossing midnight
                        if ($endtime == '00:00:00') {
                            $endtime = '23:59:59';
                        }

                        $query->where(function ($q) use (
                            $starttime,
                            $endtime
                        ) {
                            $q->where(function ($subQ) use ($starttime, $endtime) {
                                // Case 1: Schedule completely overlaps the search time
                                $subQ->where('Schedule.fromtime', '<=', $starttime)
                                    ->where('Schedule.Totime', '>=', $endtime);
                            })
                                ->orWhereBetween('Schedule.fromtime', [$starttime, $endtime]) // Case 2: Start time is in range
                                ->orWhereBetween('Schedule.Totime', [$starttime, $endtime]); // Case 3: End time is in range
                        });
                    }
                    if (!empty($starttime)) {
                        $query->where('Schedule.fromtime', '>=', $starttime);
                    }
                    if (!empty($endtime)) {
                        if ($endtime == '00:00:00') {
                            $endtime = '23:59:59';
                        }
                        $query->where('Schedule.Totime', '<=', $endtime);
                    }
                })
                ->get();
            // dd($schools);

            if ($schools->isEmpty()) {
                return response()->json(['message' => 'No schools found before distance calculation'], 404);
            }

            $origins = "$strUserLat,$strUserLong";

            // Remove duplicate locations
            $uniqueSchools = $schools->unique(function ($school) {
                return "{$school->latitude},{$school->longitude}";
            });


            // Split destinations into batches of 25
            $batches = array_chunk($uniqueSchools->toArray(), $batchSize);
            $distances = [];

            foreach ($batches as $batch) {
                $destinations = collect($batch)->map(function ($school) {
                    return "{$school['latitude']},{$school['longitude']}";
                })->implode('|');

                if (empty($destinations)) continue;

                $client = new Client();
                $url = "https://maps.googleapis.com/maps/api/distancematrix/json";

                $response = $client->get($url, [
                    'query' => [
                        'origins' => $origins,
                        'destinations' => $destinations,
                        'key' => 'AIzaSyDJDm56GxJQyzh8fa7dmsdEA1CVPeZBno8',
                        'departure_time' => 'now',
                    ]
                ]);

                $distanceMatrix = json_decode($response->getBody()->getContents(), true);

                if ($distanceMatrix['status'] !== 'OK') {
                    //Log::error('Google Distance API Error:', $distanceMatrix);
                    return response()->json([
                        'message' => 'Error calculating distances',
                        'error' => $distanceMatrix['status']
                    ], 500);
                }

                $elements = $distanceMatrix['rows'][0]['elements'];
                foreach ($batch as $index => $school) {
                    $distances[] = [
                        'school' => $school,
                        'distance' => $elements[$index]['distance']['value'] ?? null,
                        'duration' => $elements[$index]['duration']['text'] ?? null,
                        'Logo' => asset('upload/logo/' . $school['Logo']),
                    ];
                }
            }

            // Filter by 50km radius
            $filteredSchools = collect($distances)->filter(function ($item) use ($maxRadius) {
                return $item['distance'] !== null && $item['distance'] <= $maxRadius;
            })->sortBy('distance')->values();

            return response()->json([
                'success' => true,
                'schools' => $filteredSchools,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Driving School Search Error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function customer_new_registration(Request $request)
    {
        try {


            $request->validate([
                "name" => 'required',
                "mobile_number" => 'required',
                "email" => 'required|email',
                "latitude" => 'required',
                "longitude" => 'required',
                "password" => 'required',

            ]);

            $existingcustomer = Customer::where(function ($query) use ($request) {
                $query->where('Customer_email', $request->email)
                    ->orWhere('Customer_phone', $request->mobile_number);
            })->first();


            if ($existingcustomer && $existingcustomer->isOtpVerified == 0) {
                $existingcustomer->delete();
            } elseif ($existingcustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'A Customer with this email or mobile number already exists.',
                ], 409);
            }

            $otp = mt_rand(100000, 999999);
            $expiry_date = now()->addMinutes(5);

            $Customerdata = [
                "Customer_name" => $request->name,
                "Customer_phone" => $request->mobile_number,
                "Customer_email" => $request->email,
                "latitude" => $request->latitude,
                "longitude" => $request->longitude,
                "password" => Hash::make($request->password),
                'otp' => $otp,
                'expiry_time' => $expiry_date,
                'strIP' => $request->ip(),
            ];

            $Customer = Customer::create($Customerdata);
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
                'message' => implode(', ', Arr::flatten($e->errors())),
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
                'otp' => 'required'
            ]);
            $customer = Customer::where([
                'otp' => $request->otp
            ])->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP is invalid. Please enter a valid OTP.',
                ], 400);
            }

            // Check if the OTP has expired
            $expiryTime = Carbon::parse($customer->expiry_time);
            if (now()->greaterThan($expiryTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired.',
                ], 400);
            }

            // Mark the OTP as verified and update the last login time
            $customer->update([
                'isOtpVerified' => 1,
                'last_login' => now(),
            ]);
            $customerDetails = $customer->only(['Customer_id', 'Customer_name', 'Customer_email', 'Customer_phone', 'isOtpVerified']);

            return response()->json([
                'success' => true,
                'message' => 'OTP is valid.',
                'customer_details' => $customerDetails,

            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function customerlogin(Request $request)
    {
        try {
            $request->validate([
                'Customer_phone' => 'required|digits_between:10,15',
                'password' => 'required',
            ]);
            $credentials = [
                'Customer_phone' => $request->Customer_phone,
                'password' => $request->password,
            ];

            $customer = Customer::where('Customer_phone', $request->Customer_phone)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found.',
                ], 404);
            }

            if ($customer) {
                // Attempt Driver Login
                if (Auth::guard('customerapi')->attempt($credentials)) {
                    $authenticatedcustomer = Auth::guard('customerapi')->user();

                    $data = [
                        "Customer_id" => $authenticatedcustomer->Customer_id,
                        "Customer_name" => $authenticatedcustomer->Customer_name,
                        "Customer_email" => $authenticatedcustomer->Customer_email,
                        "Customer_phone" => $authenticatedcustomer->Customer_phone,

                    ];

                    $token = JWTAuth::fromUser($authenticatedcustomer);

                    return response()->json([
                        'success' => true,
                        'message' => 'Customer login successful.',
                        'Customerdetail' => $data,
                        'authorisation' => [
                            'token' => $token,
                            'type' => 'bearer',
                        ],
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Customer credentials.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function profiledetails(Request $request)
    {
        try {

            $request->validate([
                'Customer_id' => 'required|integer'
            ]);

            $Customer = Customer::where('Customer_id', $request->Customer_id)
                ->where('iStatus', 1)
                ->where('isDelete', 0)
                ->first();

            if (!$Customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    "Customer_name" => $Customer->Customer_name,
                    "Customer_Address" => $Customer->Customer_address,
                    "Customer_phone" => $Customer->Customer_phone,
                    "Customerimg" => $Customer->Customer_image
                        ? asset('upload/Customer/' . $Customer->Customer_image)
                        : '',
                    "Customer_email" => $Customer->Customer_email,
                    "iStatus" => $Customer->iStatus,
                    "strIP" => $Customer->strIP,
                    "created_at" => $Customer->created_at,
                    "updated_at" => $Customer->updated_at,
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

            if (Auth::guard('customerapi')->check()) {

                $customer = Auth::guard('customerapi')->user();

                $request->validate([
                    'Customer_id' => 'required'
                ]);

                $customer = Customer::where(['iStatus' => 1, 'isDelete' => 0, 'Customer_id' => $request->Customer_id])->first();

                if (!$customer) {
                    return response()->json([
                        'success' => false,
                        'message' => "Customer not found."
                    ]);
                }

                // Start building the Vendor data
                $CustomerData = [];

                // Add fields conditionally
                if ($request->has('Customer_name')) {
                    $CustomerData["Customer_name"] = $request->Customer_name;
                }
                if ($request->has('Customer_Address')) {
                    $CustomerData["Customer_address"] = $request->Customer_Address;
                }
                if ($request->has('Customer_phone')) {
                    $CustomerData["Customer_phone"] = $request->Customer_phone;
                }
                if ($request->has('email')) {
                    $CustomerData["Customer_email"] = $request->email;
                }


                if ($request->hasFile('Customerimg')) {
                    $root = $_SERVER['DOCUMENT_ROOT'];
                    $image = $request->file('Customerimg');
                    $imgName = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                    $destinationPath = $root . '/driving_school/upload/Customer/';

                    // Ensure the directory exists
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Move the uploaded image to the destination path
                    $image->move($destinationPath, $imgName);

                    // Delete the old image if it exists
                    if ($customer->Customer_image && file_exists($destinationPath . $customer->Customer_image)) {
                        unlink($destinationPath . $customer->Customer_image);
                    }

                    // Update the image name
                    $CustomerData['Customer_image'] = $imgName;
                }

                // Always update 'updated_at'
                $CustomerData['updated_at'] = now();

                DB::beginTransaction();

                try {

                    Customer::where(['Customer_id' => $request->Customer_id])->update($CustomerData);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => "Customer Profile updated successfully.",
                        // 'data' => [
                        //     'vendorimg' => isset($CustomerData['Customerimg']) ? asset('upload/Customer/' . $CustomerData['Customerimg']) : null,
                        // ]
                    ], 200);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    throw $th;
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer is not authorized.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function check_schedule_available(Request $request)
    {
        try {
            $request->validate([
                'car_id' => 'required',
                'total_session' => 'required|integer|min:1',
                'SchoolId' => 'required|integer',
                'Schedule_date' => 'required|date',
                'fromtime' => 'required',
                'Totime' => 'required',
                'customer_id' => 'required',
            ]);

            // Convert times to standard format
            $fromTime = Carbon::parse($request->fromtime)->format('H:i:s');
            $toTime = Carbon::parse($request->Totime)->format('H:i:s');
            $startdate = $request->Schedule_date;
            // Dynamically add days based on total_session
            $endDate = Carbon::parse($startdate)->addDays($request->total_session)->format('Y-m-d');
            
            // 1️⃣ Check if any date is already booked → FAIL
            $alreadyBooked = Schedule::where('car_id', $request->car_id)
                ->where('SchoolId', $request->SchoolId)
                ->whereBetween('Schedule_date', [$startdate, $endDate])
                ->whereTime('fromtime', '<=', $fromTime)
                ->whereTime('Totime', '>=', $toTime)
                ->where('Customer_id', '>', 0)
                ->exists();
             
            
            if ($alreadyBooked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some sessions in the selected date range are already booked.',
                ], 400);
            }

            // Count sessions within the given date range
            $availableSessions = Schedule::where('car_id', $request->car_id)
                ->where('SchoolId', $request->SchoolId)
                ->whereBetween('Schedule_date', [$startdate, $endDate])
                ->whereTime('fromtime', '<=', $fromTime)
                ->whereTime('Totime', '>=', $toTime)
                ->where('Customer_id', 0)
                ->count();
           
            // Debugging: Check count
            //Log::info("Available sessions: " . $availableSessions);

            if ($availableSessions >= $request->total_session) {
                // Update available sessions with customer_id
                $updatedRows = Schedule::where('car_id', $request->car_id)
                    ->where('SchoolId', $request->SchoolId)
                    ->whereBetween('Schedule_date', [$request->Schedule_date, $endDate])
                    ->whereTime('fromtime', '<=', $toTime)
                    ->whereTime('Totime', '>=', $fromTime)
                    ->where('Customer_id', 0)
                    ->take($request->total_session);
                //->update(['Customer_id' => $request->customer_id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Sessions are available',
                    'updated_rows' => $updatedRows
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => "Sessions are not available",
            ], 400);
        } catch (\Throwable $th) {
            Log::error('Error checking schedule availability: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function packageorder(Request $request)
    {
        try {
            DB::beginTransaction();

            if (!Auth::guard('customerapi')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer is not authorized.',
                ], 401);
            }

            // Validate Request
            $request->validate([
                "car_id" => 'required',
                "SchoolId" => 'required',
                "package_id" => 'required',
                "start_date" => 'required',
                "schedule_id" => 'required',
                "customer_id" => 'required',
                "customer_name" => 'nullable',
                "customer_phone" => 'nullable',
                "pickup_drop" => 'nullable',
                "pickup_drop_amount" => 'nullable',
                "landmark" => 'nullable',
                "Address" => 'nullable',
                "pincode" => 'required',
                "iAmount" => 'nullable',
                "package_amount" => 'required',
                "advance_payment" => 'required',
                "Advance_payment_percentage" => 'nullable',
                "iNetAmount" => 'required',

            ]);

            // Check if customer exists
            $Customer = Customer::where('Customer_id', $request->customer_id)->first();
            if (!$Customer) {
                return response()->json([
                    'message' => 'Customer not found',
                    'success' => false,
                ], 404);
            }
            $CompanyProfit = CompanyProfit::first();
            $CompanyPercentage = $CompanyProfit->percentage;

            $main_amount = $request->iNetAmount;
            $Adminshare = $CompanyPercentage * $main_amount / 100;
            if ($CompanyProfit) {
                $CompanyPercentage = $CompanyProfit->percentage;

                $main_amount = $request->iNetAmount;
                $Adminshare = $CompanyPercentage * $main_amount / 100;
            }

            // Create Order Data
            $orderdata = array(

                "customer_id" => $request->customer_id,
                "iAmount" => $request->iAmount,
                "iDiscount" => $request->Advance_payment_percentage,
                "Advance_payment_percentage" => $request->Advance_payment_percentage,
                "iNetAmount" => $request->iNetAmount,
                "package_amount" => $request->package_amount,
                "advance_payment" => $request->advance_payment,
                "start_date" => $request->start_date,
                "car_id" => $request->car_id,
                "package_id" => $request->package_id,
                "schedule_id" => $request->schedule_id,
                "customer_name" => $request->customer_name,
                "customer_phone" => $request->customer_phone,
                "landmark" => $request->landmark,
                "SchoolId" => $request->SchoolId,
                "pickup_drop" => $request->pickup_drop,
                "pickup_drop_amount" => $request->pickup_drop_amount,
                "Address" => $request->Address,
                "car_schedule_id" => $request->car_schedule_id,
                "pincode" => $request->pincode,
                "AdminShare" => $Adminshare,
                "strIP" => $request->ip(),
                "created_at" => now(),
            );

            $Order = Packageorder::create($orderdata);

            $paymentData = [
                'order_id' => 0,
                'oid' => $Order->package_order_id,
                'customer_id' => $request->customer_id,
                'receipt' => 'PAY-' . $Order->package_order_id . '-' . time(),
                'amount' => $request->advance_payment,
                'currency' => 'INR',
                'status' => 'Pending',
                'iPaymentType' => 1,
                'created_at' => now()
            ];
            $paymentId = DB::table('card_payment')->insertGetId($paymentData);

            // Create Razorpay Order
            $api = new Api(config('app.razorpay_key'), config('app.razorpay_secret'));
            $razorpayOrderData = [
                'receipt' => $paymentId . '-' . date('dmYHis'),
                'amount' => $request->advance_payment * 100, // Razorpay expects amount in paise
                'currency' => 'INR',
            ];
            $razorpayOrder = $api->order->create($razorpayOrderData);
            $razorpayOrderId = $razorpayOrder['id'];

            //update order table ad update razoper pay prder id

            // Insert First Payment Entry
            DB::commit();

            return response()->json([
                'success' => true,
                "message" => "Order created successfully!",
                "order_id" => $Order->package_order_id,
                "razorpay_order_id" => $razorpayOrderId,
                "payment_id" => $paymentId,
                "advance_payment" => $request->advance_payment,
                "Advance_payment_percentage" => $request->Advance_payment_percentage,
                "remaining_amount" => $request->iNetAmount - $request->advance_payment
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function paymentstatus(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate Request
            $request->validate([
                "order_id" => 'required',
                "razorpay_payment_id" => 'nullable',
                "razorpay_order_id" => 'nullable',
                "paymentId" => 'required',
                "razorpay_signature" => 'nullable',
                "amount" => 'required|numeric',
                "json" => 'required',
                "Advance_payment_percentage" => 'required',

            ]);

            // Find the order
            $Order = Packageorder::with('packagename', 'school')->where("package_order_id", $request->order_id)->first();
            if (!$Order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if ($request->status == "Success") {
                // Mark order as fully paid
                if ($request->Advance_payment_percentage == 100) {
                    CardPayment::where("id", $request->paymentId)->update(['full_payment' => 1]);
                }
                Packageorder::where("package_order_id", $request->order_id)->update(['isPayment' => 1]);
                CardPayment::where("id", $request->paymentId)->update(['razorpay_payment_id' => $request->razorpay_payment_id, 'json' => $request->json, 'razorpay_order_id' => $request->razorpay_order_id, 'razorpay_signature' => $request->razorpay_signature, 'status' => 'Success']);
                $sendEmailDetails = DB::table('sendemaildetails')->where(['id' => 9])->first();
                $data = [
                    "customer_name" => $Order->customer_name,
                    "purchase_date" => $Order->start_date,
                    "package_name" => $Order->packagename->name,
                    "package_amount" => $Order->iNetAmount,
                    "School_name" => $Order->school->name,
                    "status" => $request->status



                ];
                $msg = [
                    'FromMail' => $sendEmailDetails->strFromMail,
                    'Title' => $sendEmailDetails->strTitle,
                    'ToEmail' => $Order->school->email,
                    'Subject' => 'New Subscription'
                ];

                // Send OTP email
                Mail::send('emails.schoolownermail', ['data' => $data], function ($message) use ($msg) {
                    $message->from($msg['FromMail'], $msg['Title']);
                    $message->to($msg['ToEmail'])->subject($msg['Subject']);
                });
            } else {
                Packageorder::where("package_order_id", $request->order_id)->update(['isPayment' => 2]);
                CardPayment::where("id", $request->paymentId)->update(['status' => 'Fail', 'json' => $request->json,]);
                $Order = Packageorder::with('packagename', 'school')->where("package_order_id", $request->order_id)->first();
                // dd($Order->school->name);
                if (!$Order) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found',
                    ], 404);
                }
                $sendEmailDetails = DB::table('sendemaildetails')->where(['id' => 9])->first();
                $data = [
                    "customer_name" => $Order->customer_name,
                    "purchase_date" => $Order->start_date,
                    "package_name" => $Order->packagename->name,
                    "package_amount" => $Order->iNetAmount,
                    "School_name" => $Order->school->name,
                    "status" => 'Fail'



                ];
                $msg = [
                    'FromMail' => $sendEmailDetails->strFromMail,
                    'Title' => $sendEmailDetails->strTitle,
                    'ToEmail' => $Order->school->email,
                    'Subject' => 'New Subscription'
                ];

                // Send OTP email
                Mail::send('emails.schoolownermail', ['data' => $data], function ($message) use ($msg) {
                    $message->from($msg['FromMail'], $msg['Title']);
                    $message->to($msg['ToEmail'])->subject($msg['Subject']);
                });
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->status == "Success" ? "payment successful." : "Payment failed.",
                'status' => $request->status
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function Todaysride(Request $request)
    {
        try {
            $request->validate([
                'Customer_id' => 'required|integer'
            ]);
            $today = now()->toDateString();
            //dd($today);
            $CustomerPackages = Packageorder::with([
                'batchSchedule',
                'CardDetail' => function ($query) {
                    $query->where('full_payment', 1);
                },
                'schedule' => function ($query) use ($today, $request) {
                    $query->whereDate('Schedule_date', $today)->where('Customer_id', $request->Customer_id)
                        ->with('drivername', 'carname', 'schoolname');
                },

            ])
                ->whereIn('schedule_id', function ($query) use ($today, $request) {
                    $query->select('Schedulemasterid')
                        ->from('Schedule')
                        ->whereDate('Schedule_date', $today)
                        ->where('Customer_id', $request->Customer_id);
                })
                ->where('customer_id', $request->Customer_id)
                ->whereIn('isPayment', [1, 2])
                ->whereIn('is_schedule', [0, 1])
                ->get();
            //dd($CustomerPackages->toArray());

            $data = [];
            foreach ($CustomerPackages as $CustomerPackage) {

                $totalAmount = $CustomerPackage->iNetAmount ?? 0;
                $paidAmount = $CustomerPackage->advance_payment ?? 0;
                $dueAmount = $totalAmount - $paidAmount;

                $data[] = [
                    "Customer_name" => $CustomerPackage->customer_name,
                    "package_order_id" => $CustomerPackage->package_order_id,
                    "School Address" => $CustomerPackage->schedule->schoolname->Address ?? '',
                    "Driver phone" => $CustomerPackage->schedule->drivername->mobile_number ?? '',
                    "fromtime" => $CustomerPackage->batchSchedule->fromtime ?? '',
                    "Totime" => $CustomerPackage->batchSchedule->Totime ?? '',
                    "drivername" => $CustomerPackage->schedule->drivername->name ?? '',
                    "School name" => $CustomerPackage->schedule->schoolname->name ?? '',
                    "car_Name" => $CustomerPackage->schedule->carname->CarBrandName ?? '',
                    "attendance" => $CustomerPackage->schedule->attendance ?? '',
                    "full_payment" => $CustomerPackage->CardDetail->full_payment ?? '',
                    "Total Amount" => $totalAmount,
                    "Paid Amount" => $paidAmount,
                    "Due Amount" => $dueAmount,
                    "Pickup Drop" => $CustomerPackage->pickup_drop ?? ''
                ];
            }

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer has no active packages.',
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

    public function mybooking(Request $request)
    {
        try {
            $request->validate([
                'Customer_id' => 'required|integer'
            ]);
            $mybooking = Packageorder::with([
                'batchSchedule',
                'package',
                'schedule' => function ($query) {
                    $query->with('drivername', 'carname', 'schoolname');
                },
                //'carname',
                // 'school'
            ])
                ->where('customer_id', $request->Customer_id)
                ->whereIn('is_schedule', [0, 1])
                ->whereIn('isPayment', [0, 1])
                ->orderBy('package_order_id', 'DESC') 
                ->get();
            //dd($mybooking);

            $data = [];
            foreach ($mybooking as $mybook) {
                $totalAmount = $mybook->iNetAmount ?? 0;
                $paidAmount = CardPayment::where('oid', $mybook->package_order_id)
                    ->where('status', 'Success')
                    ->sum('amount');
                $dueAmount = max(0, $totalAmount - $paidAmount);
                $package = $mybook->package->first();
                $totalsession = $package->session;
                $consumesession = Schedule::where('package_order_id', $mybook->package_order_id)
                    ->where('Customer_id', $mybook->customer_id)
                    ->sum('attendance');
                $available = $totalsession - $consumesession;
                $data[] = [
                    "School name" => $mybook->schedule->schoolname->name ?? '',
                    "School Address" => $mybook->schedule->schoolname->Address ?? '',
                    "School Logo" => 'https://getdemo.in/driving_school/upload/logo/' . $mybook->schedule->schoolname->Logo ?? '',
                    "drivername" => $mybook->schedule->drivername->name ?? '',
                    "Package Name" => $package->name ?? '',
                    "Package Amount" => $package->price ?? '',
                    "Pickup drop Amount" => $package->pickup_drop_amount ?? '',
                    "Customer_name" => $mybook->customer_name,
                    "package_order_id" => $mybook->package_order_id,
                    "Driver phone" => $mybook->schedule->drivername->mobile_number ?? '',
                    "fromtime" => $mybook->batchSchedule->fromtime ?? '',
                    "Totime" => $mybook->batchSchedule->Totime ?? '',
                    "car_Name" => $mybook->schedule->carname->CarBrandName ?? '',
                    "Total Amount" => $totalAmount,
                    "Paid Amount" => $paidAmount,
                    "Due Amount" => $dueAmount,
                    "Pickup Drop" => $mybook->pickup_drop ?? '',
                    "Total session" => $totalsession,
                    "Consume session" => $consumesession,
                    "Remain session" => $available,


                ];
            }

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer has no active packages.',
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
                'message' => 'An error occurred while fetching my booking rides.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function startridetotp(Request $request)
    {
        try {

            $request->validate([
                "package_order_id" => 'required',
                "Customer_id" => 'required',

            ]);
            $otp = mt_rand(100000, 999999);
            $existingCustomer = Packageorder::where(function ($query) use ($request) {
                $query->where('package_order_id', $request->iOrderId)
                    ->orWhere('customer_id', $request->Customer_id);
            })->first();

            if (empty($existingCustomer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A customer with this Package not found.',
                ], 409);
            }
            $updateotp = array(
                'start_ride_otp' => $otp
            );
            DB::beginTransaction();
            Packageorder::where("package_order_id", $request->package_order_id)->update($updateotp);
            DB::commit();
            return response()->json([
                'success' => true,
                'start_otp' => $otp,
                'message' => 'Start ride OTP sent Successfully',
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            $errorMessage = implode(', ', Arr::flatten($e->errors()));

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function DuePayment(Request $request)
    {
        try {
            DB::beginTransaction();

            if (!Auth::guard('customerapi')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer is not authorized.',
                ], 401);
            }

            // Validate Request
            $request->validate([
                "package_order_id" => 'required',
                "customer_id" => 'required',
                "amount" => 'required',

            ]);

            // Check if customer exists
            $Customer = Customer::where('Customer_id', $request->customer_id)->first();
            if (!$Customer) {
                return response()->json([
                    'message' => 'Customer not found',
                    'success' => false,
                ], 404);
            }

            // Create Order Data

            $paymentData = [
                'order_id' => 0,
                'oid' => $request->package_order_id,
                'customer_id' => $request->customer_id,
                'receipt' => '',
                'amount' => $request->amount,
                'currency' => 'INR',
                'status' => 'Pending',
                'iPaymentType' => 1,
                'created_at' => now()
            ];

            $Order = CardPayment::create($paymentData);


            // Create Razorpay Order
            $api = new Api(config('app.razorpay_key'), config('app.razorpay_secret'));
            $razorpayOrderData = [
                'receipt' => $Order->id . '-' . date('dmYHis'),
                'amount' => $request->amount * 100, // Razorpay expects amount in paise
                'currency' => 'INR',
            ];
            $razorpayOrder = $api->order->create($razorpayOrderData);
            $razorpayOrderId = $razorpayOrder['id'];

            //update order table ad update razoper pay prder id

            // Insert First Payment Entry
            DB::commit();

            return response()->json([
                'success' => true,
                "message" => "Due Amount Order created successfully!",
                "order_id" => $request->package_order_id,
                "razorpay_order_id" => $razorpayOrderId,
                "payment_id" => $Order->id,
                "receipt" => $razorpayOrder['receipt'],
                "currency" => $razorpayOrder['currency'],
                "amount" => $request->amount
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function DuePaymentstatus(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate Request
            $request->validate([
                "order_id" => 'required',
                "razorpay_payment_id" => 'nullable',
                "razorpay_order_id" => 'nullable',
                "paymentId" => 'required',
                "razorpay_signature" => 'nullable',
                "amount" => 'required|numeric',
                "json" => 'required',
                "receipt" => 'required',


            ]);

            // Find the order
            $Order = Packageorder::where("package_order_id", $request->order_id)->first();
            if (!$Order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if ($request->status == "Success") {
                // Mark order as fully paid
                Packageorder::where("package_order_id", $request->order_id)->update(['isPayment' => 1]);

                CardPayment::where("id", $request->paymentId)->update(['receipt' => $request->receipt, 'razorpay_payment_id' => $request->razorpay_payment_id, 'json' => $request->json, 'razorpay_order_id' => $request->razorpay_order_id, 'razorpay_signature' => $request->razorpay_signature, 'status' => 'Success', 'full_payment' => 1]);
            } else {
                Packageorder::where("package_order_id", $request->order_id)->update(['isPayment' => 2]);
                CardPayment::where("id", $request->paymentId)->update(['status' => 'Fail', 'json' => $request->json]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->status == "Success" ? "Final payment successful." : "Payment failed.",
                'status' => $request->status
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
