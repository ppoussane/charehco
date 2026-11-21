<?php

use App\Models\Project;
use App\Models\ProjectImage;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Project')] class extends Component {
    use WithFileUploads;

    public ?Project $project = null;

    public string $title = '';
    public string $summary = '';
    public string $body = '';
    public string $location = '';
    public ?int $year = null;
    public string $category = '';

    /** Optional Farsi versions, shown on FA pages. */
    public string $title_fa = '';
    public string $summary_fa = '';
    public string $body_fa = '';
    public string $location_fa = '';
    public string $category_fa = '';

    public bool $is_published = false;
    public bool $show_on_home = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $cover = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $gallery = [];

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->authorize('update', $project);
            $this->project = $project;
            $this->fill($project->only(['title', 'is_published', 'show_on_home', 'year']));
            $this->summary = (string) $project->summary;
            $this->body = (string) $project->body;
            $this->location = (string) $project->location;
            $this->category = (string) $project->category;

            foreach (['title_fa', 'summary_fa', 'body_fa', 'location_fa', 'category_fa'] as $field) {
                $this->{$field} = (string) $project->{$field};
            }
        } else {
            $this->authorize('create', Project::class);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'category' => ['nullable', 'string', 'max:100'],
            'title_fa' => ['nullable', 'string', 'max:255'],
            'summary_fa' => ['nullable', 'string', 'max:500'],
            'body_fa' => ['nullable', 'string', 'max:20000'],
            'location_fa' => ['nullable', 'string', 'max:255'],
            'category_fa' => ['nullable', 'string', 'max:100'],
            'is_published' => ['boolean'],
            'show_on_home' => ['boolean'],
            'cover' => [$this->project?->cover_image ? 'nullable' : 'required', 'image', 'max:15360'],
            'gallery' => ['array', 'max:12'],
            'gallery.*' => ['image', 'max:15360'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => __('Title'), 'title_fa' => __('Title'),
            'summary' => __('Summary'), 'summary_fa' => __('Summary'),
            'body' => __('Description'), 'body_fa' => __('Description'),
            'location' => __('Location'), 'location_fa' => __('Location'),
            'category' => __('Category'), 'category_fa' => __('Category'),
            'year' => __('Year'),
            'cover' => __('Cover image'),
            'gallery' => __('Gallery'), 'gallery.*' => __('Gallery'),
        ];
    }

    public function save(): void
    {
        $project = $this->project ?? new Project;
        $project->exists ? $this->authorize('update', $project) : $this->authorize('create', Project::class);

        $this->validate();

        $project->fill([
            'title' => $this->title,
            'summary' => $this->summary ?: null,
            'body' => $this->body ?: null,
            'location' => $this->location ?: null,
            'year' => $this->year,
            'category' => $this->category ?: null,
            'title_fa' => $this->title_fa ?: null,
            'summary_fa' => $this->summary_fa ?: null,
            'body_fa' => $this->body_fa ?: null,
            'location_fa' => $this->location_fa ?: null,
            'category_fa' => $this->category_fa ?: null,
            'is_published' => $this->is_published,
        ]);

        // Only the admin decides what appears on the home page.
        if (Auth::user()->can('feature', Project::class)) {
            $project->show_on_home = $this->show_on_home;
        }

        $isNew = ! $project->exists;

        if ($isNew) {
            $project->user_id = Auth::id();
        }

        if ($this->cover) {
            Media::delete($project->cover_image);
            $project->cover_image = $this->cover->store('projects', 'public');
        }

        $project->save();

        $sort = (int) $project->images()->max('sort');
        foreach ($this->gallery as $file) {
            $project->images()->create(['path' => $file->store('projects', 'public'), 'sort' => ++$sort]);
        }

        $this->reset('cover', 'gallery');
        $this->project = $project->fresh();

        Flux::toast(variant: 'success', text: __('Project saved.'));

        if ($isNew) {
            $this->redirectRoute('manage.projects.edit', $project->id, navigate: true);
        }
    }

    public function removeImage(int $imageId): void
    {
        abort_unless($this->project, 404);
        $this->authorize('update', $this->project);

        $image = ProjectImage::where('project_id', $this->project->id)->findOrFail($imageId);
        Media::delete($image->path);
        $image->delete();

        $this->project->refresh();
    }

    public function removeUpload(int $index): void
    {
        unset($this->gallery[$index]);
        $this->gallery = array_values($this->gallery);
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $project ? __('Edit project') : __('New project') }}</flux:heading>
            <flux:subheading>{{ __('Share a project on the website. Text supports Markdown (## headings, **bold**, lists).') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            @if ($project?->is_published)
                <flux:button icon="arrow-top-right-on-square" :href="route('projects.show', $project)" target="_blank">{{ __('View on site') }}</flux:button>
            @endif
            <flux:button variant="ghost" :href="route('manage.projects.index')" wire:navigate>{{ __('Back to projects') }}</flux:button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('English version') }}</flux:heading>
                <flux:text size="sm">{{ __('Required. Shown on English pages, and on Farsi pages when no Farsi text is given.') }}</flux:text>
            </div>
            <flux:input wire:model="title" :label="__('Title')" dir="ltr" required data-test="project-title" />
            <flux:textarea wire:model="summary" :label="__('Summary')" :description="__('One or two sentences shown on cards and the home page.')" dir="ltr" rows="2" />

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="location" :label="__('Location')" dir="ltr" />
                <flux:input wire:model="year" type="number" :label="__('Year')" />
                <flux:input wire:model="category" :label="__('Category')" :placeholder="__('e.g. Residential')" dir="ltr" />
            </div>

            <flux:textarea wire:model="body" :label="__('Description')" dir="ltr" rows="10" />
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Farsi version') }} <span lang="fa">(فارسی)</span></flux:heading>
                <flux:text size="sm">{{ __('Optional. Shown when visitors choose FA. Empty fields fall back to English.') }}</flux:text>
            </div>
            <flux:input wire:model="title_fa" :label="__('Title')" dir="rtl" lang="fa" data-test="project-title-fa" />
            <flux:textarea wire:model="summary_fa" :label="__('Summary')" dir="rtl" lang="fa" rows="2" />
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="location_fa" :label="__('Location')" dir="rtl" lang="fa" />
                <flux:input wire:model="category_fa" :label="__('Category')" dir="rtl" lang="fa" />
            </div>
            <flux:textarea wire:model="body_fa" :label="__('Description')" dir="rtl" lang="fa" rows="10" />
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Cover image') }}</flux:heading>
                <flux:text size="sm">{{ __('Landscape images of at least 1600px wide look best. Max 15 MB.') }}</flux:text>
            </div>

            <div class="flex flex-wrap items-start gap-6">
                <div class="aspect-[4/3] w-64 overflow-hidden rounded-lg bg-zinc-100 dark:bg-white/10">
                    @if ($cover && method_exists($cover, 'isPreviewable') && $cover->isPreviewable())
                        <img src="{{ $cover->temporaryUrl() }}" alt="" class="size-full object-cover">
                    @elseif ($project?->coverUrl())
                        <img src="{{ $project->coverUrl() }}" alt="" class="size-full object-cover">
                    @else
                        <div class="flex size-full items-center justify-center text-zinc-400"><flux:icon name="photo" class="size-8" /></div>
                    @endif
                </div>
                <div class="space-y-2">
                    <input type="file" wire:model="cover" accept="image/*" class="block text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:text-white dark:file:bg-white dark:file:text-zinc-800" data-test="project-cover">
                    <div wire:loading wire:target="cover" class="text-sm text-zinc-500">{{ __('Uploading…') }}</div>
                    <flux:error name="cover" />
                </div>
            </div>
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Gallery') }}</flux:heading>
                <flux:text size="sm">{{ __('Additional images shown on the project page.') }}</flux:text>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($project?->images ?? [] as $image)
                    <div class="group relative aspect-[4/3] overflow-hidden rounded-lg bg-zinc-100" wire:key="img-{{ $image->id }}">
                        <img src="{{ $image->url() }}" alt="" class="size-full object-cover">
                        <button type="button" wire:click="removeImage({{ $image->id }})" wire:confirm="{{ __('Remove this image?') }}"
                            class="absolute end-1 top-1 rounded bg-black/60 p-1 text-white opacity-0 transition group-hover:opacity-100 focus:opacity-100"
                            aria-label="{{ __('Remove image') }}">
                            <flux:icon name="x-mark" variant="micro" />
                        </button>
                    </div>
                @endforeach
                @foreach ($gallery as $i => $file)
                    <div class="group relative aspect-[4/3] overflow-hidden rounded-lg bg-zinc-100 ring-2 ring-amber-500/60" wire:key="upload-{{ $i }}">
                        @if ($file->isPreviewable())
                            <img src="{{ $file->temporaryUrl() }}" alt="" class="size-full object-cover">
                        @endif
                        <button type="button" wire:click="removeUpload({{ $i }})" class="absolute end-1 top-1 rounded bg-black/60 p-1 text-white" aria-label="{{ __('Remove upload') }}">
                            <flux:icon name="x-mark" variant="micro" />
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="space-y-2">
                <input type="file" wire:model="gallery" accept="image/*" multiple class="block text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:text-white dark:file:bg-white dark:file:text-zinc-800">
                <div wire:loading wire:target="gallery" class="text-sm text-zinc-500">{{ __('Uploading…') }}</div>
                <flux:error name="gallery" />
                <flux:error name="gallery.*" />
                @if ($gallery)
                    <flux:text size="sm">{{ __('New images are added when you save.') }}</flux:text>
                @endif
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:switch wire:model="is_published" :label="__('Published')" :description="__('Visible to everyone on the projects page.')" align="left" />
            @can('feature', App\Models\Project::class)
                <flux:switch wire:model="show_on_home" :label="__('Show on home page')" :description="__('The home page shows the two most recent published projects with this enabled.')" align="left" />
            @endcan
        </flux:card>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary" data-test="save-project">{{ __('Save project') }}</flux:button>
            <div wire:loading wire:target="save" class="text-sm text-zinc-500">{{ __('Saving…') }}</div>
        </div>
    </form>
</div>
