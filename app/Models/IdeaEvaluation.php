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
        'adviser_id',
        'adviser_feasibility',
        'adviser_impact',
        'adviser_complexity',
        'adviser_innovation',
        'final_score',
        'ai_title',
        'ai_description',
        'ai_general_objective',
        'ai_specific_objectives',
        'ai_enhanced_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_specific_objectives' => 'array',
            'ai_enhanced_at' => 'datetime',
        ];
    }
}
