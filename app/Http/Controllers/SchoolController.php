<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CityMaster;

use App\Models\StateMaster;

use App\Models\DrivingSchool;

use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;



class SchoolController extends Controller

{

    public function index(Request $request)

    {

        try {
            $Schoollist = DrivingSchool::where('is_login', 1)->paginate(config('app.per_page'));
            return view('School.index', compact('Schoollist'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }
}
