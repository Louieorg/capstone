<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'is_office_head',
        'office_department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_office_head' => 'boolean',
        ];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedbackComment::class);
    }

    public function savedIdeas(): HasMany
    {
        return $this->hasMany(SavedIdea::class);
    }

    public function isOfficeReviewer(): bool
    {
        return in_array($this->role, ['office_academic', 'office_chief'], true);
    }

    public function officeLabel(): ?string
    {
        return match ($this->role) {
            'office_academic' => 'Academic Affairs',
            'office_chief' => 'Chief Administrative Office',
            default => null,
        };
    }

    public function reviewableCategories(): array
    {
        if (! $this->isOfficeReviewer()) {
            return [];
        }

        return \App\Models\CategoryAssignment::categoriesForOffice($this->role);
    }
}
