<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CompanyProfit;

use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;
use Spatie\Image\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CompanyProfitController extends Controller

{

    public function index(Request $request)

    {

        try {

            $companyprofit = CompanyProfit::orderBy('id', 'asc')->paginate(env('PER_PAGE'));
            return view('CompanyProfit.index', compact('companyprofit'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function add()
    {
        try {

            return view('CompanyProfit.add');
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }


    public function store(Request $request)
    {

        //dd($request);
        try {
            // Validate the incoming request
            $request->validate([

                'percentage' => 'required|numeric|min:0|max:40',

            ]);
            // Create the deal
            $Data = CompanyProfit::create([

                'percentage'  => $request->percentage,
                'strIP' => $request->ip(),
                'created_at'        => now()

            ]);

            Toastr::success('Company Profit Percentage Add successfully!', 'Success');
            return redirect()->route('CompanyProfit.index');
        } catch (\Throwable $th) {

            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }




    public function edit(Request $request, $id)

    {

        try {

            $datas = CompanyProfit::where(['iStatus' => 1, 'isDelete' => 0, 'id' => $id])->first();

            return view('CompanyProfit.edit', compact('datas'));
        } catch (\Throwable $th) {

            // Rollback and return with Error

            DB::rollBack();

            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }



    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $request->validate([

                'percentage'     => 'required|numeric|min:0|max:40',
            ]);

            // Update deal details
            CompanyProfit::where(['id' => $id])->update([
                'percentage'              => $request->percentage,
                'updated_at'              => now()
            ]);

            // Commit transaction
            DB::commit();

            Toastr::success('Company Profit Percentage updated successfully!', 'Success');
            return redirect()->route('CompanyProfit.index');
        } catch (\Throwable $th) {
            // Rollback on error
            DB::rollBack();
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }



    public function delete(Request $request)

    {

        DB::beginTransaction();



        try {

            CompanyProfit::where(['iStatus' => 1, 'isDelete' => 0, 'id' => $request->id])->delete();



            DB::commit();



            Toastr::success('Company Profit Percentage deleted successfully :)', 'Success');

            //return back();

            return response()->json(['success' => true]);
        } catch (ValidationException $e) {

            DB::rollBack();

            Toastr::error(implode(', ', $e->errors()));

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {

            DB::rollBack();

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }



    public function deleteselected(Request $request)

    {

        try {

            $ids = $request->input('company_ids', []);

            CompanyProfit::whereIn('id', $ids)->delete();



            Toastr::success('Company Profit Percentage deleted successfully :)', 'Success');

            return back();
        } catch (ValidationException $e) {

            DB::rollBack();

            Toastr::error(implode(', ', $e->errors()));

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {

            DB::rollBack();

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }
}
