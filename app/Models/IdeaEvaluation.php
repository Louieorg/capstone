<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdeaEvaluation extends Model
{
    protected $fillable = [
        'idea_title',
        'category',
        'feasibility',
        'impact',
        'complexity',
        'innovation',
        'overall_score',
        'recommendation',
        'adviser_id'
    ];
}   