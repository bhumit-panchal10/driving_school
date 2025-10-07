<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use App\Models\PassMaster;

use App\Models\Role;

use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;

use App\Models\User;

use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;



class UserController extends Controller

{

    /**

     * Display a listing of the resource.

     */

    public function index()

    {

        try {

            $users = User::orderBy('first_name', 'asc')

                ->whereNotIn('id', [1])

                ->paginate(env('PER_PAGE'));



            return view('user.index', compact('users'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }



    public function add()

    {

        try {

            $Role = Role::whereNotIn('id', [1])->get();

            return view('user.add', compact('Role'));
        } catch (\Throwable $th) {

            Toastr::error('Error: ' . $th->getMessage());

            return redirect()->back()->withInput();
        }
    }



    public function store(Request $request)

    {

        // dd($request);

        DB::beginTransaction();

        try {



            $request->validate([

                'first_name' => 'required',

                'last_name' => 'required',

                'email' => 'required|unique:users,email,except,id',

                'mobile_number' => 'required|unique:users,mobile_number,except,id',

                'password' => 'required',

                'role_id' => 'required',

                'address' => 'required'

            ]);



            $Role = Role::where(['id' => $request->role_id])->first();



            User::create([

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'email' => $request->email,

                'mobile_number' => $request->mobile_number,

                'password' => Hash::make($request->password),

                'role_id' => $request->role_id,

                'role_name' => $Role->name,

                'address' => $request->address,

                'created_at' => now(),

                'strIP' => $request->ip(),

            ]);

            // dd($User);



            DB::commit();



            Toastr::success('User created successfully :)', 'Success');

            return redirect()->route('user.index');

            // return back();

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

            Toastr::error('Failed to create user: ' . $th->getMessage(), 'Error');

            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }



    public function edit(Request $request)

    {

        $Role = Role::whereNotIn('id', [1])->get();

        $User = User::where('id', $request->id)->first();



        return view('user.edit', compact('User', 'Role'));
    }



    public function update(Request $request, $id)

    {

        DB::beginTransaction();



        try {



            $request->validate([

                'first_name' => 'required',

                'last_name' => 'required',

                'email' => 'required|unique:users,email,' . $id . ',id',

                'mobile_number' => 'required|unique:users,mobile_number,' . $id . ',id',

                'role_id' => 'required',

                'address' => 'required'

            ]);



            $Role = Role::where(['id' => $request->role_id])->first();

            $data = [

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'email' => $request->email,

                'mobile_number' => $request->mobile_number,

                'role_id' => $request->role_id,

                'role_name' => $Role->name,

                'address' => $request->address,

                'updated_at' => now(),

                'strIP' => $request->ip(),

            ];

            User::where("id", "=", $id)->update($data);



            DB::commit();



            Toastr::success('User updated successfully :)', 'Success');

            return redirect()->route('user.index');
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



    public function delete(Request $request)

    {

        DB::beginTransaction();



        try {

            User::where([

                'status' => 1,

                'id' => $request->id

            ])->delete();



            DB::commit();



            Toastr::success('User deleted successfully :)', 'Success');

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

            $ids = $request->input('pass_ids', []);

            PassMaster::whereIn('id', $ids)->delete();



            Toastr::success('Pass deleted successfully :)', 'Success');

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



    public function user_change_password(Request $request)

    {

        // dd($request);

        DB::beginTransaction();



        try {



            $request->validate([

                'newpassword' => 'required',

                'confirmpassword' => 'required'

            ]);



            $newpassword = $request->newpassword;

            $confirmpassword = $request->confirmpassword;



            if ($newpassword == $confirmpassword) {



                User::where(['id' => $request->userId])->update([

                    'password' => Hash::make($request->confirmpassword)

                ]);

                DB::commit();



                Toastr::success('password updated successfully :)', 'Success');

                return back();
            } else {

                Toastr::error('new password and confirm password does not match :)', 'Error');

                return back();
            }
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
