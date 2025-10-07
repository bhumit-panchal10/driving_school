<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DrivingSchool extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'SchoolId';

    public $table = 'drivingschool';
    protected $fillable = [
        'SchoolId',
        'name',
        'mobile_number',
        'email',
        'password',
        'confirmpassword',
        'BrandName',
        'Logo',
        'Ac_no',
        'Ac_holdername',
        'ifsc_code',
        'isAdminApproved',
        'Certificate',
        'bank_name',
        'City',
        'state_id',
        'Address',
        'otp',
        'GST_No',
        'is_login',
        'License_No',
        'licencesexpiry_date',
        'driver_schoolid',
        'gender',
        'experience',
        'women_allow_ride',
        'expiry_time',
        'latitude',
        'longitude',
        'is_changepasswordfirsttime',
        'isOtpVerified',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'SchoolId', 'SchoolId');
    }

    public function state()
    {
        return $this->belongsTo(StateMaster::class, 'state_id', 'stateId');
    }

    public function batchSchedules()
    {
        return $this->hasMany(ScheduleMaster::class, 'SchoolId', 'SchoolId');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }



    public function getJWTCustomClaims()

    {

        return [];
    }

    protected $hidden = [

        'password'

    ];



    public function getAuthPassword()

    {

        return $this->password;
    }
}
