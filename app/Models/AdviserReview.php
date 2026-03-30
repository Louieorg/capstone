<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdviserReview extends Model
{
    protected $fillable = [
    'idea_title',
    'category',
    'comment',
    'recommendation',
    'user_id'
];
}
