<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    public $table = 'Image';
    protected $fillable = [
        'id',
        'subcat_id',
        'category_id',
        'Image',
        'cat_name',
        'subcat_name',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id', 'Categories_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategories::class, 'subcat_id', 'iSubCategoryId');
    }
}
