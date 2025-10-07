<?php

namespace App\Http\Controllers;

use App\Models\FaqMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;
use NunoMaduro\Collision\Adapters\Phpunit\State;
use App\Models\DrivingSchool;
use App\Models\Packageorder;
use App\Models\NewsLetters;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $school = DrivingSchool::where('is_login', 1)->count();
        $pendingorder = Packageorder::where('is_schedule', 0)->count();
        $ongoingorder = Packageorder::where('is_schedule', 1)->count();

        return view('dashboard.home', compact('school', 'pendingorder', 'ongoingorder'));
    }

    public function getProfile()
    {
        $sessionrole = Auth::user();
        $session = Auth::user()->id;
        $users = User::where('users.id',  $session)->first();
        // dd($users);

        return view('profile', compact('users'));
    }

    public function updateProfile(Request $request)
    {

        // dd($request); 
        $request->validate([

            'editmobile' => ['required', 'digits:10']


        ]);

        $session = Auth::user()->id;
        $users = User::where('id', '=', $session)
            //  ->where('email', 'admin@admin.com')
            ->first();
        //  dd($users);

        $data = array(
            "first_name" => $request->editfirstname,
            "last_name" => $request->editlastname,
            "email" => $request->editemail,
            "address" => $request->editaddress,
            "mobile_number" => $request->editmobile
        );
        // dd($data);
        //9028187696
        $user = User::where('users.id',  $session)->update($data);
        if ($user) {
            Toastr::success('User proflie updated successfully :)', 'Success');
            return redirect()->back();
        } else {
            Toastr::error('Something went wrong :)', 'Error');
            return redirect()->back();
        }
    }

    public function changePassword(Request $request)
    {
        return view('dashboard.Changepassword');
    }

    public function changePassword_update(Request $request)
    {
        DB::beginTransaction();
        try {
            $newPassword = $request->newPassword;
            $confirmPassword = $request->confirmPassword;

            $users = User::where('id',  $request->id)->first();

            if (!Hash::check($request->oldPassword, $users->password)) {
                Toastr::error('Old password does not match');
                return redirect()->back()->withInput();
            }

            if ($newPassword === $confirmPassword) {

                $users->password = Hash::make($request->newPassword);
                $users->save();

                DB::commit();

                Toastr::success('Password changed successfully');
                return redirect()->route('logout');
            } else {
                Toastr::error('new password and confirm password does not match');
                return redirect()->back()->withInput();
            }
        } catch (ValidationException $e) {
            DB::rollBack();
            Toastr::error('Validation Error: ' . implode(', ', $e->errors()));
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
}
