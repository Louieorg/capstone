<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'impact',
        'category',
        'category_other',
        'frequency',
        'current_process',
        'current_process_other',
        'affected_users',
        'affected_group',
        'affected_group_other',
        'is_anonymous',
        'status',
    ];

    protected $casts = [
        'affected_group' => 'array',   // auto JSON encode/decode
        'is_anonymous'   => 'boolean',
    ];

    public function votes()
    {
        return $this->hasMany(FeedbackVote::class);
    }
}