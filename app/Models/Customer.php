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


class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    protected $primaryKey = 'Customer_id';
    public $table = 'Customer';
    protected $fillable = [
        'Customer_id',
        'Customer_name',
        'Customer_email',
        'Customer_address',
        'DOB',
        'password',
        'Customer_image',
        'Customer_phone',
        'isOtpVerified',
        'otp',
        'latitude',
        'longitude',
        'expiry_time',
        'iStatus',
        'isDelete',
        'created_at',
        'updated_at',
        'strIP',

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
