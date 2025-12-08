<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StateMaster;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
//use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\ScheduleMaster;
use GuzzleHttp\Client;
use App\Models\DrivingSchool;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ScheduleApiController extends Controller

{

    public function getdriver(Request $request)
    {
        $request->validate([
            'SchoolId' => 'required'
        ]);
        try {

            $Drivers = DrivingSchool::select('SchoolId', 'name')->where('driver_schoolid', $request->SchoolId)->get();
            return response()->json([
                'success' => true,
                'data' => $Drivers,
                'message' => 'Drivers Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getcar(Request $request)
    {
        $request->validate([
            'SchoolId' => 'required'
        ]);
        try {

            $Car = Car::select(
                'car_id',
                DB::raw("CONCAT(model, '-', CarBrandName) as car_name")
            )
                ->where('SchoolId', $request->SchoolId)
                ->get();


            return response()->json([
                'success' => true,
                'data' => $Car,
                'message' => 'Car Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getschedulemaster(Request $request)
    {
        try {
            $request->validate(
                [
                    'Driver_id' => 'required'

                ]
            );
            // $item->to_datetime = $item->Todate . ' ' . $item->Totime;
            $ScheduleMaster = ScheduleMaster::where('driver_id', $request->Driver_id)->get();
            // dd($ScheduleMaster);
            $ScheduleM = [];
            foreach ($ScheduleMaster as $ScheduleMas) {
                $ScheduleM[] = [
                    'Schedule_master_id' => $ScheduleMas['Schedule_master_id'],
                    'fromdate' => date('d-m-y', strtotime($ScheduleMas['fromdate']))
                        . ' To ' . date('d-m-y', strtotime($ScheduleMas['Todate']))
                        . " - " . date('H:i', strtotime($ScheduleMas['fromtime']))
                        . ' ' . date('H:i', strtotime($ScheduleMas['Totime']))
                ];
            }


            return response()->json([
                'success' => true,
                'data' => $ScheduleM,
                'message' => 'schedulemaster Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

   public function ScheduleAdd(Request $request)
   {
    try {
        // Validate request
        $request->validate([
            'Customer_id' => 'nullable|integer',
            'driver_id' => 'nullable|integer',
            'car_id' => 'required|integer',
            'SchoolId' => 'required|integer',
            'fromtime' => 'required|date_format:H:i:s',
            'Totime' => 'required|date_format:H:i:s',
        ]);

        // Set start date as current date
        $startDate = Carbon::now()->startOfDay();
        $endDate = $startDate->copy()->addDays(90);

        // Generate 90-day date range
        $period = CarbonPeriod::create($startDate, $endDate);

        // ---------------------------------------------
        // 🚫 CHECK IF TIME SLOT ALREADY EXISTS
        // ---------------------------------------------
        $exists = Schedule::where('car_id', $request->car_id)
            ->where('SchoolId', $request->SchoolId)
            ->whereIn('Schedule_date', collect($period)->map(fn($d) => $d->format('Y-m-d')))
            ->where(function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('fromtime', '<', $request->Totime)
                          ->where('Totime', '>', $request->fromtime);
                });
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This car is already scheduled for the selected time.',
            ], 400);
        }

        // ---------------------------------------------
        // ✔ CREATE MASTER ENTRY
        // ---------------------------------------------
        $ScheduleMaster = ScheduleMaster::create([
            'SchoolId' => $request->SchoolId,
            'car_id' => $request->car_id,
            'fromtime' => $request->fromtime,
            'Totime' => $request->Totime,
            'strIP' => $request->ip(),
            'created_at' => now(),
        ]);

        // ---------------------------------------------
        // ✔ INSERT 90 DAYS SCHEDULE
        // ---------------------------------------------
        foreach ($period as $date) {
            Schedule::create([
                'Customer_id' => $request->Customer_id ?? 0,
                'driver_id' => $request->driver_id,
                'car_id' => $request->car_id,
                'SchoolId' => $request->SchoolId,
                'fromtime' => $request->fromtime,
                'Totime' => $request->Totime,
                'Schedule_date' => $date->format('Y-m-d'),
                'Schedulemasterid' => $ScheduleMaster->Schedule_master_id,
                'strIP' => $request->ip(),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedules created successfully.',
        ], 201);

    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'error' => $th->getMessage(),
        ], 500);
    }
}



    public function ScheduleList(Request $request)
    {
        $request->validate([

            'SchoolId' => 'required|integer',
            'car_id' => 'required|integer',
        ]);

        try {
            $ScheduleMaster = ScheduleMaster::with('carname')->where('car_id', $request->car_id)->get();
            $ScheduleMas = [];
            foreach ($ScheduleMaster as $schedule) {

                $ScheduleMas[] = [
                    "car_id" => $schedule->car_id,
                    "car_name" => $schedule->carname->CarBrandName,
                    "model" => $schedule->carname->model,
                    "car_registration_no" => $schedule->carname->car_registration_no,
                    "Schedule_master_id" => $schedule->Schedule_master_id,
                    "fromtime" => $schedule->fromtime,
                    "Totime" => $schedule->Totime,
                ];
            }
            return response()->json([
                'success' => true,
                'data' => $ScheduleMas,
                'message' => 'Batch Fetch Successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    public function SearchDriverSchedule(Request $request)
    {
        try {
            // Validate incoming search parameters
            $request->validate([
                'driver_id' => 'required',
                'Schedule_master_id' => 'nullable|integer',
            ]);

            $query = Schedule::query();

            if ($request->has('driver_id')) {
                $query->where('driver_id', $request->driver_id);
            }

            if ($request->has('Schedule_master_id')) {
                $scheduleMasterId = $request->Schedule_master_id;

                if ($scheduleMasterId == 0) {
                    // Handle case where Schedule_master_id is null (retrieve all with null Schedulemasterid)
                    $query->orWhereNull('Schedulemasterid');
                } else {
                    // Allow 0 and any other integer value
                    $query->where('Schedulemasterid', $scheduleMasterId);
                }
            }

            // Execute the query and fetch results
            $schedules = $query->get();

            return response()->json([
                'success' => true,
                'data' => $schedules,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function ScheduleUpdate(Request $request)
    {

        $request->validate(
            [
                
            "fromtime" => '',
            "Totime" => '',
            "Schedule_id" => 'required',

            ]
        );
        try {

            $Schedule = Schedule::find($request->Schedule_id);

            if ($Schedule) {
                $Schedule->update([
                    'fromtime' => $request->fromtime,
                    'Totime' => $request->Totime,
                    'strIP' => $request->ip(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Schedule updated successfully.',
                    'data' => $Schedule,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule not found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function ScheduleDelete(Request $request)
    {
        try {
            $request->validate([
                "Schedule_master_id" => 'required'
            ]);

            // Find records in both tables
            $scheduleMaster = ScheduleMaster::find($request->Schedule_master_id);
            $schedule = Schedule::where('Schedulemasterid', $request->Schedule_master_id)->get();

            if ($scheduleMaster) {
                // Delete related schedules first
                if ($schedule->isNotEmpty()) {
                    Schedule::where('Schedulemasterid', $request->Schedule_master_id)->delete();
                }

                // Now delete the ScheduleMaster entry
                $scheduleMaster->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Schedule deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule Master not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
