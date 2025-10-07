<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packagelist extends Model
{
    use HasFactory;
    public $table = 'package_list';
    protected $primaryKey = 'package_list_id';
    protected $fillable = [
        'package_list_id',
        'car_id',
        'package_id',
        'start_date',
        'schedule_id',
        'customer_name',
        'customer_phone',
        'landmark',
        'Address',
        'pincode',
        'car_schedule_id',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at',
        'customer_id',
        'iAmount',
        'iDiscount',
        'iNetAmount',
        'isPayment',
        'payment_mode',
        'Advance_payment_percentage',
        'status'


    ];
}
