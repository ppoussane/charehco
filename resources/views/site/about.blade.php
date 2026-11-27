@php
    $principles = [
        ['title' => __('Open to all'), 'text' => __('Anyone with an idea can take part: established practices, young offices and students alike.')],
        ['title' => __('Fair'), 'text' => __('A clear brief and anonymous judging give every entry the same chance.')],
        ['title' => __('Inspiring'), 'text' => __('Winning ideas and shared projects are published to inspire the next generation of architects.')],
    ];
@endphp

<x-layouts::site :title="__('About us')" :description="__('Chareharch organises open architecture competitions and shares the projects that inspire them.')" :overlay-header="true">
    {{-- Hero --}}
    <section class="relative h-[80svh] min-h-[520px] overflow-hidden bg-ink" data-parallax-parent>
        <div class="absolute inset-0" data-parallax-item>
            @if ($heroImage)
                <img src="{{ $heroImage }}" alt="" class="size-full object-cover" fetchpriority="high">
            @endif
        </div>
        <div class="pointer-events-none absolute -inset-1/4 z-[2] bg-black/45 mix-blend-multiply"></div>
        <div class="absolute inset-x-0 top-1/2 z-10 -translate-y-1/2">
            <div class="container-site text-center text-white">
                <h1 class="display-1">
                    <span class="block" data-hero-line>{{ __('About Chareharch') }}</span>
                    <em class="block text-gold" data-hero-line>{{ __('Architecture through open competition') }}</em>
                </h1>
            </div>
        </div>
    </section>

    {{-- Mission --}}
    <section class="py-16 sm:py-24 lg:py-32">
        <div class="container-site">
            <div class="grid gap-10 lg:mx-[8.333%] lg:grid-cols-12 lg:gap-12">
                <div class="lg:col-span-5" data-fade>
                    <h2 class="display-2 flex flex-col">
                        <span>{{ __('Good architecture begins with a good question.') }}</span>
                        <em class="text-bronze">{{ __('Our mission') }}</em>
                    </h2>
                </div>
                <div class="space-y-6 text-ink/85 lg:col-span-6 lg:col-start-7" data-fade>
                    <p>{{ __('Chareharch organises open architecture competitions that invite architects, designers and students to answer real questions about how we live, work and gather. Every competition starts with a carefully written brief and ends with ideas that can shape real places.') }}</p>
                    <p>{{ __('Alongside the competitions we share projects from our community — built work and bold concepts — so that good ideas travel further.') }}</p>
                    <div class="flex flex-wrap gap-x-10 gap-y-3 pt-2">
                        <a href="{{ route('competitions.index') }}" class="btn btn-dark">{{ __('See open competitions') }}<x-site.arrow /></a>
                        <a href="{{ route('projects.index') }}" class="btn btn-dark">{{ __('View all projects') }}<x-site.arrow /></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How a competition works --}}
    <section class="bg-white py-16 sm:py-24 lg:py-32" aria-labelledby="about-steps">
        <div class="container-site">
            <div class="lg:mx-[8.333%]">
                <h2 id="about-steps" class="display-2 flex max-w-2xl flex-col" data-fade="current">
                    <span>{{ __('How a competition works') }}</span>
                    <em class="text-bronze">{{ __('From brief to results') }}</em>
                </h2>

                <x-site.competition-steps class="mt-12 lg:mt-16" data-fade />
            </div>
        </div>
    </section>

    {{-- Image grid --}}
    @if (count($gridImages) === 3)
        <section class="py-16 sm:py-24" aria-hidden="true">
            <div class="container-site">
                <div class="grid grid-cols-2 gap-2 lg:mx-[8.333%] lg:grid-cols-12 xl:gap-4" data-reveal-parent>
                    <div class="relative col-span-2 aspect-[16/10] overflow-hidden rounded bg-white lg:col-span-7" data-reveal-item>
                        <img src="{{ $gridImages[0] }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                    </div>
                    <div class="grid gap-2 lg:col-span-5 xl:gap-4">
                        <div class="relative aspect-[4/3] overflow-hidden rounded bg-white lg:aspect-auto" data-reveal-item>
                            <img src="{{ $gridImages[1] }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                        </div>
                        <div class="relative aspect-[4/3] overflow-hidden rounded bg-white lg:aspect-auto" data-reveal-item>
                            <img src="{{ $gridImages[2] }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Principles --}}
    <section class="pb-16 sm:pb-24 lg:pb-32" aria-labelledby="about-principles">
        <div class="container-site">
            <div class="lg:mx-[8.333%]">
                <h2 id="about-principles" class="display-2 flex flex-col" data-fade="current">
                    <span>{{ __('What we stand for') }}</span>
                    <em class="text-bronze">{{ __('Our principles') }}</em>
                </h2>
                <div class="mt-12 grid gap-10 md:grid-cols-3 lg:mt-16" data-fade>
                    @foreach ($principles as $principle)
                        <div>
                            <h3 class="display-3">{{ $principle['title'] }}</h3>
                            <p class="mt-3 text-ink/80">{{ $principle['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Call to action --}}
    <section class="pb-16 sm:pb-24 lg:pb-32">
        <div class="container-site">
            <div class="flex flex-wrap items-center justify-between gap-6 rounded bg-white px-8 py-10 lg:mx-[8.333%] lg:px-14" data-fade="current">
                <div>
                    <div class="display-3">{{ __('Have a question about a competition?') }}</div>
                    <p class="mt-1 text-ink/70">{{ __('We are happy to help — send us a message.') }}</p>
                </div>
                <a href="{{ route('contact') }}" class="btn btn-dark">{{ __('Contact us') }}<x-site.arrow /></a>
            </div>
        </div>
    </section>
</x-layouts::site>
