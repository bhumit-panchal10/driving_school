<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayToSchool extends Model
{
    use HasFactory;
    public $table = 'PayToSchool';
    protected $primaryKey = 'PayToSchool_id';
    protected $fillable = [
        'PayToSchool_id',
        'School_id',
        'School_Name',
        'Amount',
        'iStatus',
        'isDelete',
        'created_at',
        'updated_at',
        'strIP',
        'date',
        'ref_no',
        'mode'




    ];
}
