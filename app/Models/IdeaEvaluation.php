<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaEvaluation extends Model
{
    protected $fillable = [
        'idea_title',
        'category',
        'office_id',
        'office_cluster_key',
        'office_source_feedback_ids',
        'office_provenance_fingerprint',
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
            'office_source_feedback_ids' => 'array',
            'ai_specific_objectives' => 'array',
            'ai_enhanced_at' => 'datetime',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
