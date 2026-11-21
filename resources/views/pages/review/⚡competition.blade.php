<?php

use App\Models\Competition;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Entries')] class extends Component {
    public Competition $competition;

    /** Admin only: include entries that were never submitted. */
    #[Url(except: false)]
    public bool $includeDrafts = false;

    public function mount(Competition $competition): void
    {
        $this->authorize('review', $competition);
        $this->competition = $competition;
    }

    #[Computed]
    public function entries()
    {
        $isAdmin = Auth::user()->isAdmin();

        return $this->competition->entries()
            ->with('user', 'competition')
            ->withCount('documents')
            ->when(! ($isAdmin && $this->includeDrafts), fn ($q) => $q->whereNotNull('submitted_at'))
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:text size="sm"><a href="{{ route('review.index') }}" wire:navigate class="hover:underline">{{ __('Jury review') }}</a></flux:text>
            <flux:heading size="xl" level="1">{{ $competition->t('title') }}</flux:heading>
            <flux:subheading>
                {{ $competition->status()->label() }} · {{ __('Deadline :date', ['date' => $competition->deadline_at->localDate('short_time')]) }}
                @if ($competition->anonymous_judging)
                    · {{ __('Anonymous judging') }}
                @endif
            </flux:subheading>
        </div>
        @can('admin')
            <div class="flex items-center gap-4">
                <flux:switch wire:model.live="includeDrafts" :label="__('Show drafts')" />
                <flux:button size="sm" icon="pencil-square" :href="route('admin.competitions.edit', $competition->id)" wire:navigate>{{ __('Edit competition') }}</flux:button>
            </div>
        @endcan
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Entry') }}</flux:table.column>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Documents') }}</flux:table.column>
            <flux:table.column>{{ __('Submitted') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->entries as $entry)
                <flux:table.row :key="$entry->id">
                    <flux:table.cell class="font-medium">{{ $entry->displayNameFor(auth()->user()) }}</flux:table.cell>
                    <flux:table.cell class="max-w-xs truncate">{{ $entry->title }}</flux:table.cell>
                    <flux:table.cell>{{ $entry->documents_count }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($entry->isSubmitted())
                            <span class="text-zinc-500">{{ $entry->submitted_at->localDate('short_time') }}</span>
                        @else
                            <flux:badge size="sm" color="amber">{{ __('Draft') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" :href="route('review.entries.show', $entry)" wire:navigate>{{ __('Open') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-10 text-center text-zinc-500">{{ __('No submitted entries yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
