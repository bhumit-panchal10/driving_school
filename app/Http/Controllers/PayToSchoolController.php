<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CityMaster;

use App\Models\PayToSchool;

use App\Models\DrivingSchool;
use App\Models\Packageorder;
use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;



class PayToSchoolController extends Controller

{

    public function PaySchoollist(Request $request)

    {
        // dd($request);
        try {

            $schoolid = $request->SchoolId;
            //dd($schoolid);
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $PaySchoollist = DB::table('package_order as po')
                ->selectRaw(
                    'COUNT(*) AS TotalOrders, 
                SUM(iNetAmount) AS TotalAmount, 
                SUM(AdminShare) AS TotalAdminShare, 
                po.SchoolId, 
                MIN(po.start_date) AS OrderDate, 
                MIN(po.IsPaidToCompany) AS IsPaidToCompany, 
                MIN(cp.full_payment) AS fullpayment, 
                MIN(ds.name) AS SchoolName'
                )

                ->join('drivingschool as ds', 'ds.SchoolId', '=', 'po.SchoolId')
                ->join('card_payment as cp', 'cp.oid', '=', 'po.package_order_id')
                ->where('cp.full_payment', 1)
                ->where('po.IsPaidToCompany', 0)
                ->when($schoolid, function ($query) use ($schoolid) {
                    $query->where('po.SchoolId', '=', $schoolid);
                })
                ->when($startdate && $enddate, function ($query) use ($startdate, $enddate) {
                    $query->whereBetween('po.start_date', [$startdate, $enddate]);
                })
                ->groupBy('po.SchoolId')
                ->paginate(config('app.per_page'));
            // dd($PaySchoollist);
            // dd($PaySchoollist->toSql());

            $Schoollist = DrivingSchool::where('is_login', 1)->get();

            return view('PayToSchool.index', compact('PaySchoollist', 'Schoollist', 'schoolid', 'startdate', 'enddate'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }
    public function schoolhistory(Request $request, $id)
    {

        try {
            $schoolhistory = PayToSchool::where('School_id', $id)->paginate(config('app.per_page'));
            //dd($schoolhistory);
            return view('SchoolHistory.index', compact('schoolhistory'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }
    public function Add_pay_school_data(Request $request)
    {

        DB::beginTransaction();
        //dd($request);
        try {
            // Validate input data
            $request->validate([
                'ref_no' => 'required',
                'mode' => 'required',


            ]);
            // Create a new Managerate record
            $PayToSchool = PayToSchool::create([
                'School_id' => $request->school_id,
                'School_Name' => $request->school_name,
                'Amount' => $request->amount,
                'date' => $request->date,
                'ref_no' => $request->ref_no,
                'mode' => $request->mode,
                'created_at' => now(),
                'strIP' => $request->ip(),
            ]);
            if ($PayToSchool) {
                // Update related package orders
                Packageorder::where('SchoolId', $request->school_id)->update([
                    'IsPaidToCompany' => 1,
                    'CompanyPaymentId' => $PayToSchool->PayToSchool_id,
                ]);
            } else {
                throw new \Exception("Failed to create payment record.");
            }

            // Commit the transaction
            DB::commit();

            Toastr::success('Payment To School Data Add successfully!', 'Success');
            return redirect()->route('PaySchool.PaySchoollist')->with('success', 'Payment To School Data Add successfully!');
        } catch (ValidationException $e) {
            DB::rollBack();
            $errors = $e->errors();  // Get the validation errors
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $errorMessages[] = $message;
                }
            }

            $errorMessageString = implode(', ', $errorMessages);
            Toastr::error($errorMessageString, 'Error');
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Failed to create Rate: ' . $th->getMessage(), 'Error');
            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }
}
