<?php

use App\Models\CompetitionEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Entry')] class extends Component {
    public CompetitionEntry $entry;

    public ?int $previewId = null;

    public function mount(CompetitionEntry $entry): void
    {
        $entry->load('competition.requirements', 'user', 'documents.requirement');

        $this->authorize('view', $entry);
        abort_unless(Auth::user()->can('review', $entry->competition), 403);

        $this->entry = $entry;
        $this->previewId = $entry->documents->first(fn ($d) => $d->isPreviewable())?->id;
    }

    #[Computed]
    public function siblings(): array
    {
        $ids = $this->entry->competition->entries()
            ->unless(Auth::user()->isAdmin(), fn ($q) => $q->whereNotNull('submitted_at'))
            ->orderBy('submitted_at')->orderBy('id')
            ->pluck('id')->all();

        $index = array_search($this->entry->id, $ids, true);

        return [
            'prev' => $index > 0 ? $ids[$index - 1] : null,
            'next' => $index !== false && $index < count($ids) - 1 ? $ids[$index + 1] : null,
            'position' => $index === false ? null : $index + 1,
            'total' => count($ids),
        ];
    }

    #[Computed]
    public function documents()
    {
        return $this->entry->documents->sortBy(fn ($d) => $d->requirement?->sort ?? PHP_INT_MAX)->values();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:text size="sm">
                <a href="{{ route('review.competition', $entry->competition->id) }}" wire:navigate class="hover:underline">{{ $entry->competition->t('title') }}</a>
                @if ($this->siblings['position'])
                    · {{ __('Entry :n of :total', ['n' => $this->siblings['position'], 'total' => $this->siblings['total']]) }}
                @endif
            </flux:text>
            <flux:heading size="xl" level="1">{{ $entry->displayNameFor(auth()->user()) }} — {{ $entry->title }}</flux:heading>
            <flux:subheading>
                @if ($entry->isSubmitted())
                    {{ __('Submitted :date', ['date' => $entry->submitted_at->localDate('short_time')]) }}
                @else
                    {{ __('Draft — not submitted') }}
                @endif
            </flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button size="sm" icon="arrow-up" :href="$this->siblings['prev'] ? route('review.entries.show', $this->siblings['prev']) : null" :disabled="! $this->siblings['prev']" wire:navigate>{{ __('Previous') }}</flux:button>
            <flux:button size="sm" icon-trailing="arrow-down" :href="$this->siblings['next'] ? route('review.entries.show', $this->siblings['next']) : null" :disabled="! $this->siblings['next']" wire:navigate>{{ __('Next') }}</flux:button>
        </div>
    </div>

    @if ($entry->statement)
        <flux:card>
            <flux:heading>{{ __('Statement') }}</flux:heading>
            <flux:text class="mt-2 whitespace-pre-line">{{ $entry->statement }}</flux:text>
        </flux:card>
    @endif

    <div class="grid gap-6 xl:grid-cols-[22rem_1fr]">
        <flux:card class="space-y-1 self-start">
            <flux:heading class="mb-2">{{ __('Documents') }}</flux:heading>
            @forelse ($this->documents as $document)
                <div wire:key="doc-{{ $document->id }}" @class([
                    'flex items-center gap-3 rounded-lg px-2 py-2 text-sm',
                    'bg-zinc-100 dark:bg-white/10' => $previewId === $document->id,
                ])>
                    <flux:icon name="document-text" class="size-5 shrink-0 text-zinc-400" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium">{{ $document->requirement?->t('label') ?? __('Document') }}</div>
                        <div class="truncate text-xs text-zinc-500">{{ $document->original_name }} · {{ $document->humanSize() }}</div>
                    </div>
                    @if ($document->isPreviewable())
                        <flux:button size="xs" variant="ghost" icon="eye" wire:click="$set('previewId', {{ $document->id }})" :aria-label="__('Preview')" />
                    @endif
                    <flux:button size="xs" variant="ghost" icon="arrow-down-tray" :href="route('documents.show', ['document' => $document, 'download' => 1])" :aria-label="__('Download')" />
                </div>
            @empty
                <flux:text size="sm">{{ __('No documents uploaded.') }}</flux:text>
            @endforelse
        </flux:card>

        <div class="min-h-[60vh] overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-white/5">
            @php($preview = $this->documents->firstWhere('id', $previewId))
            @if ($preview && str_starts_with((string) $preview->mime_type, 'image/'))
                <img src="{{ route('documents.show', $preview) }}" alt="{{ $preview->original_name }}" class="mx-auto max-h-[80vh] object-contain">
            @elseif ($preview)
                <iframe src="{{ route('documents.show', $preview) }}" title="{{ $preview->original_name }}" class="h-[80vh] w-full" wire:key="frame-{{ $preview->id }}"></iframe>
            @else
                <div class="flex h-full min-h-[60vh] items-center justify-center p-10 text-center text-sm text-zinc-500">
                    {{ __('Select a PDF or image to preview it here. Other files can be downloaded.') }}
                </div>
            @endif
        </div>
    </div>
</div>
