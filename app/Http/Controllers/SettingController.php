<?php



namespace App\Http\Controllers;



use App\Models\Career;

use Illuminate\Http\Request;

use App\Models\Setting;

use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;



class SettingController extends Controller

{

    public function index(Request $request)

    {

        try {



            $data = Setting::orderBy('settingId', 'desc')->first();

            // dd($datas);

            return view('setting.index', compact('data'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }



    public function update(Request $request)

    {

        DB::beginTransaction();

        try {



            $data = [

                'suv_charges' => $request->suv_charges,

                'pet_charges' => $request->pet_charges,

                'rain_charges' => $request->rain_charges,

                'list_price_percentage' => $request->list_price_percentage,

                'discount_on_ride' => $request->discount_on_ride,

                'airport_railway_charges' => $request->airport_railway_charges,

                'updated_at' => now(),

                'strIP' => $request->ip(),

            ];

            Setting::where("settingId", "=", $request->settingId)->update($data);

            DB::commit();



            Toastr::success('Setting updated successfully :)', 'Success');

            return back();
        } catch (ValidationException $e) {

            DB::rollBack();

            $errors = $e->errors(); // Get the errors array

            $errorMessages = []; // Initialize an array to hold error messages



            // Loop through the errors array and flatten the error messages

            foreach ($errors as $field => $messages) {

                foreach ($messages as $message) {

                    $errorMessages[] = $message;
                }
            }

            // Join all error messages into a single string

            $errorMessageString = implode(', ', $errorMessages);

            Toastr::error($errorMessageString, 'Error');

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {

            DB::rollBack();

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }
}
