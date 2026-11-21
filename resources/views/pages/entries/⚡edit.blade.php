<?php

use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\CompetitionRequirement;
use App\Models\EntryDocument;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('My entry')] class extends Component {
    use WithFileUploads;

    public Competition $competition;

    public ?CompetitionEntry $entry = null;

    public string $title = '';
    public string $statement = '';

    /** @var array<int|string, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> requirement id => upload */
    public array $files = [];

    public function mount(Competition $competition): void
    {
        $this->competition = $competition;
        $this->entry = $competition->entries()->where('user_id', Auth::id())->first();

        // Without an entry you may only start one while the competition is open.
        if (! $this->entry) {
            $this->authorize('enter', $competition);
        } else {
            $this->authorize('view', $this->entry);
            $this->title = $this->entry->title;
            $this->statement = (string) $this->entry->statement;
        }
    }

    #[Computed]
    public function requirements()
    {
        return $this->competition->requirements;
    }

    #[Computed]
    public function documents()
    {
        return $this->entry?->documents()->get()->keyBy('competition_requirement_id') ?? collect();
    }

    #[Computed]
    public function editable(): bool
    {
        return $this->entry
            ? Auth::user()->can('update', $this->entry)
            : Auth::user()->can('enter', $this->competition);
    }

    public function updatedFiles($value, $key): void
    {
        $requirement = $this->requirementFor($key);
        $this->validateOnly("files.{$key}", ["files.{$key}" => $requirement->fileRules()], [], ["files.{$key}" => $requirement->t('label')]);
    }

    public function save(bool $submit = false): void
    {
        abort_unless($this->editable, 403, __('This competition no longer accepts changes.'));

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'statement' => ['nullable', 'string', 'max:5000'],
        ];
        $attributes = ['title' => __('Entry title'), 'statement' => __('Statement')];
        foreach ($this->files as $requirementId => $file) {
            $requirement = $this->requirementFor($requirementId);
            $rules["files.{$requirementId}"] = $requirement->fileRules();
            $attributes["files.{$requirementId}"] = $requirement->t('label');
        }
        $this->validate($rules, [], $attributes);

        $entry = $this->entry ?? new CompetitionEntry;
        $entry->fill(['title' => $this->title, 'statement' => $this->statement ?: null]);

        if (! $entry->exists) {
            $entry->competition()->associate($this->competition);
            $entry->user()->associate(Auth::user());
        }
        $entry->save();

        foreach ($this->files as $requirementId => $file) {
            $requirement = $this->requirementFor($requirementId);

            $entry->documents()->where('competition_requirement_id', $requirement->id)->get()->each->delete();

            $path = $file->storeAs(
                "competitions/{$this->competition->id}/entries/{$entry->id}",
                Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
                EntryDocument::DISK,
            );

            $entry->documents()->create([
                'competition_requirement_id' => $requirement->id,
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->files = [];
        $this->entry = $entry->fresh();
        unset($this->documents);

        if ($submit) {
            $missing = $this->entry->missingRequirements();

            if ($missing->isNotEmpty()) {
                Flux::toast(variant: 'warning', text: __('Upload the remaining required documents: :list', ['list' => $missing->map->t('label')->join(App\Support\Locale::isFarsi() ? '، ' : ', ')]));

                return;
            }

            $this->entry->forceFill(['submitted_at' => now()])->save();
            Flux::toast(variant: 'success', heading: __('Entry submitted'), text: __('Good luck! You can still replace files until the deadline.'));

            return;
        }

        $this->keepSubmissionComplete();
        Flux::toast(variant: 'success', text: __('Your entry has been saved.'));
    }

    public function removeDocument(int $documentId): void
    {
        abort_unless($this->entry && $this->editable, 403);

        $this->entry->documents()->findOrFail($documentId)->delete();
        $this->entry->refresh();
        unset($this->documents);

        $this->keepSubmissionComplete();
    }

    public function clearUpload(string $requirementId): void
    {
        unset($this->files[$requirementId]);
        $this->resetValidation("files.{$requirementId}");
    }

    /**
     * A submitted entry that lost a required document goes back to draft.
     */
    private function keepSubmissionComplete(): void
    {
        if ($this->entry?->isSubmitted() && $this->entry->missingRequirements()->isNotEmpty()) {
            $this->entry->forceFill(['submitted_at' => null])->save();
            Flux::toast(variant: 'warning', text: __('A required document is missing, so your entry is back to draft. Upload it and submit again.'));
        }
    }

    private function requirementFor(int|string $id): CompetitionRequirement
    {
        return $this->competition->requirements->firstWhere('id', (int) $id) ?? abort(422);
    }
}; ?>

<div class="max-w-3xl space-y-6">
    <div>
        <flux:text size="sm"><a href="{{ route('competitions.show', $competition) }}" class="hover:underline">{{ $competition->t('title') }}</a></flux:text>
        <flux:heading size="xl" level="1">{{ $entry ? __('Your entry') : __('Attend this competition') }}</flux:heading>
        <flux:subheading>
            {{ __('Deadline: :date', ['date' => $competition->deadline_at->localDate('full')]) }}
            @if ($competition->anonymous_judging)
                · {{ __('Judging is anonymous — do not put your name on your documents.') }}
            @endif
        </flux:subheading>
    </div>

    @if ($entry?->isSubmitted())
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>{{ __('Submitted on :date', ['date' => $entry->submitted_at->localDate('datetime')]) }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Your entry reference is :code.', ['code' => $entry->code]) }}
                @if ($this->editable)
                    {{ __('You can still replace documents until the deadline.') }}
                @endif
            </flux:callout.text>
        </flux:callout>
    @elseif (! $this->editable)
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.text>{{ __('This competition no longer accepts entries. Your draft was not submitted.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="save(false)" class="space-y-6">
        <flux:card class="space-y-6">
            <flux:input wire:model="title" :label="__('Entry title')" :description="__('A short title for your proposal.')" :disabled="! $this->editable" required data-test="entry-title" />
            <flux:textarea wire:model="statement" :label="__('Short statement (optional)')" rows="4" :disabled="! $this->editable" />
        </flux:card>

        <flux:card class="space-y-2">
            <flux:heading>{{ __('Documents') }}</flux:heading>
            <flux:text size="sm">{{ __('Upload each document below. Files are only visible to you, the admin and the assigned jury.') }}</flux:text>

            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @foreach ($this->requirements as $requirement)
                    @php($document = $this->documents->get($requirement->id))
                    <div class="space-y-3 py-5" wire:key="requirement-{{ $requirement->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="font-medium text-zinc-800 dark:text-white">
                                    {{ $requirement->t('label') }}
                                    @if ($requirement->is_required)
                                        <span class="text-red-500" aria-hidden="true">*</span>
                                    @else
                                        <span class="text-sm font-normal text-zinc-500">({{ __('optional') }})</span>
                                    @endif
                                </div>
                                @if ($requirement->t('description'))
                                    <flux:text size="sm">{{ $requirement->t('description') }}</flux:text>
                                @endif
                            </div>
                            <flux:badge size="sm" color="zinc">{{ strtoupper(implode(', ', $requirement->extensions())) }} · {{ __('max :size MB', ['size' => $requirement->max_size_mb]) }}</flux:badge>
                        </div>

                        @if ($document)
                            <div class="flex flex-wrap items-center gap-3 rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-white/5">
                                <flux:icon name="document-text" class="size-5 text-zinc-400" />
                                <a href="{{ route('documents.show', $document) }}" target="_blank" class="font-medium hover:underline">{{ $document->original_name }}</a>
                                <span class="text-zinc-500">{{ $document->humanSize() }}</span>
                                @if ($this->editable)
                                    <flux:button size="xs" variant="ghost" icon="trash" class="ms-auto" wire:click="removeDocument({{ $document->id }})" wire:confirm="{{ __('Remove this file?') }}">{{ __('Remove') }}</flux:button>
                                @endif
                            </div>
                        @endif

                        @if ($this->editable)
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="file" wire:model="files.{{ $requirement->id }}"
                                    accept="{{ collect($requirement->extensions())->map(fn ($e) => '.'.$e)->join(',') }}"
                                    class="block text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:text-white dark:file:bg-white dark:file:text-zinc-800"
                                    data-test="upload-{{ $requirement->id }}">
                                <div wire:loading wire:target="files.{{ $requirement->id }}" class="text-sm text-zinc-500">{{ __('Uploading…') }}</div>
                                @if (isset($files[$requirement->id]))
                                    <flux:badge size="sm" color="amber">{{ $document ? __('Will replace the current file when you save') : __('Ready — save to upload') }}</flux:badge>
                                    <flux:button size="xs" variant="ghost" wire:click="clearUpload('{{ $requirement->id }}')">{{ __('Cancel') }}</flux:button>
                                @endif
                            </div>
                            <flux:error name="files.{{ $requirement->id }}" />
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>

        @if ($this->editable)
            <div class="flex flex-wrap items-center gap-3">
                <flux:button type="submit" data-test="save-entry">{{ __('Save draft') }}</flux:button>
                <flux:button type="button" variant="primary" icon="paper-airplane" wire:click="save(true)" data-test="submit-entry">
                    {{ $entry?->isSubmitted() ? __('Save changes') : __('Submit entry') }}
                </flux:button>
                <div wire:loading wire:target="save" class="text-sm text-zinc-500">{{ __('Saving…') }}</div>
            </div>
        @endif
    </form>
</div>
