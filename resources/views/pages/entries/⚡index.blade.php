<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My entries')] class extends Component {
    #[Computed]
    public function entries()
    {
        return Auth::user()->entries()->with('competition.requirements')->withCount('documents')->latest()->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('My entries') }}</flux:heading>
            <flux:subheading>{{ __('Competitions you are attending and the documents you uploaded.') }}</flux:subheading>
        </div>
        <flux:button icon="trophy" :href="route('competitions.index')">{{ __('Browse competitions') }}</flux:button>
    </div>

    @forelse ($this->entries as $entry)
        <flux:card class="flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-1">
                <flux:heading>{{ $entry->title }}</flux:heading>
                <flux:text size="sm">
                    {{ $entry->competition->t('title') }} ·
                    {{ __(':uploaded of :total documents', ['uploaded' => $entry->documents_count, 'total' => $entry->competition->requirements->count()]) }} ·
                    {{ __('Deadline :date', ['date' => $entry->competition->deadline_at->localDate('short')]) }}
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                @if ($entry->isSubmitted())
                    <flux:badge color="green" size="sm" icon="check-circle">{{ __('Submitted') }}</flux:badge>
                @else
                    <flux:badge color="amber" size="sm">{{ __('Draft') }}</flux:badge>
                @endif
                <flux:button size="sm" :href="route('entries.edit', $entry->competition)" wire:navigate>{{ __('Open') }}</flux:button>
            </div>
        </flux:card>
    @empty
        <flux:callout icon="trophy">
            <flux:callout.heading>{{ __('You have not entered a competition yet') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Find an open competition, read the brief and upload the required documents.') }}
                <flux:callout.link :href="route('competitions.index')">{{ __('See competitions') }}</flux:callout.link>
            </flux:callout.text>
        </flux:callout>
    @endforelse
</div>
