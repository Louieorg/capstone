<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackComment extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'feedback_id',
        'user_id',
        'body',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
