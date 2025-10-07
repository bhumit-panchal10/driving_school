<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StateMaster;
use App\Models\Plan;
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


class PlanApiController extends Controller

{
    public function PlanAdd(Request $request)
    {
        try {

            $request->validate([
                "name" => 'required',
                "description" => 'required',
                "price" => 'required',
                "pickup_drop_amount" => 'required',
                "session" => '',
                "SchoolId" => '',


            ]);

            $Plan = Plan::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'pickup_drop_amount' => $request->pickup_drop_amount,
                'session' => $request->session,
                'SchoolId' => $request->SchoolId,
                'strIP' => $request->ip(),
                'created_at' => now()

            ]);
            return response()->json([
                'success' => true,
                'data' => $Plan,
                'message' => 'Plan Added Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function PlanList(Request $request)
    {
        $request->validate([
            'SchoolId' => 'required',
        ]);
        try {

            $Plan = Plan::where('SchoolId', $request->SchoolId)->get();
            if ($Plan->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No plans found.',
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $Plan,
                'message' => 'Plan Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Planshow(Request $request)
    {
        $request->validate(
            [
                'PlanId' => 'required'

            ]
        );
        try {

            $Plan = Plan::where('PlanId', $request->PlanId)->first();

            return response()->json([
                'success' => true,
                'data' => $Plan,
                'message' => 'Plan Fetch Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function PlanUpdate(Request $request)
    {
        $request->validate(
            [
                'PlanId' => 'required',
                'name' => 'required',
                'description' => 'required',
                'price' => 'required',
                "session" => 'nullable|integer',
                "pickup_drop_amount" => 'required',
                // "SchoolId" => 'nullable|integer',


            ]
        );
        try {

            $Plan = Plan::find($request->PlanId);

            if ($Plan) {
                $Plan->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'price' => $request->price,
                    'session' => $request->session,
                    'pickup_drop_amount' => $request->pickup_drop_amount,

                    // 'SchoolId' => $request->SchoolId,
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Plan updated successfully.',
                    'data' => $Plan,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function PlanDelete(Request $request)
    {
        try {
            $request->validate([

                "PlanId" => 'required'
            ]);
            $Plan = Plan::find($request->PlanId);
            if ($Plan) {
                $Plan->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Plan Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found',
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
