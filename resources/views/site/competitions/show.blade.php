@php
    $user = auth()->user();
    $status = $competition->status();
@endphp

<x-layouts::site :title="$competition->t('title')" :description="$competition->t('summary')" :overlay-header="true">
    <section class="relative h-[85svh] min-h-[560px] overflow-hidden bg-ink" data-parallax-parent>
        <div class="absolute inset-0" data-parallax-item>
            @if ($competition->coverUrl())
                <img src="{{ $competition->coverUrl() }}" alt="" class="size-full object-cover" fetchpriority="high">
            @endif
        </div>
        <div class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-t from-black/75 via-black/25 to-black/30"></div>
        <div class="absolute inset-x-0 bottom-0 z-10 pb-12 text-white lg:pb-20">
            <div class="container-site">
                <div class="lg:mx-[8.333%]">
                    <span class="pill" data-hero-line>{{ $status->label() }}</span>
                    <h1 class="display-1 mt-4 max-w-4xl" data-hero-line>{{ $competition->t('title') }}</h1>
                    @if ($competition->t('summary'))
                        <p class="mt-4 max-w-2xl text-[17px] leading-8 text-white/85" data-hero-line>{{ $competition->t('summary') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-28">
        <div class="container-site">
            <div class="grid gap-14 lg:mx-[8.333%] lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <div class="prose-site" data-fade="current">{!! $competition->descriptionHtml() !!}</div>

                    @if ($competition->requirements->isNotEmpty())
                        <div class="mt-16" data-fade>
                            <h2 class="display-3">{{ __('What to submit') }}</h2>
                            <ul class="mt-6 divide-y divide-ink/10 border-y border-ink/10">
                                @foreach ($competition->requirements as $requirement)
                                    <li class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 py-5">
                                        <div>
                                            <div class="font-normal">
                                                {{ $requirement->t('label') }}
                                                @unless ($requirement->is_required)
                                                    <span class="text-[14px] text-ink/55">— {{ __('optional') }}</span>
                                                @endunless
                                            </div>
                                            @if ($requirement->t('description'))
                                                <div class="text-[15px] leading-7 text-ink/70">{{ $requirement->t('description') }}</div>
                                            @endif
                                        </div>
                                        <div class="text-[13px] tracking-wide text-ink/55 uppercase">
                                            {{ strtoupper(implode(', ', $requirement->extensions())) }} · {{ __('max :size MB', ['size' => $requirement->max_size_mb]) }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <aside class="lg:col-span-4 lg:col-start-9">
                    <div class="rounded bg-white p-8 lg:sticky lg:top-32" data-fade="current">
                        <dl class="space-y-5 text-[15px] leading-6">
                            <div><dt class="text-ink/55">{{ __('Entries open') }}</dt><dd>{{ $competition->starts_at->localDate() }}</dd></div>
                            <div><dt class="text-ink/55">{{ __('Submission deadline') }}</dt><dd>{{ $competition->deadline_at->localDate('datetime') }}</dd></div>
                            @if ($competition->results_at)
                                <div><dt class="text-ink/55">{{ __('Results') }}</dt><dd>{{ $competition->results_at->localDate() }}</dd></div>
                            @endif
                            @if ($competition->t('prize'))
                                <div><dt class="text-ink/55">{{ __('Prize') }}</dt><dd>{{ $competition->t('prize') }}</dd></div>
                            @endif
                            @if ($competition->t('location'))
                                <div><dt class="text-ink/55">{{ __('Location') }}</dt><dd>{{ $competition->t('location') }}</dd></div>
                            @endif
                            @if ($competition->anonymous_judging)
                                <div><dt class="text-ink/55">{{ __('Judging') }}</dt><dd>{{ __('Anonymous') }}</dd></div>
                            @endif
                        </dl>

                        <div class="mt-8 border-t border-ink/10 pt-8">
                            @if (! $competition->isAcceptingEntries())
                                <p class="text-[15px] leading-7 text-ink/70">
                                    {{ $status === App\Enums\CompetitionStatus::Upcoming
                                        ? __('Entries open on :date.', ['date' => $competition->starts_at->localDate()])
                                        : __('This competition no longer accepts entries.') }}
                                </p>
                            @elseif (! $user)
                                <p class="text-[15px] leading-7 text-ink/70">{{ __('Create a free account to attend and upload your documents.') }}</p>
                                <div class="mt-5 flex flex-col gap-3">
                                    <a href="{{ route('register') }}" class="btn btn-dark">{{ __('Register to attend') }}<x-site.arrow /></a>
                                    <a href="{{ route('login') }}" class="btn btn-dark">{{ __('I already have an account') }}<x-site.arrow /></a>
                                </div>
                            @elseif ($user->can('enter', $competition))
                                <a href="{{ route('entries.edit', $competition) }}" class="btn btn-dark">
                                    {{ $entry ? ($entry->isSubmitted() ? __('View or update my entry') : __('Continue my entry')) : __('Attend this competition') }}
                                    <x-site.arrow />
                                </a>
                                @if ($entry?->isSubmitted())
                                    <p class="mt-3 text-[14px] text-bronze">{{ __('Your entry was submitted on :date.', ['date' => $entry->submitted_at->localDate()]) }}</p>
                                @endif
                            @elseif ($user->isJury())
                                <p class="text-[15px] leading-7 text-ink/70">{{ __('Jury members cannot enter competitions.') }}</p>
                                @can('review', $competition)
                                    <a href="{{ route('review.competition', $competition->id) }}" class="btn btn-dark mt-4">{{ __('Review entries') }}<x-site.arrow /></a>
                                @endcan
                            @elseif ($user->isAdmin())
                                <div class="flex flex-col gap-3">
                                    <a href="{{ route('admin.competitions.edit', $competition->id) }}" class="btn btn-dark">{{ __('Edit competition') }}<x-site.arrow /></a>
                                    <a href="{{ route('review.competition', $competition->id) }}" class="btn btn-dark">{{ __('View entries') }}<x-site.arrow /></a>
                                </div>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts::site>
