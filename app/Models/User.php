<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property bool $can_edit
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Role and permission columns are deliberately not mass assignable:
     * only the admin can change them, through explicit assignments.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'participant',
        'can_edit' => false,
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
            'role' => Role::class,
            'can_edit' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isJury(): bool
    {
        return $this->role === Role::Jury;
    }

    public function isParticipant(): bool
    {
        return $this->role === Role::Participant;
    }

    /**
     * Admins, and users the admin granted the "Edit" permission, can publish projects.
     */
    public function canEditContent(): bool
    {
        return $this->isAdmin() || $this->can_edit;
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<CompetitionEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CompetitionEntry::class);
    }

    /**
     * Competitions this jury member has been assigned to.
     *
     * @return BelongsToMany<Competition, $this>
     */
    public function juryCompetitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_jury')->withTimestamps();
    }

    public function isJuryFor(Competition $competition): bool
    {
        return $this->isJury()
            && $this->juryCompetitions()->whereKey($competition->getKey())->exists();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
