@props(['project', 'ratio' => 'aspect-[4/3.1]'])

<article {{ $attributes }}>
    <a href="{{ route('projects.show', $project) }}" class="project-card {{ $ratio }}" data-reveal-item>
        @if ($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="{{ $project->t('title') }}" loading="lazy">
        @endif
        <span class="project-card__content">
            <span class="font-serif text-[clamp(1.6rem,2.6vw,2.6rem)] leading-tight italic">{{ $project->t('title') }}</span>
            <span class="pill mt-3 h-8 text-[14px]">{{ __('Discover the project') }}</span>
        </span>
    </a>
    <div class="mt-4 flex items-baseline justify-between gap-4 text-[15px]">
        <span class="font-normal">{{ $project->t('title') }}</span>
        <span class="text-ink/60">{{ collect([$project->t('location'), $project->year])->filter()->join(' · ') }}</span>
    </div>
</article>
