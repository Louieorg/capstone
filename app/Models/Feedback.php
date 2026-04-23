<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'affected_group' => 'array',   // auto JSON encode/decode
        'is_anonymous' => 'boolean',
        'is_flagged' => 'boolean',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class);
    }

    public function scopeNotFlagged(Builder $query): Builder
    {
        return $query->where('is_flagged', false);
    }
}
