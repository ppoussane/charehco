<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Media;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $body
 * @property string|null $location
 * @property int|null $year
 * @property string|null $category
 * @property string|null $cover_image
 * @property bool $is_published
 * @property bool $show_on_home
 * @property Carbon|null $published_at
 */
#[Fillable(['title', 'title_fa', 'summary', 'summary_fa', 'body', 'body_fa', 'location', 'location_fa', 'year', 'category', 'category_fa', 'cover_image', 'is_published', 'show_on_home', 'published_at'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
            'published_at' => 'datetime',
            'year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Project $project) {
            if (blank($project->slug) || $project->isDirty('title')) {
                $project->slug = static::uniqueSlug($project->title, $project->id);
            }

            if ($project->is_published && blank($project->published_at)) {
                $project->published_at = now();
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'project';
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<ProjectImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function coverUrl(): ?string
    {
        return Media::url($this->cover_image);
    }

    public function bodyHtml(): string
    {
        return Str::markdown((string) $this->t('body'), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
