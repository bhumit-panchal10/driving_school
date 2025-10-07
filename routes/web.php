<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\AreaController;

use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\CompanyProfitController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\TestPaperController;

use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\OrderListController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\CMSController;
use App\Http\Controllers\PayToSchoolController;


Route::get('/', function () {
    return view('auth.login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('admin.php', function () {
        return view('dashboard.home');
    });
    Route::get('admin.php', function () {
        return view('dashboard.home');
    });
});

Auth::routes();
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    return 'Cache is cleared';
});

Route::get('/phpinfo', function () {
    phpinfo();
});


Route::get('/admin/login', [LoginController::class, 'login'])->name('admin.login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('authenticate');
Route::get('/logout', [LoginController::class, 'logoutPage'])->name('logout_page');
Route::any('/log-out', [LoginController::class, 'logout'])->name('logout');

Route::group(['namespace' => 'App\Http\Controllers\Auth'], function () {
    // -----------------------------login----------------------------------------//
    // Route::controller(LoginController::class)->group(function () {
    //     Route::get('/admin/login', 'login')->name('login');
    //     Route::post('/login', 'authenticate')->name('authenticate');
    //     Route::get('/logout', 'logout')->name('logout');
    //     Route::get('logout/page', 'logoutPage')->name('logout/page');
    // });

    // ------------------------------ register ----------------------------------//
    Route::controller(RegisterController::class)->group(function () {
        Route::get('/register', 'register')->name('register');
        Route::post('/register', 'storeUser')->name('register');
    });

    // ----------------------------- forget password ----------------------------//
    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::get('forget-password', 'getEmail')->name('forget-password');
        Route::post('forget-password', 'postEmail')->name('forget-password');
    });

    // ----------------------------- reset password -----------------------------//
    Route::controller(ResetPasswordController::class)->group(function () {
        Route::get('reset-password/{token}', 'getPassword');
        Route::post('reset-password', 'updatePassword');
    });
});

Route::get('/run-crone-job', function () {
    Artisan::call('app:generate-daily-schedule'); // Ensure the command name matches exactly
    return 'Cron job has been executed.';
});
// Dashboard routes
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');
Route::get('/profile', [HomeController::class, 'getProfile'])->middleware('auth')->name('profile');
Route::post('/updateprofile', [HomeController::class, 'updateProfile'])->middleware('auth')->name('updateprofile');
Route::get('/Changepassword', [HomeController::class, 'changePassword'])->middleware('auth')->name('Changepassword');
Route::post('/Change_password', [HomeController::class, 'changePassword_update'])->middleware('auth')->name('Change_password');

//Area Master
Route::prefix('admin')->name('School.')->middleware('auth')->group(function () {
    Route::any('/School/index', [SchoolController::class, 'index'])->name('index');
});

Route::prefix('admin')->name('PaySchool.')->middleware('auth')->group(function () {
    Route::any('PaySchoollist', [PayToSchoolController::class, 'PaySchoollist'])->name('PaySchoollist');
    Route::post('Addpayschooldata', [PayToSchoolController::class, 'Add_pay_school_data'])->name('Addpayschooldata');
    Route::get('schoolhistory/{id}', [PayToSchoolController::class, 'schoolhistory'])->name('schoolhistory');
});

Route::prefix('admin')->name('Order.')->middleware('auth')->group(function () {
    Route::any('/PendingOrderlist', [OrderListController::class, 'PendingOrderlist'])->name('PendingOrderlist');
    Route::any('/OngoingOrderlist', [OrderListController::class, 'OngoingOrderlist'])->name('OngoingOrderlist');
    Route::any('/CompleteOrderlist', [OrderListController::class, 'CompleteOrderlist'])->name('CompleteOrderlist');
});

Route::prefix('admin')->name('CompanyProfit.')->middleware('auth')->group(function () {
    Route::any('/CompanyProfit/index', [CompanyProfitController::class, 'index'])->name('index');
    Route::get('/CompanyProfit/add', [CompanyProfitController::class, 'add'])->name('add');
    Route::post('/CompanyProfit/store', [CompanyProfitController::class, 'store'])->name('store');
    Route::get('/CompanyProfit/edit/{id?}', [CompanyProfitController::class, 'edit'])->name('edit');
    Route::post('/CompanyProfit/update/{id?}', [CompanyProfitController::class, 'update'])->name('update');
    Route::delete('/CompanyProfit/delete', [CompanyProfitController::class, 'delete'])->name('delete');
    Route::delete('/CompanyProfit/deleteselected', [CompanyProfitController::class, 'deleteselected'])->name('deleteselected');
    Route::any('/CompanyProfit/updateStatus', [CompanyProfitController::class, 'updateStatus'])->name('updateStatus');
});

//categories Master
Route::prefix('admin')->name('question.')->middleware('auth')->group(function () {
    Route::any('/question/index/{id?}/{lan?}', [QuestionController::class, 'index'])->name('index');
    Route::get('/question/add/{id?}/{lan?}', [QuestionController::class, 'add'])->name('add');
    Route::post('/question/store', [QuestionController::class, 'store'])->name('store');
    Route::get('/question/edit/{id?}', [QuestionController::class, 'edit'])->name('edit');
    Route::post('/question/update/{id?}', [QuestionController::class, 'update'])->name('update');
    Route::delete('/question/delete', [QuestionController::class, 'delete'])->name('delete');
    Route::delete('/question/deleteselected', [QuestionController::class, 'deleteselected'])->name('deleteselected');
    Route::any('/question/updateStatus', [QuestionController::class, 'updateStatus'])->name('updateStatus');
    Route::get('/questionlist/{testpaper_id?}/{lan?}', [QuestionController::class, 'questionlist'])->name('questionlist');
    Route::get('/gujaratiquestion', [QuestionController::class, 'gujaratiquestion'])->name('gujaratiquestion');
    Route::get('/englishquestion', [QuestionController::class, 'englishquestion'])->name('englishquestion');
});

Route::prefix('admin')->name('TestPaper.')->middleware('auth')->group(function () {
    Route::any('/TestPaper/index', [TestPaperController::class, 'index'])->name('index');
    Route::get('/TestPaper/add', [TestPaperController::class, 'add'])->name('add');
    Route::post('/TestPaper/store', [TestPaperController::class, 'store'])->name('store');
    Route::get('/TestPaper/edit/{id?}', [TestPaperController::class, 'edit'])->name('edit');
    Route::post('/TestPaper/update/{id?}', [TestPaperController::class, 'update'])->name('update');
    Route::delete('/TestPaper/delete', [TestPaperController::class, 'delete'])->name('delete');
    Route::delete('/TestPaper/deleteselected', [TestPaperController::class, 'deleteselected'])->name('deleteselected');
    Route::any('/TestPaper/updateStatus', [TestPaperController::class, 'updateStatus'])->name('updateStatus');
});

//Faq Master
Route::prefix('admin')->name('faq.')->middleware('auth')->group(function () {
    Route::get('/faq/index', [FaqController::class, 'index'])->name('index');
    Route::get('/faq/add', [FaqController::class, 'add'])->name('add');
    Route::post('/faq/store', [FaqController::class, 'store'])->name('store');
    Route::get('/faq/edit/{id?}', [FaqController::class, 'edit'])->name('edit');
    Route::get('/faq/view/{id?}', [FaqController::class, 'view'])->name('view');
    Route::post('/faq/update/{id?}', [FaqController::class, 'update'])->name('update');
    Route::delete('/faq/delete', [FaqController::class, 'delete'])->name('delete');
    Route::delete('/faq/deleteselected', [FaqController::class, 'deleteselected'])->name('deleteselected');
    Route::any('/faq/updateStatus', [FaqController::class, 'updateStatus'])->name('updateStatus');
});

//Career Master

//CMS
Route::prefix('admin')->name('cms.')->middleware('auth')->group(function () {
    Route::get('/cms/index', [CMSController::class, 'index'])->name('index');
    Route::get('/cms/edit/{id?}', [CMSController::class, 'edit'])->name('edit');
    Route::post('/cms/update/{id?}', [CMSController::class, 'update'])->name('update');
});
