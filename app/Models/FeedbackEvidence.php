<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackEvidence extends Model
{
    use HasFactory;

    protected $table = 'feedback_evidence';

    protected $fillable = [
        'feedback_id',
        'user_id',
        'file_path',
        'file_name',
        'file_type',
        'mime_type',
        'file_size',
        'caption',
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
