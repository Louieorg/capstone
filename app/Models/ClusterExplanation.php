<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClusterExplanation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'patterns' => 'array',
            'experiences' => 'array',
            'generated_at' => 'datetime',
            'last_attempted_at' => 'datetime',
        ];
    }
}
