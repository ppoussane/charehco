<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Enums\Role;
use App\Models\Concerns\HasTranslations;
use App\Support\Media;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $location
 * @property string|null $prize
 * @property string|null $cover_image
 * @property Carbon $starts_at
 * @property Carbon $deadline_at
 * @property Carbon|null $results_at
 * @property bool $anonymous_judging
 * @property bool $is_published
 * @property bool $show_on_home
 */
#[Fillable(['title', 'title_fa', 'summary', 'summary_fa', 'description', 'description_fa', 'location', 'location_fa', 'prize', 'prize_fa', 'cover_image', 'starts_at', 'deadline_at', 'results_at', 'anonymous_judging', 'is_published', 'show_on_home'])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'deadline_at' => 'datetime',
            'results_at' => 'datetime',
            'anonymous_judging' => 'boolean',
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Competition $competition) {
            if (blank($competition->slug) || $competition->isDirty('title')) {
                $competition->slug = static::uniqueSlug($competition->title, $competition->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'competition';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<CompetitionRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(CompetitionRequirement::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<CompetitionEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CompetitionEntry::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function jury(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'competition_jury')
            ->where('role', Role::Jury->value)
            ->withTimestamps();
    }

    /**
     * @param  Builder<Competition>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function status(): CompetitionStatus
    {
        $now = now();

        return match (true) {
            $now->lt($this->starts_at) => CompetitionStatus::Upcoming,
            $now->lte($this->deadline_at) => CompetitionStatus::Open,
            $this->results_at === null || $now->lt($this->results_at) => CompetitionStatus::Judging,
            default => CompetitionStatus::Closed,
        };
    }

    public function isAcceptingEntries(): bool
    {
        return $this->is_published && $this->status() === CompetitionStatus::Open;
    }

    public function coverUrl(): ?string
    {
        return Media::url($this->cover_image);
    }

    public function descriptionHtml(): string
    {
        return Str::markdown((string) $this->t('description'), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
