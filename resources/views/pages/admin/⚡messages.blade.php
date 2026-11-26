<?php

use App\Models\ContactMessage;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Messages')] class extends Component {
    use WithPagination;

    #[Url(except: false)]
    public bool $unreadOnly = false;

    public ?int $openId = null;

    public function mount(): void
    {
        $this->authorize('admin');
    }

    public function updatedUnreadOnly(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function messages()
    {
        return ContactMessage::query()
            ->when($this->unreadOnly, fn ($q) => $q->unread())
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return ContactMessage::unread()->count();
    }

    #[Computed]
    public function openMessage(): ?ContactMessage
    {
        return $this->openId ? ContactMessage::find($this->openId) : null;
    }

    public function open(int $id): void
    {
        $this->authorize('admin');

        $message = ContactMessage::findOrFail($id);
        if (! $message->isRead()) {
            $message->forceFill(['read_at' => now()])->save();
        }

        $this->openId = $message->id;
        unset($this->openMessage, $this->unreadCount);

        Flux::modal('contact-message')->show();
    }

    public function markUnread(int $id): void
    {
        $this->authorize('admin');

        ContactMessage::whereKey($id)->update(['read_at' => null]);
        unset($this->unreadCount);

        Flux::modal('contact-message')->close();
    }

    public function delete(int $id): void
    {
        $this->authorize('admin');

        ContactMessage::whereKey($id)->delete();
        $this->openId = null;
        unset($this->unreadCount);

        Flux::modal('contact-message')->close();
        Flux::toast(variant: 'success', text: __('Message deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Messages') }}</flux:heading>
            <flux:subheading>{{ __('Messages sent through the contact form. You also receive each one by email.') }}</flux:subheading>
        </div>
        <flux:switch wire:model.live="unreadOnly" :label="__('Unread only (:count)', ['count' => $this->unreadCount])" />
    </div>

    <flux:table :paginate="$this->messages">
        <flux:table.columns>
            <flux:table.column>{{ __('From') }}</flux:table.column>
            <flux:table.column>{{ __('Subject') }}</flux:table.column>
            <flux:table.column>{{ __('Message') }}</flux:table.column>
            <flux:table.column>{{ __('Received') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->messages as $message)
                <flux:table.row :key="$message->id" wire:click="open({{ $message->id }})" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-white/5" data-test="message-row-{{ $message->id }}">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            @unless ($message->isRead())
                                <span class="size-2 shrink-0 rounded-full bg-sky-500" aria-hidden="true"></span>
                            @endunless
                            <div class="min-w-0">
                                <div @class(['truncate text-zinc-800 dark:text-white', 'font-semibold' => ! $message->isRead()])>{{ $message->name }}</div>
                                <div class="truncate text-xs text-zinc-500" dir="ltr">{{ $message->email }}</div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="zinc">{{ $message->topicLabel() }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="max-w-sm truncate text-zinc-500">{{ Str::limit($message->message, 80) }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $message->created_at->localDate('short_time') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="py-10 text-center text-zinc-500">{{ __('No messages yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="contact-message" class="w-full md:max-w-xl">
        @if ($message = $this->openMessage)
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $message->name }}</flux:heading>
                    <flux:text dir="ltr" class="rtl:text-end">{{ $message->email }}</flux:text>
                    <flux:text size="sm" class="mt-1">{{ $message->topicLabel() }} · {{ $message->created_at->localDate('short_time') }}</flux:text>
                </div>
                <div class="whitespace-pre-line rounded-lg bg-zinc-50 p-4 text-sm leading-6 text-zinc-800 dark:bg-white/5 dark:text-zinc-200">{{ $message->message }}</div>
                <div class="flex flex-wrap justify-between gap-2">
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $message->id }})" wire:confirm="{{ __('Delete this message?') }}">{{ __('Delete') }}</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="markUnread({{ $message->id }})">{{ __('Mark as unread') }}</flux:button>
                    </div>
                    <flux:button size="sm" variant="primary" icon="paper-airplane"
                        href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->topicLabel()) }}">{{ __('Reply by email') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
