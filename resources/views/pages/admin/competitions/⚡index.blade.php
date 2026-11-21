<?php

use App\Models\Competition;
use App\Support\Media;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Competitions')] class extends Component {
    public function mount(): void
    {
        $this->authorize('admin');
    }

    #[Computed]
    public function competitions()
    {
        return Competition::query()
            ->withCount([
                'entries',
                'entries as submitted_entries_count' => fn ($q) => $q->whereNotNull('submitted_at'),
                'jury',
            ])
            ->orderByDesc('deadline_at')
            ->get();
    }

    public function delete(int $competitionId): void
    {
        $competition = Competition::findOrFail($competitionId);
        $this->authorize('delete', $competition);

        Media::delete($competition->cover_image);
        $competition->entries()->with('documents')->get()->each(function ($entry) {
            $entry->documents->each->delete();
        });
        $competition->delete();

        Flux::toast(variant: 'success', text: __('Competition deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Competitions') }}</flux:heading>
            <flux:subheading>{{ __('Create competitions, define the documents participants must upload, and assign the jury.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.competitions.create')" wire:navigate data-test="create-competition">
            {{ __('New competition') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Competition') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Deadline') }}</flux:table.column>
            <flux:table.column>{{ __('Entries') }}</flux:table.column>
            <flux:table.column>{{ __('Jury') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->competitions as $competition)
                <flux:table.row :key="$competition->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-14 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-white/10">
                                @if ($competition->coverUrl())
                                    <img src="{{ $competition->coverUrl() }}" alt="" class="size-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-zinc-800 dark:text-white">{{ $competition->t('title') }}</div>
                                <div class="flex gap-1 pt-0.5">
                                    @unless ($competition->is_published)
                                        <flux:badge size="sm" color="zinc">{{ __('Unpublished') }}</flux:badge>
                                    @endunless
                                    @if ($competition->show_on_home)
                                        <flux:badge size="sm" color="amber">{{ __('On home page') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$competition->status()->color()">{{ $competition->status()->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $competition->deadline_at->localDate('short_time') }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $competition->submitted_entries_count }} / {{ $competition->entries_count }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $competition->jury_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="inbox-stack" :href="route('review.competition', $competition->id)" wire:navigate :aria-label="__('Entries')" />
                            <flux:button size="sm" variant="ghost" icon="eye" :href="route('competitions.show', $competition)" target="_blank" :aria-label="__('View')" />
                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.competitions.edit', $competition->id)" wire:navigate :aria-label="__('Edit')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $competition->id }})" wire:confirm="{{ __('Delete this competition, its entries and all uploaded documents? This cannot be undone.') }}" :aria-label="__('Delete')" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-10 text-center text-zinc-500">{{ __('No competitions yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
