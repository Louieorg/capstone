<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdviserReview extends Model
{
    protected $fillable = [
        'idea_title',
        'category',
        'comment',
        'recommendation',
        'user_id',
        'feasibility',
        'impact',
        'complexity',
        'innovation',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
