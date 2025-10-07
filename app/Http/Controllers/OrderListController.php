<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CompanyProfit;
use App\Models\Packageorder;

use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;
use Spatie\Image\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderListController extends Controller

{

    public function PendingOrderlist(Request $request)

    {

        try {

            $pendingorderlist = Packageorder::with('packagename', 'school')->where('is_schedule', 0)->paginate(env('PER_PAGE'));
            //dd($pendingorderlist);
            return view('OrderList.pendingorderlist', compact('pendingorderlist'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function OngoingOrderlist(Request $request)

    {

        try {

            $ongoingorderlist = Packageorder::where('is_schedule', 1)->paginate(env('PER_PAGE'));
            return view('OrderList.ongoingorderlist', compact('ongoingorderlist'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function CompleteOrderlist(Request $request)

    {

        try {

            $completeorderlist = Packageorder::where('is_schedule', 2)->paginate(env('PER_PAGE'));
            return view('OrderList.completeorderlist', compact('completeorderlist'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }
}
