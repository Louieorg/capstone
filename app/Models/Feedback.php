<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'impact',
        'category',
        'department',
        'category_other',
        'frequency',
        'current_process',
        'current_process_other',
        'affected_users',
        'affected_group',
        'affected_group_other',
        'is_anonymous',
        'is_flagged',
        'attachment_path',
        'attachment_type',
        'status',
        'is_priority',
        'priority_status',
        'priority_taken_by',
        'priority_taken_at',
        'priority_resolved_at',
        'priority_office',
        'reviewed_by',
        'reviewed_at',
        'title_en',
        'description_en',
        'impact_en',
        'translated_at',
    ];

    public function getTranslatedTitleAttribute(): string
    {
        return $this->title_en ?: $this->title;
    }

    public function getTranslatedDescriptionAttribute(): string
    {
        return $this->description_en ?: $this->description;
    }

    public function getTranslatedImpactAttribute(): ?string
    {
        return $this->impact_en ?: $this->impact;
    }

    public function getPublicPriorityStatusAttribute(): ?string
    {
        return match ($this->priority_status) {
            'pending' => 'Awaiting Institutional Review',
            'taken' => 'Under Institutional Review',
            'resolved' => 'Resolved',
            default => null,
        };
    }

    public function takenBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'priority_taken_by');
    }

    protected $casts = [
        'affected_group' => 'array',   // auto JSON encode/decode
        'is_anonymous' => 'boolean',
        'is_flagged' => 'boolean',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedbackComment::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FeedbackEvidence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNotFlagged(Builder $query): Builder
    {
        return $query->where('is_flagged', false);
    }

    public function reviewedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
