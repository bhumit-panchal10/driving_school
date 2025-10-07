<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Driver;
use App\Models\DrivingSchool;
use App\Models\Car;

class Schedule extends Model
{
    use HasFactory;
    public $table = 'Schedule';
    protected $primaryKey = 'Schedule_id';
    protected $fillable = [
        'Schedule_id',
        'Customer_id',
        'driver_id',
        'status',
        'fromtime',
        'fromdate',
        'Todate',
        'Totime',
        'pickup_drop_point',
        'car_id',
        'day',
        'Schedule_date',
        'attendance',
        'SchoolId',
        'package_order_id',
        'Schedulemasterid',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
    public function batchSchedule()
    {
        return $this->belongsTo(ScheduleMaster::class, 'Schedulemasterid', 'Schedule_master_id');
    }
    public function packageOrder()
    {
        return $this->belongsTo(Packageorder::class, 'Schedulemasterid', 'schedule_id');
    }
    public function package_Order()
    {
        return $this->belongsTo(Packageorder::class, 'package_order_id', 'package_order_id');
    }
    public function school()
    {
        return $this->belongsTo(DrivingSchool::class, 'SchoolId', 'SchoolId');
    }

    public function drivername()
    {
        return $this->belongsTo(DrivingSchool::class, 'driver_id', 'SchoolId');
    }

    public function schoolname()
    {
        return $this->belongsTo(DrivingSchool::class, 'SchoolId', 'SchoolId');
    }

    public function carname()
    {
        return $this->belongsTo(Car::class, 'car_id', 'car_id');
    }
}
