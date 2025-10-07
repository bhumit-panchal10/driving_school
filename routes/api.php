<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\SchoolOwnerApiController;
use App\Http\Controllers\Api\CarApiController;
use App\Http\Controllers\Api\FrontApiController;
use App\Http\Controllers\Api\DriverApiController;
use App\Http\Controllers\Api\ScheduleApiController;
use App\Http\Controllers\Api\PlanApiController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TestPaperApiController;
use Illuminate\Support\Facades\Artisan;
use App\Mail\MyTestEmail;
use Illuminate\Support\Facades\Mail;


Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    return 'Cache is cleared';
});


//vendor api
Route::post('/schoolowner-registration', [SchoolOwnerApiController::class, 'schoolowner_new_registration'])->name('schoolowner_new_registration');
Route::post('/login', [SchoolOwnerApiController::class, 'login']);
Route::post('/verifyOTP', [SchoolOwnerApiController::class, 'verifyOTP'])->name('verifyOTP');
Route::post('/schoolowner/change/password', [SchoolOwnerApiController::class, 'change_password'])->name('change_password');
Route::post('/generate-image', [SchoolOwnerApiController::class, 'generateImage'])->name('generateImage');
Route::post('/schoolowner/forgot/password', [SchoolOwnerApiController::class, 'forgot_password'])->name('forgot_password');
Route::post('/schoolowner/forgot/password/verifyOTP', [SchoolOwnerApiController::class, 'forgot_password_verifyOTP'])->name('forgot_password_verifyOTP');
Route::post('/logout', [SchoolOwnerApiController::class, 'logout']);
Route::post('/schoolowner/profile', [SchoolOwnerApiController::class, 'profiledetails'])->name('profiledetails');
Route::post('/schoolowner/profile/update', [SchoolOwnerApiController::class, 'profileUpdate'])->name('profileUpdate');
Route::get('/states', [SchoolOwnerApiController::class, 'statelist'])->name('statelist');
Route::post('/schoolowner/profile/update', [SchoolOwnerApiController::class, 'profileUpdate'])->name('profileUpdate');
Route::post('/schoolowner/customerschedule', [SchoolOwnerApiController::class, 'customerschedule'])->name('customerschedule');
Route::post('/schoolowner/DriverAssign', [SchoolOwnerApiController::class, 'DriverAssign'])->name('DriverAssign');
Route::post('/schoolowner/Driver', [SchoolOwnerApiController::class, 'Driver'])->name('Driver');
Route::post('/schoolowner/ongoingcustomer', [SchoolOwnerApiController::class, 'ongoingcustomer'])->name('ongoingcustomer');
Route::post('/schoolowner/schedule_pending_session', [SchoolOwnerApiController::class, 'schedule_pending_session'])->name('schedule_pending_session');
Route::post('/schoolowner/MarkAscomplete', [SchoolOwnerApiController::class, 'MarkAscomplete'])->name('MarkAscomplete');
Route::post('/schoolowner/cancelorder', [SchoolOwnerApiController::class, 'cancelorder'])->name('cancelorder');
Route::post('/schoolowner/completeorder', [SchoolOwnerApiController::class, 'completeorder'])->name('completeorder');

//customer api
Route::post('/customer-registration', [CustomerController::class, 'customer_new_registration'])->name('customer_new_registration');
Route::post('/customer/verifyOTP', [CustomerController::class, 'verifyOTP'])->name('verifyOTP');
Route::post('/customerlogin', [CustomerController::class, 'customerlogin']);
Route::post('/customer/search', [CustomerController::class, 'search'])->name('search');
Route::post('/customer/check_schedule_available', [CustomerController::class, 'check_schedule_available'])->name('check_schedule_available');
Route::post('/customer/packageorder', [CustomerController::class, 'packageorder'])->name('packageorder');
Route::post('/customer/profiledetails', [CustomerController::class, 'profiledetails'])->name('profiledetails');
Route::post('/customer/profile/update', [CustomerController::class, 'profileUpdate'])->name('profileUpdate');
Route::post('/customer/paymentstatus', [CustomerController::class, 'paymentstatus'])->name('paymentstatus');
Route::post('/customer/Todaysride', [CustomerController::class, 'Todaysride'])->name('Todaysride');
Route::post('/customer/startridetotp', [CustomerController::class, 'startridetotp'])->name('startridetotp');
Route::post('/customer/mybooking', [CustomerController::class, 'mybooking'])->name('mybooking');
Route::post('/customer/DuePayment', [CustomerController::class, 'DuePayment'])->name('DuePayment');
Route::post('/customer/DuePaymentstatus', [CustomerController::class, 'DuePaymentstatus'])->name('DuePaymentstatus');

//Test Paper
Route::post('/TestPaperlist', [TestPaperApiController::class, 'TestPaperlist'])->name('TestPaperlist');
Route::post('/Questionlist', [TestPaperApiController::class, 'Questionlist'])->name('Questionlist');

//car api
Route::post('/CarAdd', [CarApiController::class, 'CarAdd'])->name('CarAdd');
Route::get('/CarType', [CarApiController::class, 'Cartype'])->name('Cartype');
Route::get('/FuelType', [CarApiController::class, 'Fueltype'])->name('Fueltype');
Route::post('/CarList', [CarApiController::class, 'CarList'])->name('CarList');
Route::post('/Carshow', [CarApiController::class, 'Carshow'])->name('Carshow');
Route::post('/CarUpdate', [CarApiController::class, 'CarUpdate'])->name('CarUpdate');
Route::post('/CarDelete', [CarApiController::class, 'CarDelete'])->name('CarDelete');

//Driver api
Route::post('/DriverAdd', [DriverApiController::class, 'DriverAdd'])->name('DriverAdd');
Route::get('/getschool', [DriverApiController::class, 'getschool'])->name('getschool');
Route::post('/DriverList', [DriverApiController::class, 'DriverList'])->name('DriverList');
Route::post('/Drivershow', [DriverApiController::class, 'Drivershow'])->name('Drivershow');
Route::post('/DriverUpdate', [DriverApiController::class, 'DriverUpdate'])->name('DriverUpdate');
Route::post('/DriverDelete', [DriverApiController::class, 'DriverDelete'])->name('DriverDelete');
Route::post('/Driver/setpassword', [DriverApiController::class, 'set_password'])->name('set_password');
Route::post('/Driver/forgotpassword', [DriverApiController::class, 'forgot_password'])->name('forgot_password');
Route::post('/Driver/forgot/password/verifyOTP', [DriverApiController::class, 'forgot_password_verifyOTP'])->name('forgot_password_verifyOTP');
Route::post('/Driver/Todayride', [DriverApiController::class, 'Todayride'])->name('Todayride');
Route::post('/Driver/startrideverifyOTP', [DriverApiController::class, 'driver_startrideverifyOTP'])->name('driver_startrideverifyOTP');
Route::post('/Driver/Profile', [DriverApiController::class, 'Profile']);

//Plan api
Route::post('/PlanAdd', [PlanApiController::class, 'PlanAdd'])->name('PlanAdd');
Route::post('/PlanList', [PlanApiController::class, 'PlanList'])->name('PlanList');
Route::post('/Planshow', [PlanApiController::class, 'Planshow'])->name('Planshow');
Route::post('/PlanUpdate', [PlanApiController::class, 'PlanUpdate'])->name('PlanUpdate');
Route::post('/PlanDelete', [PlanApiController::class, 'PlanDelete'])->name('PlanDelete');

//Schedule api
Route::post('/ScheduleAdd', [ScheduleApiController::class, 'ScheduleAdd'])->name('ScheduleAdd');
Route::post('/ScheduleList', [ScheduleApiController::class, 'ScheduleList'])->name('ScheduleList');
Route::post('/getdriver', [ScheduleApiController::class, 'getdriver'])->name('getdriver');
Route::post('/getschedulemaster', [ScheduleApiController::class, 'getschedulemaster'])->name('getschedulemaster');
Route::post('/getcar', [ScheduleApiController::class, 'getcar'])->name('getcar');
Route::post('/SearchDriverSchedule', [ScheduleApiController::class, 'SearchDriverSchedule'])->name('SearchDriverSchedule');
Route::post('/ScheduleUpdate', [ScheduleApiController::class, 'ScheduleUpdate'])->name('ScheduleUpdate');
Route::post('/ScheduleDelete', [ScheduleApiController::class, 'ScheduleDelete'])->name('ScheduleDelete');

Route::get('/testroute', function () {
    $name = "Funny Coder";

    // The email sending is done using the to method on the Mail facade
    Mail::to('dev2.apolloinfotech@gmail.com')->send(new MyTestEmail($name));
});


Route::get('/run-scheduled-notifications', function () {
    Artisan::call('send:scheduled-notifications');
    return 'Scheduled ride notifications command has been executed.';
});
