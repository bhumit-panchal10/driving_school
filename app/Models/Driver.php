<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Driver extends Authenticatable implements JWTSubject
{
    use HasFactory;
    public $table = 'Driver';
    protected $primaryKey = 'Driver_id';
    protected $fillable = [
        'Driver_id',
        'Driver_name',
        'Driver_email',
        'Driver_address',
        'License_No',
        'mobile_number',
        'licencesexpiry_date',
        'SchoolId',
        'password',
        'confirm_password',
        'gender',
        'otp',
        'is_login',
        'last_login',
        'expiry_time',
        'experience',
        'women_allow_ride',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];

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
