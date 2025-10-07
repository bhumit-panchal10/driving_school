<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categories;
use App\Models\SubCategories;

class CompanyProfit extends Model
{
    use HasFactory;
    public $table = 'company_profit';
    protected $fillable = [
        'id',
        'percentage',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
}
