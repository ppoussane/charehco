<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $competition_id
 * @property int $user_id
 * @property string $code Anonymous reference shown to the jury
 * @property string $title
 * @property string|null $statement
 * @property Carbon|null $submitted_at
 */
#[Fillable(['title', 'statement'])]
class CompetitionEntry extends Model
{
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CompetitionEntry $entry) {
            if (blank($entry->code)) {
                do {
                    $code = Str::upper(Str::random(6));
                } while (static::where('code', $code)->exists());

                $entry->code = $code;
            }
        });
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<EntryDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EntryDocument::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Required documents that have not been uploaded yet.
     *
     * @return Collection<int, CompetitionRequirement>
     */
    public function missingRequirements(): Collection
    {
        $uploaded = $this->documents()->pluck('competition_requirement_id')->filter()->all();

        return $this->competition->requirements
            ->where('is_required', true)
            ->reject(fn (CompetitionRequirement $requirement) => in_array($requirement->id, $uploaded, true))
            ->values();
    }

    /**
     * Name shown to a viewer: the jury only sees the anonymous code
     * when the competition uses anonymous judging.
     */
    public function displayNameFor(User $viewer): string
    {
        if ($viewer->isJury() && $this->competition->anonymous_judging) {
            return __('Entry :code', ['code' => $this->code]);
        }

        return $this->user->name;
    }
}
