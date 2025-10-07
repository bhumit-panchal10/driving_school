<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleMaster extends Model
{
    use HasFactory;
    public $table = 'Batch-Schedule-master';
    protected $primaryKey = 'Schedule_master_id';
    protected $fillable = [
        'Schedule_master_id',
        'driver_id',
        'car_id',
        'SchoolId',
        'fromtime',
        'fromdate',
        'Todate',
        'Totime',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'Schedulemasterid', 'Schedule_master_id');
    }
    public function driver()
    {
        return $this->belongsTo(DrivingSchool::class, 'driver_id', 'SchoolId');
    }
    public function carname()
    {
        return $this->belongsTo(Car::class, 'car_id', 'car_id');
    }
}
