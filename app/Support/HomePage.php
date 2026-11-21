<?php

namespace App\Support;

use App\Models\Competition;
use App\Models\Project;
use App\Models\Setting;

/**
 * Home page images, kept as a published version (what visitors see) and a
 * draft version (what the admin is editing and previewing).
 */
class HomePage
{
    public const PUBLISHED = 'home.published';

    public const DRAFT = 'home.draft';

    /**
     * Editable image slots: key => [label, help, min, max].
     *
     * @var array<string, array{label: string, help: string, min: int, max: int}>
     */
    public const SLOTS = [
        'hero' => [
            'label' => 'Hero slideshow',
            'help' => 'Full-screen images at the top of the page. They cross-fade every few seconds.',
            'min' => 1,
            'max' => 5,
        ],
        'spotlight' => [
            'label' => 'Competition spotlight',
            'help' => 'Full-width images behind the featured competition. Leave empty to use the competition cover.',
            'min' => 0,
            'max' => 5,
        ],
    ];

    /**
     * Images used until the admin publishes their own.
     *
     * @return array<string, array<int, string>>
     */
    public static function defaults(): array
    {
        return [
            'hero' => ['demo/hero-1.jpg', 'demo/hero-2.jpg', 'demo/hero-3.webp'],
            'spotlight' => ['demo/spotlight-1.jpg', 'demo/spotlight-2.jpg', 'demo/spotlight-3.jpg'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function published(): array
    {
        return static::normalize(Setting::get(self::PUBLISHED) ?? static::defaults());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function draft(): array
    {
        return static::normalize(Setting::get(self::DRAFT) ?? static::published());
    }

    /**
     * @param  array<string, array<int, string>>  $content
     */
    public static function saveDraft(array $content): void
    {
        Setting::put(self::DRAFT, static::normalize($content));
    }

    /**
     * Apply the draft to the live home page.
     */
    public static function publish(): void
    {
        Setting::put(self::PUBLISHED, static::draft());
        Setting::query()->where('key', self::DRAFT)->delete();
    }

    public static function discardDraft(): void
    {
        Setting::query()->where('key', self::DRAFT)->delete();
    }

    public static function hasUnpublishedChanges(): bool
    {
        return Setting::query()->where('key', self::DRAFT)->exists()
            && static::draft() !== static::published();
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, array<int, string>>
     */
    public static function normalize(array $content): array
    {
        $normalized = [];

        foreach (self::SLOTS as $slot => $config) {
            $normalized[$slot] = collect($content[$slot] ?? [])
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->take($config['max'])
                ->values()
                ->all();
        }

        return $normalized;
    }

    /**
     * Everything the home page view needs, for the given image content.
     *
     * @param  array<string, array<int, string>>  $content
     * @return array<string, mixed>
     */
    public static function viewData(array $content): array
    {
        $projects = Project::published()
            ->where('show_on_home', true)
            ->latest('published_at')
            ->take(2)
            ->get();

        if ($projects->count() < 2) {
            $projects = $projects->merge(
                Project::published()
                    ->whereNotIn('id', $projects->modelKeys())
                    ->latest('published_at')
                    ->take(2 - $projects->count())
                    ->get()
            );
        }

        $competition = Competition::published()->where('show_on_home', true)->latest('deadline_at')->first()
            ?? Competition::published()->where('deadline_at', '>=', now())->orderBy('deadline_at')->first()
            ?? Competition::published()->latest('deadline_at')->first();

        $spotlight = $content['spotlight'];

        if ($spotlight === [] && $competition?->cover_image) {
            $spotlight = [$competition->cover_image];
        }

        return [
            'heroSlides' => array_map(Media::url(...), $content['hero']),
            'spotlightSlides' => array_map(Media::url(...), $spotlight),
            'projects' => $projects,
            'competition' => $competition,
        ];
    }
}
