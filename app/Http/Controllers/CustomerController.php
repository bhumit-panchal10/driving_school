<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ActiveTrip;
use App\Models\AreaMaster;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Carbon;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {

            $Customer = Customer::select(
                'customer.customerid',
                'customer.customername',
                'customer.customermobile',
                'customer.customeremail',
                'customer.strPhoto',

                'state-masters.stateName',
                'city-masters.cityName'
            )
                ->orderBy('customer.customerid', 'desc')
                ->where(['customer.iStatus' => 1, 'customer.isDelete' => 0])
                ->leftjoin('state-masters', 'state-masters.stateId', '=', 'customer.state')
                ->leftjoin('city-masters', 'city-masters.cityId', '=', 'customer.city')
                ->paginate(env('PER_PAGE'));
            // dd($Customer)    ;

            return view('customer.index', compact('Customer'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    
    public function trip_list(Request $request,$id)
    {
        try {
            
            $FromDate = $request->fromdate;
            $ToDate = $request->todate;
            
            $History = ActiveTrip::orderBy('EnterDateTime', 'asc')
                    ->where(['active-trip.iStatus' => 1, 'active-trip.isDelete' => 0, 'active-trip.iCustomerId' => $id, 'active-trip.iTripStatus' => 5])
                    ->leftjoin('driver-masters', 'active-trip.iDriverId', '=', 'driver-masters.id')
                    ->when($FromDate, fn($query) => $query->where('active-trip.EnterDateTime', '>=', date('Y-m-d 00:00:00', strtotime($FromDate))))
                    ->when($ToDate, fn($query) => $query->where('active-trip.EnterDateTime', '<=', date('Y-m-d 23:59:59', strtotime($ToDate))))
                    ->paginate(env('PER_PAGE'));
                    // ->toSql();
            // dd($History);
            
            $Customer = Customer::where(['customerid' => $id])->first();
            // dd($Customer);
            return view('customer.trip', compact('History','FromDate', 'ToDate' , 'id' , 'Customer'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    
    public function trip_details(Request $request,$id)
    {
        try {
            
            $History = ActiveTrip::orderBy('EnterDateTime', 'asc')
                    ->where(['active-trip.iStatus' => 1, 'active-trip.isDelete' => 0, 'active-trip.iTripId' => $id])
                    ->leftjoin('driver-masters', 'active-trip.iDriverId', '=', 'driver-masters.id')
                    ->first();
                    // ->toSql();
            // dd($History);
            
            $Customer = Customer::where(['customerid' => $id])->first();

            return view('customer.trip_detail', compact('History', 'id' , 'Customer'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    
    // public function trip_details(Request $request,$id)
    // {
    //     try {
            
    //         $FromDate = $request->fromdate;
    //         $ToDate = $request->todate;
            
    //         $History = ActiveTrip::orderBy('iTripId', 'asc')
    //                 ->where(['iStatus' => 1, 'isDelete' => 0, 'iCustomerId' => $id, 'iTripStatus' => 5])
    //                 ->when($FromDate, fn($query) => $query->where('active-trip.EnterDateTime', '>=', date('Y-m-d 00:00:00', strtotime($FromDate))))
    //                 ->when($ToDate, fn($query) => $query->where('active-trip.EnterDateTime', '<=', date('Y-m-d 23:59:59', strtotime($ToDate))))
    //                 ->get();
    //         // dd($History);
            
    //         // Map data to extract necessary fields
    //         $data = $History->map(function ($history) {

    //                 // Extract the first occurrence of a 6-digit pincode from strDestination
    //                 $destinationpincode = null;
    //                 $sourcepincode = null;
    //                 if (preg_match("/\b\d{6}\b/", $history->strSource, $matches)) {
    //                     $sourcepincode = $matches[0];
    //                 }

    //                 if (preg_match("/\b\d{6}\b/", $history->strDestination, $matches)) {
    //                     $destinationpincode = $matches[0];
    //                 }

    //                 $source =  AreaMaster::where(['iStatus' => 1, 'isDelete' => 0, 'areaPincode' => $sourcepincode])->first();
    //                 $destination =  AreaMaster::where(['iStatus' => 1, 'isDelete' => 0, 'areaPincode' => $destinationpincode])->first();

    //                 $rideAmount = $history->decNetAmount ?? $history->iAmountAfterAddCharges;
    //                 // Format date with AM/PM
    //                 $formattedDate = Carbon::parse($history->EnterDateTime)->format('D, jS F h:i A');

    //                 if ($history->iPaymentMode == 0) {
    //                     $PaymentMode = "paid by cash";
    //                 } else {
    //                     $PaymentMode = "paid by online";
    //                 }
                    
    //                 if ($source) {
    //                     $strSource = $source->areaName;
    //                 } else {
    //                     $strSource = $history->strSource;
    //                 }
                    
    //                 if ($destination) {
    //                     $strDestination = $destination->areaName;
    //                 } else {
    //                     $strDestination = $history->strDestination;
    //                 }

    //                 return [
    //                     "id" => $history->iTripId,
    //                     "strSource" =>  $strSource,
    //                     "strDestination" => $strDestination,
    //                     "rideamount" => $rideAmount,
    //                     "time" => Carbon::parse($history->EnterDateTime)->format('H:i'),
    //                     "date" => $formattedDate,
    //                     'iPaymentMode' => $PaymentMode
    //                 ];
    //             });        
    //         // dd($data);

    //         return view('customer.documents', compact('FromDate', 'ToDate' , 'data' , 'id'));
    //     } catch (\Throwable $th) {
    //         Toastr::error('Error: ' . $th->getMessage());
    //         return redirect()->back()->withInput();
    //     }
    // }
}
