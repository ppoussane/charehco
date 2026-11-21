<?php

use App\Enums\Role;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            ['label' => __('Participants'), 'value' => User::where('role', Role::Participant->value)->count(), 'icon' => 'users'],
            ['label' => __('Jury members'), 'value' => User::where('role', Role::Jury->value)->count(), 'icon' => 'scale'],
            ['label' => __('Published projects'), 'value' => Project::published()->count(), 'icon' => 'building-office-2'],
            ['label' => __('Submitted entries'), 'value' => CompetitionEntry::whereNotNull('submitted_at')->count(), 'icon' => 'inbox-stack'],
        ];
    }

    #[Computed]
    public function openCompetitions()
    {
        return Competition::published()
            ->where('starts_at', '<=', now())
            ->where('deadline_at', '>=', now())
            ->orderBy('deadline_at')
            ->get();
    }

    #[Computed]
    public function myEntries()
    {
        return $this->user->entries()->with('competition')->latest()->get();
    }

    #[Computed]
    public function juryCompetitions()
    {
        return $this->user->juryCompetitions()
            ->withCount(['entries as submitted_entries_count' => fn ($q) => $q->whereNotNull('submitted_at')])
            ->orderBy('deadline_at')
            ->get();
    }
}; ?>

<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Welcome, :name', ['name' => $this->user->name]) }}</flux:heading>
        <flux:subheading>
            @if ($this->user->isAdmin())
                {{ __('You are signed in as the site administrator.') }}
            @elseif ($this->user->isJury())
                {{ __('You are a jury member. Review the entries of the competitions assigned to you.') }}
            @else
                {{ __('Browse open competitions and upload your documents to take part.') }}
            @endif
        </flux:subheading>
    </div>

    @if ($this->user->isAdmin())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->stats as $stat)
                <flux:card class="flex items-center gap-4">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/10">
                        <flux:icon :name="$stat['icon']" class="size-5 text-zinc-500" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-zinc-800 dark:text-white">{{ $stat['value'] }}</div>
                        <flux:text size="sm">{{ $stat['label'] }}</flux:text>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="plus" variant="primary" :href="route('admin.competitions.create')" wire:navigate>{{ __('New competition') }}</flux:button>
            <flux:button icon="plus" :href="route('manage.projects.create')" wire:navigate>{{ __('New project') }}</flux:button>
            <flux:button icon="users" :href="route('admin.users')" wire:navigate>{{ __('Manage users') }}</flux:button>
            <flux:button icon="photo" :href="route('admin.home-page')" wire:navigate>{{ __('Edit home page images') }}</flux:button>
        </div>
    @endif

    @if ($this->user->isJury())
        <section class="space-y-3">
            <flux:heading size="lg">{{ __('Your jury assignments') }}</flux:heading>
            @forelse ($this->juryCompetitions as $competition)
                <flux:card class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <flux:heading>{{ $competition->t('title') }}</flux:heading>
                        <flux:text size="sm">{{ trans_choice(':count submitted entry|:count submitted entries', $competition->submitted_entries_count) }} · {{ __('Deadline :date', ['date' => $competition->deadline_at->localDate('short')]) }}</flux:text>
                    </div>
                    <flux:button size="sm" icon="scale" :href="route('review.competition', $competition->id)" wire:navigate>{{ __('Review entries') }}</flux:button>
                </flux:card>
            @empty
                <flux:callout icon="information-circle">
                    <flux:callout.text>{{ __('The admin has not assigned you to a competition yet.') }}</flux:callout.text>
                </flux:callout>
            @endforelse
        </section>
    @endif

    @if ($this->user->can_edit && ! $this->user->isAdmin())
        <flux:card class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading>{{ __('You can share projects') }}</flux:heading>
                <flux:text size="sm">{{ __('The admin gave you the Edit permission.') }}</flux:text>
            </div>
            <flux:button size="sm" icon="plus" variant="primary" :href="route('manage.projects.create')" wire:navigate>{{ __('Share a project') }}</flux:button>
        </flux:card>
    @endif

    @if ($this->user->isParticipant())
        <section class="space-y-3">
            <flux:heading size="lg">{{ __('Open competitions') }}</flux:heading>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($this->openCompetitions as $competition)
                    @php($entry = $this->myEntries->firstWhere('competition_id', $competition->id))
                    <flux:card class="flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <flux:heading>{{ $competition->t('title') }}</flux:heading>
                            <flux:badge size="sm" :color="$competition->status()->color()">{{ $competition->status()->label() }}</flux:badge>
                        </div>
                        <flux:text size="sm">{{ $competition->t('summary') }}</flux:text>
                        <flux:text size="sm">{{ __('Deadline :date', ['date' => $competition->deadline_at->localDate('short_time')]) }}</flux:text>
                        <div class="mt-auto flex gap-2">
                            <flux:button size="sm" variant="primary" :href="route('entries.edit', $competition)" wire:navigate>
                                {{ $entry ? __('Continue my entry') : __('Attend this competition') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" :href="route('competitions.show', $competition)">{{ __('Read the brief') }}</flux:button>
                        </div>
                    </flux:card>
                @empty
                    <flux:text>{{ __('There are no open competitions right now.') }}</flux:text>
                @endforelse
            </div>
        </section>

        @if ($this->myEntries->isNotEmpty())
            <section class="space-y-3">
                <flux:heading size="lg">{{ __('Your entries') }}</flux:heading>
                @foreach ($this->myEntries as $entry)
                    <flux:card class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <flux:heading>{{ $entry->title }}</flux:heading>
                            <flux:text size="sm">{{ $entry->competition->t('title') }}</flux:text>
                        </div>
                        @if ($entry->isSubmitted())
                            <flux:badge color="green" size="sm" icon="check-circle">{{ __('Submitted') }}</flux:badge>
                        @else
                            <flux:badge color="amber" size="sm">{{ __('Draft') }}</flux:badge>
                        @endif
                    </flux:card>
                @endforeach
            </section>
        @endif
    @endif
</div>
