<x-layouts::site :title="__('Competitions')">
    <section class="pt-36 pb-16 lg:pt-52 lg:pb-24">
        <div class="container-site">
            <div class="lg:mx-[8.333%]">
                <div class="max-w-2xl" data-fade>
                    <h1 class="display-1 flex flex-col">
                        <span>{{ __('Competitions') }}</span>
                        <em class="text-bronze">{{ __('Open calls for ideas') }}</em>
                    </h1>
                    <p class="mt-6 max-w-xl text-ink/80">{{ __('Register, upload your documents before the deadline, and an independent jury will review your entry.') }}</p>
                </div>

                <div class="mt-14 space-y-16 lg:mt-20 lg:space-y-24">
                    @forelse ($current as $competition)
                        @include('site.competitions._card', ['competition' => $competition])
                    @empty
                        <p class="text-ink/60">{{ __('There is no open competition at the moment. Check back soon.') }}</p>
                    @endforelse
                </div>

                @if ($past->isNotEmpty())
                    <div class="mt-24 border-t border-ink/10 pt-16 lg:mt-32">
                        <h2 class="display-2" data-fade="current">{{ __('Past competitions') }}</h2>
                        <div class="mt-12 space-y-16">
                            @foreach ($past as $competition)
                                @include('site.competitions._card', ['competition' => $competition])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts::site>
