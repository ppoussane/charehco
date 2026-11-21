{{--
    Home page. Rendered for visitors with the published images, and for the
    admin's preview with the draft images ($preview = true).
--}}
<x-layouts::site :overlay-header="true" :preview="$preview ?? false">

    {{-- 1. Hero: full-screen cross-fading slideshow --}}
    <section class="relative h-svh min-h-[560px] overflow-hidden bg-ink" data-parallax-parent aria-label="{{ __('Introduction') }}">
        <div class="absolute inset-0" data-parallax-item data-slider="hero" data-autoplay="7500">
            @foreach ($heroSlides as $i => $src)
                <div class="fade-slide @if ($i === 0) is-active @endif" data-slide>
                    <img src="{{ $src }}" alt="" @if ($i > 0) loading="lazy" @endif fetchpriority="{{ $i === 0 ? 'high' : 'low' }}">
                </div>
            @endforeach
        </div>
        <div class="pointer-events-none absolute -inset-1/4 z-[2] bg-black/45 mix-blend-multiply"></div>

        <div class="pointer-events-none absolute inset-x-0 top-1/2 z-10 -translate-y-1/2">
            <div class="container-site text-center">
                <h1 class="display-1 text-white">
                    <span class="block" data-hero-line>{{ __('Where architecture') }}</span>
                    <em class="block text-gold" data-hero-line>{{ __('competes for ideas') }}</em>
                </h1>
            </div>
        </div>

        @if (count($heroSlides) > 1)
            <div class="slider-dots slider-dots--white absolute bottom-6 left-1/2 z-10 -translate-x-1/2 sm:bottom-12 lg:bottom-[70px]" data-slider-dots="hero">
                @foreach ($heroSlides as $i => $src)
                    <button type="button" @class(['is-active' => $i === 0]) aria-label="{{ __('Show image :n', ['n' => $i + 1]) }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 2. Projects --}}
    <section class="py-16 sm:py-24 lg:py-32" aria-labelledby="home-projects-title">
        <div class="container-site">
            <div class="lg:mx-[8.333%]">
                <div class="max-w-2xl" data-fade>
                    <h2 id="home-projects-title" class="display-2 flex flex-col">
                        <span>{{ __('Ideas become places.') }}</span>
                        <em class="text-bronze">{{ __('Selected projects') }}</em>
                    </h2>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-x-10 gap-y-3" data-fade>
                    <span>{{ __('Charehco — architecture competitions & design') }}</span>
                    <a href="{{ route('projects.index') }}" class="btn btn-dark">
                        {{ __('View all projects') }}
                        <x-site.arrow />
                    </a>
                </div>

                <div class="mt-12 grid gap-10 md:grid-cols-2 md:gap-6 lg:mt-20 xl:gap-8" data-reveal-parent>
                    @forelse ($projects as $i => $project)
                        <x-site.project-card :project="$project" class="{{ $i % 2 === 1 ? 'md:mt-28' : '' }}" />
                    @empty
                        <p class="text-ink/60">{{ __('Projects will appear here once they are published.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Competition spotlight --}}
    @if ($competition)
        <section class="relative h-svh max-h-[820px] min-h-[600px] overflow-hidden bg-ink text-white" data-parallax-parent aria-labelledby="home-competition-title">
            <div class="parallax-media" data-parallax-item data-slider="spotlight" data-autoplay="7500">
                @forelse ($spotlightSlides as $i => $src)
                    <div class="fade-slide @if ($i === 0) is-active @endif" data-slide>
                        <img src="{{ $src }}" alt="" loading="lazy">
                    </div>
                @empty
                    <div class="fade-slide is-active bg-bronze" data-slide></div>
                @endforelse
            </div>
            <div class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-t from-black/75 via-black/30 to-black/10"></div>

            <div class="absolute inset-x-0 bottom-0 z-10 pb-10 sm:pb-16 lg:pb-20">
                <div class="container-site">
                    <div class="grid items-end gap-8 lg:mx-[8.333%] lg:grid-cols-12">
                        <div class="lg:col-span-7" data-fade>
                            <span class="pill">{{ $competition->status()->label() }}</span>
                            <h2 id="home-competition-title" class="display-2 mt-4">{{ $competition->t('title') }}</h2>
                            @if ($competition->t('summary'))
                                <p class="mt-3 max-w-xl text-[16px] leading-7 text-white/80">{{ $competition->t('summary') }}</p>
                            @endif
                            <dl class="mt-6 flex flex-wrap gap-x-10 gap-y-2 text-[15px] leading-6">
                                <div><dt class="text-white/60">{{ __('Deadline') }}</dt><dd>{{ $competition->deadline_at->localDate() }}</dd></div>
                                @if ($competition->t('prize'))
                                    <div><dt class="text-white/60">{{ __('Prize') }}</dt><dd>{{ $competition->t('prize') }}</dd></div>
                                @endif
                                @if ($competition->t('location'))
                                    <div><dt class="text-white/60">{{ __('Location') }}</dt><dd>{{ $competition->t('location') }}</dd></div>
                                @endif
                            </dl>
                            <div class="mt-8 flex flex-wrap gap-x-10 gap-y-3">
                                <a href="{{ route('competitions.show', $competition) }}" class="btn btn-white">
                                    {{ $competition->isAcceptingEntries() ? __('Attend this competition') : __('Discover the competition') }}
                                    <x-site.arrow />
                                </a>
                                <a href="{{ route('competitions.index') }}" class="btn btn-white">
                                    {{ __('All competitions') }}
                                    <x-site.arrow />
                                </a>
                            </div>
                        </div>

                        @if (count($spotlightSlides) > 1)
                            <div class="lg:col-span-5 lg:justify-self-end">
                                <div class="slider-dots slider-dots--white" data-slider-dots="spotlight">
                                    @foreach ($spotlightSlides as $i => $src)
                                        <button type="button" @class(['is-active' => $i === 0]) aria-label="{{ __('Show image :n', ['n' => $i + 1]) }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
</x-layouts::site>
