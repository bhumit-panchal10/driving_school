<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategories extends Model
{
    use HasFactory;
    public $table = 'subcategory';
    protected $primaryKey = 'iSubCategoryId';
    protected $fillable = [
        'iSubCategoryId',
        'iCategoryId',
        'strSubCategoryName',
        'strCategoryName',
        'strSlugName',
        'SubCategories_img',
        'subCategory_icon',
        'display_homepage',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
}
