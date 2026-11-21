<?php

use App\Models\Project;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Projects')] class extends Component {
    use WithPagination;

    #[Computed]
    public function projects()
    {
        $user = Auth::user();

        return Project::query()
            ->with('author')
            ->unless($user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')
            ->paginate(12);
    }

    public function delete(int $projectId): void
    {
        $project = Project::with('images')->findOrFail($projectId);
        $this->authorize('delete', $project);

        Media::delete($project->cover_image);
        $project->images->each(fn ($image) => Media::delete($image->path));
        $project->delete();

        Flux::toast(variant: 'success', text: __('Project deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Projects') }}</flux:heading>
            <flux:subheading>
                {{ auth()->user()->isAdmin() ? __('All projects shared on the website.') : __('Projects you have shared on the website.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('manage.projects.create')" wire:navigate data-test="create-project">
            {{ __('New project') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->projects">
        <flux:table.columns>
            <flux:table.column>{{ __('Project') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Author') }}</flux:table.column>
            <flux:table.column>{{ __('Updated') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->projects as $project)
                <flux:table.row :key="$project->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-14 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-white/10">
                                @if ($project->coverUrl())
                                    <img src="{{ $project->coverUrl() }}" alt="" class="size-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-zinc-800 dark:text-white">{{ $project->t('title') }}</div>
                                <div class="text-xs text-zinc-500">{{ collect([$project->t('location'), $project->year])->filter()->join(' · ') }}</div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-1">
                            @if ($project->is_published)
                                <flux:badge size="sm" color="green">{{ __('Published') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Draft') }}</flux:badge>
                            @endif
                            @if ($project->show_on_home)
                                <flux:badge size="sm" color="amber">{{ __('On home page') }}</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $project->author?->name }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $project->updated_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @if ($project->is_published)
                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('projects.show', $project)" target="_blank" :aria-label="__('View')" />
                            @endif
                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('manage.projects.edit', $project->id)" wire:navigate :aria-label="__('Edit')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $project->id }})" wire:confirm="{{ __('Delete this project and its images?') }}" :aria-label="__('Delete')" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-10 text-center text-zinc-500">{{ __('No projects yet. Share your first one.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
