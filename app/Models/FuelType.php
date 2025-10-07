<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    use HasFactory;
    public $table = 'FuelType';
    protected $primaryKey = 'fueltype_id';
    protected $fillable = [
        'fueltype_id',
        'fueltype_name',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
}
