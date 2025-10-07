<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Vendor extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'vendor_id';

    public $table = 'vendor';
    protected $fillable = [
        'vendor_id',
        'vendorname',
        'vendormobile',
        'vendorimg',
        'vendoraddress',
        'vendorstate',
        'vendorcity',
        'vendorcategory',
        'latitude',
        'longitude',
        'email',
        'expiry_time',
        'otp',
        'password',
        'facbooklink',
        'instragramlink',
        'businessname',
        'businessaddress',
        'vendorsocialpage',
        'businesscategory',
        'businessubcategory',
        'login_id',
        'is_changepasswordfirsttime',
        'isOtpVerified',
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

    public function deal()
    {
        return $this->belongsTo(Deals::class, 'deal_id', 'Deals_id'); // vendor.deal_id matches deals.Deals_id
    }
    public function state()
    {
        return $this->belongsTo(StateMaster::class, 'vendorstate', 'stateId');
    }

    public function city()
    {
        return $this->belongsTo(CityMaster::class, 'vendorcity', 'cityId');
    }
}
