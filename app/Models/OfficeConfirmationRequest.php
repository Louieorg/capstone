<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeConfirmationRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_STALE = 'stale';

    protected $fillable = [
        'requester_user_id',
        'idea_evaluation_id',
        'office_id',
        'status',
        'decision_outcome',
        'decided_by_user_id',
        'decision_at',
        'office_cluster_key',
        'source_feedback_ids',
        'provenance_fingerprint',
        'stale_at',
        'active_opportunity_id',
    ];

    protected function casts(): array
    {
        return [
            'source_feedback_ids' => 'array',
            'decision_at' => 'datetime',
            'stale_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function ideaEvaluation(): BelongsTo
    {
        return $this->belongsTo(IdeaEvaluation::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
