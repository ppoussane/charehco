<?php

use App\Support\HomePage;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Home page')] class extends Component {
    use WithFileUploads;

    /** @var array<string, array<int, string>> Draft image paths per slot. */
    public array $content = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $heroUploads = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $spotlightUploads = [];

    /** Slot the image library modal adds to. */
    public string $librarySlot = 'hero';

    public function mount(): void
    {
        $this->authorize('admin');
        $this->content = HomePage::draft();
    }

    #[Computed]
    public function hasChanges(): bool
    {
        return HomePage::hasUnpublishedChanges();
    }

    /**
     * Images already on the site that can be reused.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function library(): array
    {
        $disk = Storage::disk('public');

        return collect(['home', 'demo', 'projects', 'competitions'])
            ->flatMap(fn ($dir) => $disk->files($dir))
            ->filter(fn ($path) => Str::endsWith(strtolower($path), ['.jpg', '.jpeg', '.png', '.webp']))
            ->sortByDesc(fn ($path) => $disk->lastModified($path))
            ->take(60)
            ->values()
            ->all();
    }

    public function validationAttributes(): array
    {
        return [
            'heroUploads.*' => __('Hero slideshow'),
            'spotlightUploads.*' => __('Competition spotlight'),
        ];
    }

    public function updatedHeroUploads(): void
    {
        $this->storeUploads('hero', 'heroUploads');
    }

    public function updatedSpotlightUploads(): void
    {
        $this->storeUploads('spotlight', 'spotlightUploads');
    }

    public function openLibrary(string $slot): void
    {
        $this->assertSlot($slot);
        $this->librarySlot = $slot;
        Flux::modal('image-library')->show();
    }

    public function addFromLibrary(string $path): void
    {
        $this->authorize('admin');
        abort_unless(in_array($path, $this->library, true), 422);

        if ($this->isFull($this->librarySlot)) {
            Flux::toast(variant: 'warning', text: __('This section already has the maximum number of images.'));

            return;
        }

        $this->content[$this->librarySlot][] = $path;
        $this->persist();
        Flux::modal('image-library')->close();
    }

    public function remove(string $slot, int $index): void
    {
        $this->authorize('admin');
        $this->assertSlot($slot);

        if (count($this->content[$slot]) <= HomePage::SLOTS[$slot]['min']) {
            Flux::toast(variant: 'warning', text: __('Keep at least :min image here.', ['min' => HomePage::SLOTS[$slot]['min']]));

            return;
        }

        unset($this->content[$slot][$index]);
        $this->content[$slot] = array_values($this->content[$slot]);
        $this->persist();
    }

    public function move(string $slot, int $index, int $direction): void
    {
        $this->authorize('admin');
        $this->assertSlot($slot);

        $target = $index + $direction;
        if (! isset($this->content[$slot][$index], $this->content[$slot][$target])) {
            return;
        }

        [$this->content[$slot][$index], $this->content[$slot][$target]] = [$this->content[$slot][$target], $this->content[$slot][$index]];
        $this->persist();
    }

    public function publish(): void
    {
        $this->authorize('admin');

        HomePage::publish();
        $this->content = HomePage::published();
        unset($this->hasChanges);

        Flux::modal('confirm-publish')->close();
        Flux::toast(variant: 'success', heading: __('Home page updated'), text: __('Visitors now see the new images.'));
    }

    public function discard(): void
    {
        $this->authorize('admin');

        HomePage::discardDraft();
        $this->content = HomePage::published();
        unset($this->hasChanges);

        $this->dispatch('home-preview-updated');
        Flux::toast(text: __('Draft discarded. The preview shows the live home page again.'));
    }

    private function storeUploads(string $slot, string $property): void
    {
        $this->authorize('admin');

        $this->validate([
            $property => ['array'],
            $property.'.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        foreach ($this->{$property} as $file) {
            if ($this->isFull($slot)) {
                Flux::toast(variant: 'warning', text: __('Only :max images fit in this section; extra files were skipped.', ['max' => HomePage::SLOTS[$slot]['max']]));
                break;
            }

            $this->content[$slot][] = $file->store('home', 'public');
        }

        $this->{$property} = [];
        $this->persist();
    }

    private function persist(): void
    {
        HomePage::saveDraft($this->content);
        $this->content = HomePage::draft();
        unset($this->hasChanges);

        $this->dispatch('home-preview-updated');
    }

    private function isFull(string $slot): bool
    {
        return count($this->content[$slot] ?? []) >= HomePage::SLOTS[$slot]['max'];
    }

    private function assertSlot(string $slot): void
    {
        abort_unless(array_key_exists($slot, HomePage::SLOTS), 422);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Home page') }}</flux:heading>
            <flux:subheading>{{ __('Change the images, check the preview, then publish to apply them to the live home page.') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($this->hasChanges)
                <flux:badge color="amber" icon="sparkles">{{ __('Unpublished changes') }}</flux:badge>
                <flux:button variant="ghost" wire:click="discard" wire:confirm="{{ __('Discard your changes and go back to the live images?') }}">{{ __('Discard') }}</flux:button>
            @else
                <flux:badge color="green" icon="check-circle">{{ __('Preview matches the live page') }}</flux:badge>
            @endif
            <flux:modal.trigger name="confirm-publish">
                <flux:button variant="primary" icon="rocket-launch" :disabled="! $this->hasChanges" data-test="open-publish">{{ __('Publish') }}</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[22rem_1fr]">
        {{-- Image slots --}}
        <div class="space-y-6">
            @foreach (App\Support\HomePage::SLOTS as $slot => $config)
                <flux:card class="space-y-4" wire:key="slot-{{ $slot }}">
                    <div>
                        <flux:heading>{{ __($config['label']) }}</flux:heading>
                        <flux:text size="sm">{{ __($config['help']) }}</flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($content[$slot] as $i => $path)
                            <div class="group relative aspect-[16/10] overflow-hidden rounded-lg bg-zinc-100 dark:bg-white/10" wire:key="{{ $slot }}-{{ $i }}-{{ md5($path) }}">
                                <img src="{{ App\Support\Media::url($path) }}" alt="" class="size-full object-cover">
                                <span class="absolute start-1.5 top-1.5 rounded bg-black/60 px-1.5 text-xs leading-5 text-white">{{ $i + 1 }}</span>
                                <div class="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-gradient-to-t from-black/60 to-transparent p-1.5 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                                    <button type="button" wire:click="move('{{ $slot }}', {{ $i }}, -1)" @disabled($i === 0) class="rounded bg-white/90 p-1 text-zinc-800 disabled:opacity-40" aria-label="{{ __('Move earlier') }}"><flux:icon name="arrow-up" variant="micro" class="-rotate-90 rtl:rotate-90" /></button>
                                    <button type="button" wire:click="move('{{ $slot }}', {{ $i }}, 1)" @disabled($loop->last) class="rounded bg-white/90 p-1 text-zinc-800 disabled:opacity-40" aria-label="{{ __('Move later') }}"><flux:icon name="arrow-down" variant="micro" class="-rotate-90 rtl:rotate-90" /></button>
                                    <button type="button" wire:click="remove('{{ $slot }}', {{ $i }})" class="rounded bg-white/90 p-1 text-red-600" aria-label="{{ __('Remove image') }}" data-test="remove-{{ $slot }}-{{ $i }}"><flux:icon name="trash" variant="micro" /></button>
                                </div>
                            </div>
                        @endforeach

                        @if (count($content[$slot]) < $config['max'])
                            <label class="flex aspect-[16/10] cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-zinc-300 text-sm text-zinc-500 transition hover:border-zinc-400 hover:text-zinc-700 dark:border-white/20 dark:hover:text-white">
                                <flux:icon name="cloud-arrow-up" class="size-6" />
                                <span wire:loading.remove wire:target="{{ $slot }}Uploads">{{ __('Upload images') }}</span>
                                <span wire:loading wire:target="{{ $slot }}Uploads">{{ __('Uploading…') }}</span>
                                <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" multiple wire:model="{{ $slot }}Uploads" data-test="upload-{{ $slot }}">
                            </label>
                        @endif
                    </div>

                    <flux:error name="{{ $slot }}Uploads" />
                    <flux:error name="{{ $slot }}Uploads.*" />

                    <div class="flex items-center justify-between">
                        <flux:text size="sm">{{ __(':count of :max images', ['count' => count($content[$slot]), 'max' => $config['max']]) }}</flux:text>
                        @if (count($content[$slot]) < $config['max'])
                            <flux:button size="sm" variant="ghost" icon="photo" wire:click="openLibrary('{{ $slot }}')">{{ __('Choose existing') }}</flux:button>
                        @endif
                    </div>
                </flux:card>
            @endforeach

            <flux:text size="sm" class="px-1">
                {{ __('The two projects and the competition shown on the home page are chosen with “Show on home page” on each project and competition.') }}
            </flux:text>
        </div>

        {{-- Live preview of the draft --}}
        <div wire:ignore
            x-data="{
                device: 'desktop',
                version: 0,
                scale: 1,
                get frameWidth() { return this.device === 'desktop' ? 1440 : 390 },
                get frameHeight() { return this.device === 'desktop' ? 900 : 844 },
                get src() { return '{{ route('admin.home-page.preview') }}?v=' + this.version },
                fit() {
                    const width = this.$refs.stage.clientWidth;
                    this.scale = Math.min(1, width / this.frameWidth);
                },
                reload() { this.version++ },
            }"
            x-init="fit(); new ResizeObserver(() => fit()).observe($refs.stage); $watch('device', () => $nextTick(() => fit()))"
            x-on:home-preview-updated.window="reload()"
            class="space-y-3 xl:sticky xl:top-6 xl:self-start">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading>{{ __('Preview') }}</flux:heading>
                <div class="flex items-center gap-1">
                    <flux:button size="sm" icon="computer-desktop" x-on:click="device = 'desktop'" x-bind:class="device === 'desktop' && '!bg-zinc-800/10 dark:!bg-white/15'" :aria-label="__('Desktop')" />
                    <flux:button size="sm" icon="device-phone-mobile" x-on:click="device = 'mobile'" x-bind:class="device === 'mobile' && '!bg-zinc-800/10 dark:!bg-white/15'" :aria-label="__('Mobile')" />
                    <flux:button size="sm" icon="arrow-path" x-on:click="reload()" :aria-label="__('Reload preview')" />
                    <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('admin.home-page.preview')" target="_blank">{{ __('Open full size') }}</flux:button>
                </div>
            </div>

            <div x-ref="stage" class="relative w-full overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-white/10 dark:bg-zinc-900"
                x-bind:style="`height: ${frameHeight * scale}px`">
                <iframe x-bind:src="src" title="{{ __('Home page preview') }}"
                    class="absolute top-0 border-0 bg-white"
                    x-bind:style="`width: ${frameWidth}px; height: ${frameHeight}px; transform: scale(${scale}); transform-origin: 0 0; left: ${Math.max(0, ($refs.stage.clientWidth - frameWidth * scale) / 2)}px`"></iframe>
            </div>
            <flux:text size="sm">{{ __('Scroll inside the preview to see every section and its animations.') }}</flux:text>
        </div>
    </div>

    {{-- Confirm publish --}}
    <flux:modal name="confirm-publish" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Apply the new home page?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Visitors will immediately see the images from the preview. You can change them again at any time.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="publish" data-test="confirm-publish">{{ __('Publish') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Image library --}}
    <flux:modal name="image-library" class="w-full md:max-w-3xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Choose an image') }}</flux:heading>
                <flux:text>{{ __('Images already uploaded to the site.') }}</flux:text>
            </div>
            <div class="grid max-h-[60vh] grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-3">
                @foreach ($this->library as $path)
                    <button type="button" wire:click="addFromLibrary(@js($path))" wire:key="lib-{{ md5($path) }}"
                        class="group relative aspect-[16/10] overflow-hidden rounded-lg bg-zinc-100 ring-offset-2 hover:ring-2 hover:ring-zinc-800 focus:ring-2 focus:ring-zinc-800 focus:outline-none dark:hover:ring-white">
                        <img src="{{ App\Support\Media::url($path) }}" alt="{{ basename($path) }}" class="size-full object-cover" loading="lazy">
                    </button>
                @endforeach
            </div>
        </div>
    </flux:modal>
</div>
