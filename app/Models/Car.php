<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;
    public $table = 'Car';
    protected $primaryKey = 'car_id';
    protected $fillable = [
        'car_id',
        'SchoolId',
        'type',
        'model',
        'CarBrandName',
        'car_registration_no',
        'fueltype',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
    public function packageOrders()
    {
        return $this->hasMany(PackageOrder::class, 'car_id', 'car_id');
    }
}
