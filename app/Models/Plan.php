<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    public $table = 'Plan';
    protected $primaryKey = 'PlanId';
    protected $fillable = [
        'PlanId',
        'name',
        'description',
        'price',
        'pickup_drop_amount',
        'session',
        'SchoolId',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
}
