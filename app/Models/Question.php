<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categories;
use App\Models\SubCategories;

class Question extends Model
{
    use HasFactory;
    public $table = 'test_question';
    protected $fillable = [
        'test_question_id',
        'question',
        'TestPaper_id',
        'option_1',
        'option_2',
        'option_3',
        'answer',
        'image',
        'language',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at'

    ];
}
