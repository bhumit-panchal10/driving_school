<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Adminuserpermission;
use App\Models\RolePermission;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Validation\ValidationException;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $Roles = Role::where('id', '!=', 1)->paginate(env('PAR_PAGE_COUNT'));

        return view('role.index', compact('Roles'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $request->validate([
                'name' => 'required|unique:roles'
            ]);

            Role::create([
                'name' => $request->name,
                'created_at' => date('Y-m-d H:i:s'),
                'strIP' => $request->ip(),
            ]);

            DB::commit();

            Toastr::success('Role created successfully :)', 'Success');
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
            Toastr::error('Failed to create role: ' . $th->getMessage(), 'Error');
            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $role = Role::where('id', $request->id)->first();

            return json_encode($role);
        } catch (\Throwable $th) {
            // Rollback and return with Error
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        // dd($request);
        DB::beginTransaction();
        try {

            $request->validate([
                'name' => 'required|unique:roles,name,' . $request->roleid . ',id'
            ]);

            Role::where(['id' => $request->roleid])->update([
                'name' => $request->name,
                'updated_at' => date('Y-m-d H:i:s'),
                'strIP' => $request->ip(),
            ]);

            DB::commit();

            Toastr::success('Role updated successfully :)', 'Success');
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

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {

            Role::where(['id' => $request->id])->delete();

            DB::commit();

            Toastr::success('Role deleted successfully :)', 'Success');
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
            $ids = $request->input('role_ids', []);
            Role::whereIn('id', $ids)->delete();

            Toastr::success('Area deleted successfully :)', 'Success');
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

    public function role_Permission(Request $request, $id)
    {
        try {
            $permission = RolePermission::where(['role_id' => $id])->first();
            // dd($permission);
            return view('role.permission', compact('permission', 'id'));
        } catch (\Exception $e) {
            Toastr::error('An error occurred: ' . $e->getMessage(), 'Error');
            return redirect()->back()->withInput();
        }
    }
    public function role_Permission_store(Request $request)
    {
        DB::beginTransaction();

        try {

            RolePermission::where('role_id', $request->role_id)
                ->delete();

            RolePermission::create([
                'role_id' => $request->role_id ?? 0,
                'MasterEntry' => $request->MasterEntry ?? 0,
                'States' => $request->States ?? 0,
                'City' => $request->City ?? 0,
                'Price' => $request->Price ?? 0,
                'Area' => $request->Area ?? 0,
                'Career' => $request->Career ?? 0,
                'Testimonial' => $request->Testimonial ?? 0,
                'Faq' => $request->Faq ?? 0,
                'News_and_Updates' => $request->News_and_Updates ?? 0,
                'Tags' => $request->Tags ?? 0,
                'Vehicle' => $request->Vehicle ?? 0,
                'Cms' => $request->Cms ?? 0,
                'Goods_Type' => $request->Goods_Type ?? 0,
                'Our_Team' => $request->Our_Team ?? 0,
                'Offer' => $request->Offer ?? 0,
                'Driver_Request' => $request->Driver_Request ?? 0,
                'Driver_List' => $request->Driver_List ?? 0,
                'Driver_Location' => $request->Driver_Location ?? 0,
                'Driver_Pass' => $request->Driver_Pass ?? 0,
                'Seo' => $request->Seo ?? 0,
                'Customer' => $request->Customer ?? 0,
                'Employee_List' => $request->Employee_List ?? 0,
                'Role'  => $request->Role ?? 0,
                'Career_Inquiry'  => $request->Career_Inquiry ?? 0,
                'Contact_Inquiry'  => $request->Contact_Inquiry ?? 0,
                'News_Letter_Inquiry'  => $request->News_Letter_Inquiry ?? 0,
                'created_at' => now(),
                'strIP' => $request->ip()
            ]);

            DB::commit();

            Toastr::success('Roles permission created successfully :)', 'Success');
            // return redirect()->route('role.index');
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
