<x-layouts::site :title="$project->t('title')" :description="$project->t('summary')" :overlay-header="true">
    {{-- Hero image with parallax --}}
    <section class="relative h-[85svh] min-h-[520px] overflow-hidden bg-ink" data-parallax-parent>
        <div class="absolute inset-0" data-parallax-item>
            @if ($project->coverUrl())
                <img src="{{ $project->coverUrl() }}" alt="{{ $project->t('title') }}" class="size-full object-cover" fetchpriority="high">
            @endif
        </div>
        <div class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-t from-black/70 via-black/20 to-black/30"></div>
        <div class="absolute inset-x-0 bottom-0 z-10 pb-12 text-white lg:pb-20">
            <div class="container-site">
                <div class="lg:mx-[8.333%]">
                    @if ($project->t('category'))
                        <span class="pill" data-hero-line>{{ $project->t('category') }}</span>
                    @endif
                    <h1 class="display-1 mt-4 max-w-4xl" data-hero-line>{{ $project->t('title') }}</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-28">
        <div class="container-site">
            <div class="grid gap-12 lg:mx-[8.333%] lg:grid-cols-12">
                <aside class="lg:col-span-3" data-fade>
                    <dl class="space-y-5 text-[15px] leading-6">
                        @if ($project->t('location'))
                            <div><dt class="text-ink/55">{{ __('Location') }}</dt><dd>{{ $project->t('location') }}</dd></div>
                        @endif
                        @if ($project->year)
                            <div><dt class="text-ink/55">{{ __('Year') }}</dt><dd>{{ $project->year }}</dd></div>
                        @endif
                        @if ($project->t('category'))
                            <div><dt class="text-ink/55">{{ __('Type') }}</dt><dd>{{ $project->t('category') }}</dd></div>
                        @endif
                        @if ($project->author)
                            <div><dt class="text-ink/55">{{ __('Shared by') }}</dt><dd>{{ $project->author->name }}</dd></div>
                        @endif
                    </dl>
                </aside>

                <div class="lg:col-span-8 lg:col-start-5">
                    @if ($project->t('summary'))
                        <p class="display-3 text-bronze italic" data-fade="current">{{ $project->t('summary') }}</p>
                    @endif
                    <div class="prose-site mt-8" data-fade="current">{!! $project->bodyHtml() !!}</div>
                </div>
            </div>
        </div>
    </section>

    @if ($project->images->isNotEmpty())
        <section class="pb-16 lg:pb-28" aria-label="{{ __('Gallery') }}">
            <div class="container-site">
                <div class="grid grid-cols-2 gap-2 lg:mx-[8.333%] lg:grid-cols-12 xl:gap-4" data-reveal-parent>
                    @foreach ($project->images as $i => $image)
                        @php
                            $span = match ($i % 3) {
                                0 => 'col-span-2 lg:col-span-7 aspect-[16/10]',
                                1 => 'lg:col-span-5 aspect-[4/5] lg:aspect-auto',
                                default => 'lg:col-span-12 aspect-[16/7]',
                            };
                        @endphp
                        <figure class="relative overflow-hidden rounded bg-white {{ $span }}" data-reveal-item>
                            <img src="{{ $image->url() }}" alt="{{ $image->caption ?? $project->t('title') }}" class="absolute inset-0 size-full object-cover transition-transform duration-700 hover:scale-[1.03]" loading="lazy">
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($more->isNotEmpty())
        <section class="bg-white py-16 lg:py-28">
            <div class="container-site">
                <div class="lg:mx-[8.333%]">
                    <div class="flex flex-wrap items-end justify-between gap-6" data-fade>
                        <h2 class="display-2 flex flex-col"><span>{{ __('More projects') }}</span><em class="text-bronze">{{ __('Keep exploring') }}</em></h2>
                        <a href="{{ route('projects.index') }}" class="btn btn-dark">{{ __('View all projects') }}<x-site.arrow /></a>
                    </div>
                    <div class="mt-12 grid gap-10 md:grid-cols-2 md:gap-6" data-reveal-parent>
                        @foreach ($more as $item)
                            <x-site.project-card :project="$item" />
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
</x-layouts::site>
