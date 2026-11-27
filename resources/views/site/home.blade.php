{{--
    Home page. Rendered for visitors with the published images, and for the
    admin's preview with the draft images ($preview = true).

    Competitions come first: the hero leads to the current competition, the
    spotlight presents it, and the steps explain how to take part. Projects
    and a closing banner follow.
--}}
<x-layouts::site overlay-header="dark" :preview="$preview ?? false">

    {{-- 1. Hero: the turning 3D villa and the way into the current competition --}}
    <section class="relative overflow-hidden pt-28 pb-14 sm:pt-32 lg:flex lg:min-h-[min(100svh,60rem)] lg:items-center lg:pt-36 lg:pb-16" aria-labelledby="home-title">
        <div class="container-site">
            <div class="grid gap-y-8 lg:grid-cols-12 lg:gap-x-8 xl:gap-x-12">
                <div class="lg:col-span-5 lg:self-end xl:col-span-4">
                    <h1 id="home-title" class="display-1">
                        <span class="block" data-hero-line>{{ __('Where architecture') }}</span>
                        <em class="block text-bronze" data-hero-line>{{ __('competes for ideas') }}</em>
                    </h1>
                    <p class="mt-6 max-w-md text-ink/80" data-hero-line>{{ __('Open architecture competitions for architects, designers and students. Read the brief, send your design, and an independent jury chooses the winners.') }}</p>
                </div>

                {{-- Four views: hover and scroll (or drag) to turn to the next one. See resources/js/villa. --}}
                <figure class="lg:col-span-7 lg:col-start-6 lg:row-span-2 lg:row-start-1 lg:self-center xl:col-span-8 xl:col-start-5">
                    <div class="villa-stage" data-villa data-frames="{{ $villaFrames }}" aria-describedby="villa-hint"
                        data-label="{{ __('Turn the 3D model of a two-level modern villa') }}"
                        data-text-view="{{ __('View :n of :total') }}">
                        <img class="villa-stage__poster" src="{{ str_replace('{n}', '00', $villaFrames) }}" width="1000" height="688"
                            alt="{{ __('Model of a two-level modern villa') }}" fetchpriority="high">
                    </div>
                    <figcaption id="villa-hint" class="villa-hint">
                        <span>
                            <span class="villa-hint__hover">{{ __('Hover over the villa and scroll to turn it.') }}</span>
                            <span class="villa-hint__touch">{{ __('Swipe sideways to turn the villa.') }}</span>
                            <span class="sr-only">{{ __('You can also drag it, or use the arrow keys.') }}</span>
                        </span>
                        <span class="slider-dots villa-dots" data-villa-dots data-label="{{ __('Show view :n') }}"></span>
                    </figcaption>
                </figure>

                <div class="lg:col-span-5 lg:row-start-2 lg:self-start xl:col-span-4">
                    @if ($competition)
                        <div class="border-t border-ink/15 pt-6" data-hero-line>
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                                <span class="pill h-8 text-[14px] text-bronze">{{ $competition->status()->label() }}</span>
                                <span class="text-[15px] text-ink/65">{{ __('Deadline') }}: {{ $competition->deadline_at->localDate() }}</span>
                            </div>
                            <p class="display-3 mt-4">
                                <a href="{{ route('competitions.show', $competition) }}" class="hover:text-bronze">{{ $competition->t('title') }}</a>
                            </p>
                            <div class="mt-6 flex flex-wrap gap-x-10 gap-y-3">
                                <a href="{{ route('competitions.show', $competition) }}" class="btn btn-dark">
                                    {{ $competition->isAcceptingEntries() ? __('Attend this competition') : __('Discover the competition') }}
                                    <x-site.arrow />
                                </a>
                                <a href="{{ route('competitions.index') }}" class="btn btn-dark">
                                    {{ __('All competitions') }}
                                    <x-site.arrow />
                                </a>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('competitions.index') }}" class="btn btn-dark" data-hero-line>
                            {{ __('All competitions') }}
                            <x-site.arrow />
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Competition spotlight --}}
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

    {{-- 3. How taking part works --}}
    <section id="how-it-works" class="bg-white py-16 sm:py-24 lg:py-32" aria-labelledby="home-steps-title">
        <div class="container-site">
            <div class="lg:mx-[8.333%]">
                <div class="flex flex-wrap items-end justify-between gap-x-10 gap-y-6" data-fade>
                    <h2 id="home-steps-title" class="display-2 flex max-w-2xl flex-col">
                        <span>{{ __('How a competition works') }}</span>
                        <em class="text-bronze">{{ __('From brief to results') }}</em>
                    </h2>
                    <a href="{{ route('competitions.index') }}" class="btn btn-dark">
                        {{ __('See open competitions') }}
                        <x-site.arrow />
                    </a>
                </div>

                <x-site.competition-steps class="mt-12 lg:mt-16" data-fade />
            </div>
        </div>
    </section>

    {{-- 4. Projects --}}
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
                    <span>{{ __('Chareharch — architecture competitions & design') }}</span>
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

    {{-- 5. Banner: full-width photos behind a last invitation to take part --}}
    @if (count($bannerSlides))
        <section class="relative h-[70svh] min-h-[480px] overflow-hidden bg-ink text-white" data-parallax-parent aria-labelledby="home-banner-title">
            <div class="parallax-media" data-parallax-item data-slider="banner" data-autoplay="7500">
                @foreach ($bannerSlides as $i => $src)
                    <div class="fade-slide @if ($i === 0) is-active @endif" data-slide>
                        <img src="{{ $src }}" alt="" loading="lazy">
                    </div>
                @endforeach
            </div>
            <div class="pointer-events-none absolute -inset-1/4 z-[2] bg-black/55 mix-blend-multiply"></div>

            <div class="absolute inset-x-0 top-1/2 z-10 -translate-y-1/2">
                <div class="container-site text-center" data-fade>
                    <h2 id="home-banner-title" class="display-1">
                        <span class="block">{{ __('Have an idea for a better place?') }}</span>
                        <em class="block text-gold">{{ __('Put it to the jury.') }}</em>
                    </h2>
                    <div class="mt-8 flex justify-center">
                        <a href="{{ route('competitions.index') }}" class="btn btn-white">
                            {{ __('Enter a competition') }}
                            <x-site.arrow />
                        </a>
                    </div>
                </div>
            </div>

            @if (count($bannerSlides) > 1)
                <div class="slider-dots slider-dots--white absolute bottom-6 left-1/2 z-10 -translate-x-1/2 sm:bottom-10" data-slider-dots="banner">
                    @foreach ($bannerSlides as $i => $src)
                        <button type="button" @class(['is-active' => $i === 0]) aria-label="{{ __('Show image :n', ['n' => $i + 1]) }}"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layouts::site>
