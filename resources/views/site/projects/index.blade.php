<x-layouts::site :title="__('Projects')">
    <section class="pt-36 pb-16 lg:pt-52 lg:pb-24">
        <div class="container-site">
            <div class="lg:mx-[8.333%]">
                <div class="max-w-2xl" data-fade>
                    <h1 class="display-1 flex flex-col">
                        <span>{{ __('Projects') }}</span>
                        <em class="text-bronze">{{ __('Architecture with intent') }}</em>
                    </h1>
                    <p class="mt-6 max-w-xl text-ink/80">{{ __('Houses, places and ideas shared by the Charehco community — from competition-winning concepts to built work.') }}</p>
                </div>

                <div class="mt-14 grid gap-x-6 gap-y-14 md:grid-cols-2 lg:mt-20 xl:gap-x-8" data-reveal-parent>
                    @forelse ($projects as $i => $project)
                        <x-site.project-card :project="$project" class="{{ $i % 2 === 1 ? 'md:mt-28' : '' }}" />
                    @empty
                        <p class="text-ink/60">{{ __('No projects have been published yet.') }}</p>
                    @endforelse
                </div>

                @if ($projects->hasPages())
                    <div class="mt-16">{{ $projects->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</x-layouts::site>
