<?php

use App\Models\Competition;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Jury review')] class extends Component {
    public function mount(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isJury(), 403);
    }

    #[Computed]
    public function competitions()
    {
        $user = Auth::user();

        $query = $user->isAdmin() ? Competition::query() : $user->juryCompetitions();

        return $query
            ->withCount(['entries as submitted_entries_count' => fn ($q) => $q->whereNotNull('submitted_at')])
            ->orderByDesc('deadline_at')
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Jury review') }}</flux:heading>
        <flux:subheading>
            {{ auth()->user()->isAdmin()
                ? __('All competitions and their submitted entries.')
                : __('Competitions the admin assigned you to. Open one to see the submitted documents.') }}
        </flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->competitions as $competition)
            <flux:card class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <flux:heading size="lg">{{ $competition->t('title') }}</flux:heading>
                    <flux:badge size="sm" :color="$competition->status()->color()">{{ $competition->status()->label() }}</flux:badge>
                </div>
                <flux:text size="sm">
                    {{ trans_choice(':count submitted entry|:count submitted entries', $competition->submitted_entries_count) }}
                    · {{ __('Deadline :date', ['date' => $competition->deadline_at->localDate('short')]) }}
                </flux:text>
                <div class="mt-auto">
                    <flux:button size="sm" variant="primary" icon="scale" :href="route('review.competition', $competition->id)" wire:navigate>{{ __('Open entries') }}</flux:button>
                </div>
            </flux:card>
        @empty
            <flux:callout icon="information-circle" class="md:col-span-2">
                <flux:callout.text>{{ __('You have not been assigned to a competition yet. The admin assigns jury members to competitions.') }}</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>
</div>
