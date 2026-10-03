<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
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
        'role',
        'is_office_head',
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

    public function representedOffices(): HasMany
    {
        return $this->hasMany(Office::class, 'representative_user_id');
    }

    /**
     * Whether the user currently represents the given active office.
     *
     * Representation is resolved from the office the user represents right
     * now, not from a role or from an office/category assignment, so an
     * office report is only ever attributed to an office this user actually
     * speaks for.
     */
    public function representsActiveOffice(mixed $officeId): bool
    {
        if ($officeId === null || $officeId === '') {
            return false;
        }

        return $this->representedOffices()
            ->where('offices.id', (int) $officeId)
            ->where('offices.is_active', true)
            ->exists();
    }

    /**
     * The active offices this user represents right now.
     *
     * Representation is a relationship, never a role: only offices that name
     * this user as their representative_user_id and are still active count. A
     * deactivated office stops being a current position immediately.
     *
     * @return Collection<int, Office>
     */
    public function activeRepresentedOffices(): Collection
    {
        return $this->representedOffices()
            ->where('offices.is_active', true)
            ->get();
    }

    /**
     * Whether the user currently represents at least one active office.
     *
     * Display only. This grants no authority: confirming or declining an
     * OfficeConfirmationRequest still requires Office.representative_user_id,
     * which is resolved fresh at decision time.
     */
    public function isOfficeRepresentative(): bool
    {
        return $this->representedOffices()
            ->where('offices.is_active', true)
            ->exists();
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
