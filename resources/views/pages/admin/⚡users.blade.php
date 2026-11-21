<?php

use App\Enums\Role;
use App\Models\Competition;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    /** User whose jury competitions are being edited in the modal. */
    public ?int $juryUserId = null;

    /** @var array<int, string> */
    public array $juryCompetitionIds = [];

    public function mount(): void
    {
        $this->authorize('admin');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->withCount('juryCompetitions', 'entries', 'projects')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->role === 'editor', fn ($q) => $q->where('can_edit', true))
            ->when(in_array($this->role, array_column(Role::cases(), 'value'), true), fn ($q) => $q->where('role', $this->role))
            ->orderByRaw("case when role = 'admin' then 0 else 1 end")
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function competitions()
    {
        return Competition::query()->orderByDesc('deadline_at')->get(['id', 'title', 'deadline_at']);
    }

    public function setRole(int $userId, string $role): void
    {
        $this->authorize('admin');

        validator(['role' => $role], [
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::assignable()))],
        ])->validate();

        $user = $this->editableUser($userId);
        $user->role = Role::from($role);
        $user->save();

        if (! $user->isJury()) {
            $user->juryCompetitions()->detach();
        }

        Flux::toast(variant: 'success', text: __(':name is now :role.', ['name' => $user->name, 'role' => $user->role->label()]));
    }

    public function toggleEdit(int $userId): void
    {
        $this->authorize('admin');

        $user = $this->editableUser($userId);
        $user->can_edit = ! $user->can_edit;
        $user->save();

        Flux::toast(
            variant: 'success',
            text: $user->can_edit
                ? __(':name can now create and edit projects.', ['name' => $user->name])
                : __(':name can no longer create or edit projects.', ['name' => $user->name]),
        );
    }

    public function editJury(int $userId): void
    {
        $this->authorize('admin');

        $user = $this->editableUser($userId);
        abort_unless($user->isJury(), 422);

        $this->juryUserId = $user->id;
        $this->juryCompetitionIds = $user->juryCompetitions()->pluck('competitions.id')->map(fn ($id) => (string) $id)->all();

        Flux::modal('jury-competitions')->show();
    }

    public function saveJury(): void
    {
        $this->authorize('admin');

        $this->validate([
            'juryCompetitionIds' => ['array'],
            'juryCompetitionIds.*' => ['integer', 'exists:competitions,id'],
        ]);

        $user = $this->editableUser((int) $this->juryUserId);
        abort_unless($user->isJury(), 422);

        $user->juryCompetitions()->sync(array_map('intval', $this->juryCompetitionIds));

        Flux::modal('jury-competitions')->close();
        Flux::toast(variant: 'success', text: __('Jury assignments updated for :name.', ['name' => $user->name]));
    }

    #[Computed]
    public function juryUser(): ?User
    {
        return $this->juryUserId ? User::find($this->juryUserId) : null;
    }

    /**
     * The admin account itself can't be changed from this page.
     */
    private function editableUser(int $userId): User
    {
        $user = User::findOrFail($userId);
        abort_if($user->isAdmin(), 403, __('The admin account cannot be changed here.'));

        return $user;
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:subheading>{{ __('Give people the Edit permission to share projects, or make them Jury and assign them to competitions.') }}</flux:subheading>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name or email')" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="role">
                <flux:select.option value="">{{ __('All users') }}</flux:select.option>
                <flux:select.option value="participant">{{ __('Participants') }}</flux:select.option>
                <flux:select.option value="jury">{{ __('Jury') }}</flux:select.option>
                <flux:select.option value="editor">{{ __('With Edit permission') }}</flux:select.option>
                <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>{{ __('User') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Edit permission') }}</flux:table.column>
            <flux:table.column>{{ __('Activity') }}</flux:table.column>
            <flux:table.column>{{ __('Joined') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id" data-test="user-row-{{ $user->id }}">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-800 dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->isAdmin())
                            <flux:badge color="amber" size="sm" icon="shield-check">{{ __('Admin') }}</flux:badge>
                        @else
                            <div class="w-36">
                                <flux:select size="sm"
                                    wire:change="setRole({{ $user->id }}, $event.target.value)"
                                    wire:key="role-{{ $user->id }}-{{ $user->role->value }}"
                                    :aria-label="__('Type for :name', ['name' => $user->name])">
                                    @foreach (Role::assignable() as $option)
                                        <flux:select.option :value="$option->value" :selected="$user->role === $option">{{ $option->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->isAdmin())
                            <flux:text size="sm">{{ __('Always') }}</flux:text>
                        @else
                            <flux:switch
                                :checked="$user->can_edit"
                                wire:click="toggleEdit({{ $user->id }})"
                                wire:key="edit-{{ $user->id }}-{{ (int) $user->can_edit }}"
                                :aria-label="__('Edit permission for :name', ['name' => $user->name])"
                                data-test="toggle-edit-{{ $user->id }}" />
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($user->isJury())
                                <flux:button size="xs" icon="scale" wire:click="editJury({{ $user->id }})">
                                    {{ trans_choice(':count competition|:count competitions', $user->jury_competitions_count) }}
                                </flux:button>
                            @endif
                            @if ($user->entries_count)
                                <flux:badge size="sm" color="zinc">{{ trans_choice(':count entry|:count entries', $user->entries_count) }}</flux:badge>
                            @endif
                            @if ($user->projects_count)
                                <flux:badge size="sm" color="zinc">{{ trans_choice(':count project|:count projects', $user->projects_count) }}</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500">{{ $user->created_at?->localDate('short') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-10 text-center text-zinc-500">{{ __('No users match your search.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="jury-competitions" class="md:w-[32rem]">
        <form wire:submit="saveJury" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Jury assignments') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Choose the competitions :name may review. Jury members see the documents submitted to these competitions.', ['name' => $this->juryUser?->name]) }}
                </flux:text>
            </div>

            <flux:checkbox.group wire:model="juryCompetitionIds" class="max-h-80 space-y-3 overflow-y-auto">
                @forelse ($this->competitions as $competition)
                    <flux:checkbox :value="(string) $competition->id" :label="$competition->t('title')" :description="__('Deadline :date', ['date' => $competition->deadline_at->localDate('short')])" />
                @empty
                    <flux:text>{{ __('There are no competitions yet.') }}</flux:text>
                @endforelse
            </flux:checkbox.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save assignments') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
