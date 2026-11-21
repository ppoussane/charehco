<article class="grid items-center gap-8 lg:grid-cols-12 lg:gap-12" data-reveal-parent>
    <a href="{{ route('competitions.show', $competition) }}" class="project-card aspect-[16/11] lg:col-span-7" data-reveal-item>
        @if ($competition->coverUrl())
            <img src="{{ $competition->coverUrl() }}" alt="{{ $competition->t('title') }}" loading="lazy">
        @endif
        <span class="project-card__content">
            <span class="pill h-8 text-[14px]">{{ __('Read the brief') }}</span>
        </span>
    </a>
    <div class="lg:col-span-5" data-fade>
        <span class="pill h-8 text-[14px] text-bronze">{{ $competition->status()->label() }}</span>
        <h3 class="display-2 mt-4"><a href="{{ route('competitions.show', $competition) }}" class="hover:text-bronze">{{ $competition->t('title') }}</a></h3>
        @if ($competition->t('summary'))
            <p class="mt-3 text-ink/80">{{ $competition->t('summary') }}</p>
        @endif
        <dl class="mt-5 grid grid-cols-2 gap-4 text-[15px] leading-6">
            <div><dt class="text-ink/55">{{ __('Deadline') }}</dt><dd>{{ $competition->deadline_at->localDate() }}</dd></div>
            @if ($competition->t('prize'))
                <div><dt class="text-ink/55">{{ __('Prize') }}</dt><dd>{{ $competition->t('prize') }}</dd></div>
            @endif
        </dl>
        <a href="{{ route('competitions.show', $competition) }}" class="btn btn-dark mt-7">{{ __('Discover the competition') }}<x-site.arrow /></a>
    </div>
</article>
