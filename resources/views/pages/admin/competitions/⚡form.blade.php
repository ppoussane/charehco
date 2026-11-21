<?php

use App\Enums\Role;
use App\Models\Competition;
use App\Models\CompetitionRequirement;
use App\Models\User;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Competition')] class extends Component {
    use WithFileUploads;

    public ?Competition $competition = null;

    public string $title = '';
    public string $summary = '';
    public string $description = '';
    public string $location = '';
    public string $prize = '';

    /** Optional Farsi versions, shown on FA pages. */
    public string $title_fa = '';
    public string $summary_fa = '';
    public string $description_fa = '';
    public string $location_fa = '';
    public string $prize_fa = '';

    public string $starts_at = '';
    public string $deadline_at = '';
    public string $results_at = '';
    public bool $anonymous_judging = true;
    public bool $is_published = false;
    public bool $show_on_home = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $cover = null;

    /** @var array<int, array{id: int|null, label: string, description: string, is_required: bool, accepted_types: array<int, string>, max_size_mb: int}> */
    public array $requirements = [];

    /** @var array<int, string> */
    public array $juryIds = [];

    public function mount(?Competition $competition = null): void
    {
        if ($competition?->exists) {
            $this->authorize('update', $competition);
            $this->competition = $competition;
            $this->title = $competition->title;
            $this->summary = (string) $competition->summary;
            $this->description = (string) $competition->description;
            $this->location = (string) $competition->location;
            $this->prize = (string) $competition->prize;
            foreach (['title_fa', 'summary_fa', 'description_fa', 'location_fa', 'prize_fa'] as $field) {
                $this->{$field} = (string) $competition->{$field};
            }
            $this->starts_at = $competition->starts_at->format('Y-m-d\TH:i');
            $this->deadline_at = $competition->deadline_at->format('Y-m-d\TH:i');
            $this->results_at = (string) $competition->results_at?->format('Y-m-d\TH:i');
            $this->anonymous_judging = $competition->anonymous_judging;
            $this->is_published = $competition->is_published;
            $this->show_on_home = $competition->show_on_home;
            $this->requirements = $competition->requirements->map(fn (CompetitionRequirement $r) => [
                'id' => $r->id,
                'label' => $r->label,
                'label_fa' => (string) $r->label_fa,
                'description' => (string) $r->description,
                'description_fa' => (string) $r->description_fa,
                'is_required' => $r->is_required,
                'accepted_types' => $r->extensions(),
                'max_size_mb' => $r->max_size_mb,
            ])->all();
            $this->juryIds = $competition->jury()->pluck('users.id')->map(fn ($id) => (string) $id)->all();
        } else {
            $this->authorize('create', Competition::class);
            $this->starts_at = now()->startOfDay()->format('Y-m-d\TH:i');
            $this->deadline_at = now()->addMonths(2)->setTime(23, 59)->format('Y-m-d\TH:i');
            $this->addRequirement();
        }
    }

    #[Computed]
    public function juryMembers()
    {
        return User::where('role', Role::Jury->value)->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function addRequirement(): void
    {
        $this->requirements[] = [
            'id' => null,
            'label' => '',
            'label_fa' => '',
            'description' => '',
            'description_fa' => '',
            'is_required' => true,
            'accepted_types' => ['pdf'],
            'max_size_mb' => 20,
        ];
    }

    public function removeRequirement(int $index): void
    {
        unset($this->requirements[$index]);
        $this->requirements = array_values($this->requirements);
    }

    public function moveRequirement(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->requirements[$index], $this->requirements[$target])) {
            return;
        }

        [$this->requirements[$index], $this->requirements[$target]] = [$this->requirements[$target], $this->requirements[$index]];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:30000'],
            'location' => ['nullable', 'string', 'max:255'],
            'prize' => ['nullable', 'string', 'max:255'],
            'title_fa' => ['nullable', 'string', 'max:255'],
            'summary_fa' => ['nullable', 'string', 'max:500'],
            'description_fa' => ['nullable', 'string', 'max:30000'],
            'location_fa' => ['nullable', 'string', 'max:255'],
            'prize_fa' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'deadline_at' => ['required', 'date', 'after:starts_at'],
            'results_at' => ['nullable', 'date', 'after:deadline_at'],
            'anonymous_judging' => ['boolean'],
            'is_published' => ['boolean'],
            'show_on_home' => ['boolean'],
            'cover' => [$this->competition?->cover_image ? 'nullable' : 'required', 'image', 'max:15360'],
            'requirements' => ['array', 'min:1', 'max:20'],
            'requirements.*.label' => ['required', 'string', 'max:255'],
            'requirements.*.description' => ['nullable', 'string', 'max:500'],
            'requirements.*.label_fa' => ['nullable', 'string', 'max:255'],
            'requirements.*.description_fa' => ['nullable', 'string', 'max:500'],
            'requirements.*.is_required' => ['boolean'],
            'requirements.*.accepted_types' => ['required', 'array', 'min:1'],
            'requirements.*.accepted_types.*' => [Rule::in(CompetitionRequirement::ALLOWED_TYPES)],
            'requirements.*.max_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'juryIds' => ['array'],
            'juryIds.*' => [Rule::exists('users', 'id')->where('role', Role::Jury->value)],
        ];
    }

    public function messages(): array
    {
        return [
            'requirements.min' => __('Add at least one document participants must upload.'),
            'requirements.*.label.required' => __('Give this document a name.'),
            'requirements.*.accepted_types.required' => __('Choose at least one file type.'),
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => __('Title'), 'title_fa' => __('Title'),
            'summary' => __('Summary'), 'summary_fa' => __('Summary'),
            'description' => __('Full brief'), 'description_fa' => __('Full brief'),
            'location' => __('Location'), 'location_fa' => __('Location'),
            'prize' => __('Prize'), 'prize_fa' => __('Prize'),
            'starts_at' => __('Entries open'),
            'deadline_at' => __('Submission deadline'),
            'results_at' => __('Results'),
            'cover' => __('Cover image'),
            'requirements.*.label' => __('Document name'),
            'requirements.*.label_fa' => __('Document name in Farsi'),
            'requirements.*.description' => __('Instructions (optional)'),
            'requirements.*.description_fa' => __('Instructions in Farsi'),
            'requirements.*.max_size_mb' => __('Max MB'),
            'requirements.*.accepted_types' => __('Accepted file types'),
        ];
    }

    public function save(): void
    {
        $competition = $this->competition ?? new Competition;
        $competition->exists ? $this->authorize('update', $competition) : $this->authorize('create', Competition::class);

        $this->validate();

        $isNew = ! $competition->exists;

        $competition->fill([
            'title' => $this->title,
            'summary' => $this->summary ?: null,
            'description' => $this->description ?: null,
            'location' => $this->location ?: null,
            'prize' => $this->prize ?: null,
            'title_fa' => $this->title_fa ?: null,
            'summary_fa' => $this->summary_fa ?: null,
            'description_fa' => $this->description_fa ?: null,
            'location_fa' => $this->location_fa ?: null,
            'prize_fa' => $this->prize_fa ?: null,
            'starts_at' => Carbon::parse($this->starts_at),
            'deadline_at' => Carbon::parse($this->deadline_at),
            'results_at' => $this->results_at ? Carbon::parse($this->results_at) : null,
            'anonymous_judging' => $this->anonymous_judging,
            'is_published' => $this->is_published,
            'show_on_home' => $this->show_on_home,
        ]);

        if ($isNew) {
            $competition->user_id = Auth::id();
        }

        if ($this->cover) {
            Media::delete($competition->cover_image);
            $competition->cover_image = $this->cover->store('competitions', 'public');
        }

        $competition->save();

        // Sync the document requirements, keeping ids so uploaded files stay linked.
        $keep = [];
        foreach (array_values($this->requirements) as $sort => $data) {
            $requirement = $data['id']
                ? $competition->requirements()->findOrNew($data['id'])
                : $competition->requirements()->make();

            $requirement->fill([
                'label' => $data['label'],
                'label_fa' => ($data['label_fa'] ?? '') ?: null,
                'description' => $data['description'] ?: null,
                'description_fa' => ($data['description_fa'] ?? '') ?: null,
                'is_required' => (bool) $data['is_required'],
                'accepted_types' => implode(',', $data['accepted_types']),
                'max_size_mb' => (int) $data['max_size_mb'],
                'sort' => $sort,
            ]);
            $requirement->competition()->associate($competition);
            $requirement->save();
            $keep[] = $requirement->id;
        }
        $competition->requirements()->whereNotIn('id', $keep)->delete();

        $this->authorize('assignJury', $competition);
        $competition->jury()->sync(array_map('intval', $this->juryIds));

        $this->reset('cover');
        Flux::toast(variant: 'success', text: __('Competition saved.'));

        if ($isNew) {
            $this->redirectRoute('admin.competitions.edit', $competition->id, navigate: true);

            return;
        }

        $this->competition = $competition->fresh();
        $this->mount($this->competition);
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $competition ? __('Edit competition') : __('New competition') }}</flux:heading>
            <flux:subheading>{{ __('Only the admin can create competitions. The description supports Markdown.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            @if ($competition)
                <flux:button icon="inbox-stack" :href="route('review.competition', $competition->id)" wire:navigate>{{ __('Entries') }}</flux:button>
                <flux:button icon="arrow-top-right-on-square" :href="route('competitions.show', $competition)" target="_blank">{{ __('View') }}</flux:button>
            @endif
            <flux:button variant="ghost" :href="route('admin.competitions.index')" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Brief') }} · {{ __('English version') }}</flux:heading>
                <flux:text size="sm">{{ __('Required. Shown on English pages, and on Farsi pages when no Farsi text is given.') }}</flux:text>
            </div>
            <flux:input wire:model="title" :label="__('Title')" dir="ltr" required data-test="competition-title" />
            <flux:textarea wire:model="summary" :label="__('Summary')" :description="__('Shown on the home page and competition cards.')" dir="ltr" rows="2" />
            <flux:textarea wire:model="description" :label="__('Full brief')" dir="ltr" rows="12" />
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="location" :label="__('Location')" dir="ltr" />
                <flux:input wire:model="prize" :label="__('Prize')" :placeholder="__('e.g. €10,000 first prize')" dir="ltr" />
            </div>
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Brief') }} · {{ __('Farsi version') }} <span lang="fa">(فارسی)</span></flux:heading>
                <flux:text size="sm">{{ __('Optional. Shown when visitors choose FA. Empty fields fall back to English.') }}</flux:text>
            </div>
            <flux:input wire:model="title_fa" :label="__('Title')" dir="rtl" lang="fa" data-test="competition-title-fa" />
            <flux:textarea wire:model="summary_fa" :label="__('Summary')" dir="rtl" lang="fa" rows="2" />
            <flux:textarea wire:model="description_fa" :label="__('Full brief')" dir="rtl" lang="fa" rows="12" />
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="location_fa" :label="__('Location')" dir="rtl" lang="fa" />
                <flux:input wire:model="prize_fa" :label="__('Prize')" dir="rtl" lang="fa" />
            </div>
        </flux:card>

        <flux:card class="space-y-6">
            <flux:heading>{{ __('Timeline') }}</flux:heading>
            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="starts_at" type="datetime-local" :label="__('Entries open')" required />
                <flux:input wire:model="deadline_at" type="datetime-local" :label="__('Submission deadline')" required />
                <flux:input wire:model="results_at" type="datetime-local" :label="__('Results (optional)')" />
            </div>
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Cover image') }}</flux:heading>
                <flux:text size="sm">{{ __('Used on the competition page and as a fallback on the home page.') }}</flux:text>
            </div>
            <div class="flex flex-wrap items-start gap-6">
                <div class="aspect-[4/3] w-64 overflow-hidden rounded-lg bg-zinc-100 dark:bg-white/10">
                    @if ($cover && $cover->isPreviewable())
                        <img src="{{ $cover->temporaryUrl() }}" alt="" class="size-full object-cover">
                    @elseif ($competition?->coverUrl())
                        <img src="{{ $competition->coverUrl() }}" alt="" class="size-full object-cover">
                    @else
                        <div class="flex size-full items-center justify-center text-zinc-400"><flux:icon name="photo" class="size-8" /></div>
                    @endif
                </div>
                <div class="space-y-2">
                    <input type="file" wire:model="cover" accept="image/*" class="block text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:text-white dark:file:bg-white dark:file:text-zinc-800">
                    <div wire:loading wire:target="cover" class="text-sm text-zinc-500">{{ __('Uploading…') }}</div>
                    <flux:error name="cover" />
                </div>
            </div>
        </flux:card>

        <flux:card class="space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <flux:heading>{{ __('Required documents') }}</flux:heading>
                    <flux:text size="sm">{{ __('What participants upload to attend. Jury members see these files.') }}</flux:text>
                </div>
                <flux:button size="sm" icon="plus" wire:click="addRequirement" type="button">{{ __('Add document') }}</flux:button>
            </div>
            <flux:error name="requirements" />

            <div class="space-y-4">
                @foreach ($requirements as $i => $requirement)
                    <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-white/10" wire:key="req-{{ $requirement['id'] ?? 'new-'.$i }}">
                        <div class="flex items-start gap-3">
                            <div class="grid flex-1 gap-4 sm:grid-cols-[1fr_8rem]">
                                <flux:input wire:model="requirements.{{ $i }}.label" :label="__('Document name')" :placeholder="__('e.g. Presentation boards')" dir="ltr" />
                                <flux:input wire:model="requirements.{{ $i }}.max_size_mb" type="number" min="1" max="50" :label="__('Max MB')" />
                            </div>
                            <div class="flex gap-1 pt-6">
                                <flux:button size="sm" variant="ghost" icon="arrow-up" wire:click="moveRequirement({{ $i }}, -1)" type="button" :disabled="$i === 0" :aria-label="__('Move up')" />
                                <flux:button size="sm" variant="ghost" icon="arrow-down" wire:click="moveRequirement({{ $i }}, 1)" type="button" :disabled="$loop->last" :aria-label="__('Move down')" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeRequirement({{ $i }})" type="button" :aria-label="__('Remove')" />
                            </div>
                        </div>
                        <flux:input wire:model="requirements.{{ $i }}.description" :label="__('Instructions (optional)')" dir="ltr" />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="requirements.{{ $i }}.label_fa" :label="__('Document name in Farsi')" dir="rtl" lang="fa" />
                            <flux:input wire:model="requirements.{{ $i }}.description_fa" :label="__('Instructions in Farsi')" dir="rtl" lang="fa" />
                        </div>
                        <flux:checkbox.group wire:model="requirements.{{ $i }}.accepted_types" :label="__('Accepted file types')">
                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                @foreach (App\Models\CompetitionRequirement::ALLOWED_TYPES as $type)
                                    <flux:checkbox :value="$type" :label="strtoupper($type)" />
                                @endforeach
                            </div>
                        </flux:checkbox.group>
                        <flux:error name="requirements.{{ $i }}.accepted_types" />
                        <flux:switch wire:model="requirements.{{ $i }}.is_required" :label="__('Required')" align="left" />
                    </div>
                @endforeach
            </div>
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading>{{ __('Jury') }}</flux:heading>
                <flux:text size="sm">{{ __('Jury members you select can review submitted entries and open their documents. Make someone Jury on the Users page first.') }}</flux:text>
            </div>
            @if ($this->juryMembers->isEmpty())
                <flux:callout icon="information-circle">
                    <flux:callout.text>
                        {{ __('There are no jury members yet.') }}
                        <flux:callout.link :href="route('admin.users')" wire:navigate>{{ __('Manage users') }}</flux:callout.link>
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:checkbox.group wire:model="juryIds" class="space-y-3">
                    @foreach ($this->juryMembers as $member)
                        <flux:checkbox :value="(string) $member->id" :label="$member->name" :description="$member->email" />
                    @endforeach
                </flux:checkbox.group>
            @endif
            <flux:switch wire:model="anonymous_judging" :label="__('Anonymous judging')" :description="__('Jury members see an entry code instead of the participant’s name.')" align="left" />
        </flux:card>

        <flux:card class="space-y-4">
            <flux:switch wire:model="is_published" :label="__('Published')" :description="__('Visible on the website. Participants can attend between the opening date and the deadline.')" align="left" />
            <flux:switch wire:model="show_on_home" :label="__('Feature on the home page')" align="left" />
        </flux:card>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary" data-test="save-competition">{{ __('Save competition') }}</flux:button>
            <div wire:loading wire:target="save" class="text-sm text-zinc-500">{{ __('Saving…') }}</div>
        </div>
    </form>
</div>
