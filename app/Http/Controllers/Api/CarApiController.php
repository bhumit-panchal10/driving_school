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
use App\Models\Customer;
use App\Models\FuelType;
use GuzzleHttp\Client;
use App\Models\DrivingSchool;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


class CarApiController extends Controller

{
    public function CarAdd(Request $request)
    {
        try {

            $request->validate([
                "SchoolId" => 'required',
                "CarBrandName" => 'required',
                "model" => 'required',
                "type" => 'required',
                "Registration_no" => 'required',
                "fueltype" => 'required',


            ]);

            $dealOption = Car::create([
                'CarBrandName' => $request->CarBrandName,
                'model' => $request->model,
                'SchoolId' => $request->SchoolId,
                'type' => $request->type,
                'car_registration_no' => $request->Registration_no,
                'fueltype' => $request->fueltype,
                'strIP' => $request->ip(),
                'created_at' => now()

            ]);
            return response()->json([
                'success' => true,
                'data' => $dealOption,
                'message' => 'Car Added Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    public function Cartype(Request $request)
    {
        try {

            $CarType = CarType::get();
            return response()->json([
                'success' => true,
                'data' => $CarType,
                'message' => 'CarType Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function Fueltype(Request $request)
    {
        try {

            $FuelType = FuelType::get();
            return response()->json([
                'success' => true,
                'data' => $FuelType,
                'message' => 'FuelType Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function CarList(Request $request)
    {
        $request->validate(
            [
                'SchoolId' => 'required'

            ]
        );
        try {

            $Car = Car::where('SchoolId', $request->SchoolId)->get();
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

    public function Carshow(Request $request)
    {
        $request->validate(
            [
                'car_id' => 'required',

            ]
        );
        try {

            $Car = Car::where('car_id', $request->car_id)->first();

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
    public function CarUpdate(Request $request)
    {
        $request->validate(
            [
                'car_id' => 'required',
                'CarBrandName' => 'required',
                'model' => 'required',
                'type' => 'required',
                'Registration_no' => 'required',
                'fueltype' => 'required',


            ]
        );
        try {

            $Car = Car::find($request->car_id);

            if ($Car) {
                $Car->update([
                    'CarBrandName' => $request->CarBrandName,
                    'model' => $request->model,
                    'type' => $request->type,
                    'car_registration_no' => $request->Registration_no,
                    'fueltype' => $request->fueltype,
                    'strIP' => $request->ip(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Car updated successfully.',
                    'data' => $Car,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Car not found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function CarDelete(Request $request)
    {
        try {
            $request->validate([

                "car_id" => 'required'
            ]);
            $Car = Car::find($request->car_id);
            if ($Car) {
                $Car->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Car Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Car not found',
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
