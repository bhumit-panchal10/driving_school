<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    public $table = 'setting';
    protected $fillable = [
        'settingId',
        'suv_charges',
        'pet_charges',
        'rain_charges',
        'list_price_percentage',
        'discount_on_ride',
        'airport_railway_charges',
        'iStatus',
        'isDelete',
        'created_at',
        'updated_at',
        'strIP'
    ];
}
