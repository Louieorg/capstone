<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
'user_id',        
'title',
'description',
'impact',
'category',
'frequency',
'current_process',
'affected_users',
'status',
'affected_group',
'is_anonymous'
];

 public function votes()
    {
        return $this->hasMany(FeedbackVote::class);
    }
}


