<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packageorder extends Model
{
    use HasFactory;
    public $table = 'package_order';
    protected $primaryKey = 'package_order_id';
    protected $fillable = [
        'package_order_id',
        'car_id',
        'package_id',
        'start_date',
        'schedule_id',
        'customer_name',
        'customer_phone',
        'landmark',
        'Address',
        'SchoolId',
        'pincode',
        'start_ride_otp',
        'start_ride',
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
        'status',
        'pickup_drop',
        'pickup_drop_amount',
        'package_amount',
        'advance_payment',
        'AdminShare',
        'IsPaidToCompany',
        'CompanyPaymentId'




    ];
    public function CardDetail()
    {
        return $this->belongsTo(CardPayment::class, 'package_order_id', 'oid');
    }

    public function batchSchedule()
    {
        return $this->belongsTo(ScheduleMaster::class, 'schedule_id', 'Schedule_master_id');
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'Schedulemasterid', 'schedule_id');
    }


    public function carname()
    {
        return $this->belongsTo(Car::class, 'car_id', 'car_id');
    }

    public function school()
    {
        return $this->belongsTo(DrivingSchool::class, 'SchoolId', 'SchoolId');
    }


    public function package()
    {

        return $this->hasMany(Plan::class, 'PlanId', 'package_id');
    }
    public function packagename()
    {

        return $this->belongsTo(Plan::class, 'package_id', 'PlanId');
    }
    public function schedulemaster()
    {
        return $this->hasMany(ScheduleMaster::class, 'Schedule_master_id', 'schedule_id');
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'Customer_id', 'customer_id');
    }
}
