<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    public $table = 'Categories';
    protected $primaryKey = 'Categories_id';
    protected $fillable = [
        'Categories_id',
        'Category_name',
        'Categories_slug',
        'Categories_img',
        'Categories_icon',
        'display_homepage',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
}
