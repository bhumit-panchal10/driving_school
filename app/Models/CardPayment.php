<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardPayment extends Model
{
    use HasFactory;
    public $table = 'card_payment';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'order_id',
        'oid',
        'razorpay_payment_id',
        'razorpay_order_id',
        'razorpay_signature',
        'receipt',
        'amount',
        'advance_payment',
        'currency',
        'status',
        'iPaymentType',
        'Remarks',
        'created_at',
        'updated_at',
        'json',
        'customer_id',
        'package_id',
        'full_payment'


    ];
}
