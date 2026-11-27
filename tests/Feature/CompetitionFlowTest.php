<?php

use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->competition = Competition::factory()->create();
    $this->boards = $this->competition->requirements()->create(['label' => 'Boards', 'accepted_types' => 'pdf', 'max_size_mb' => 5, 'is_required' => true]);
    $this->extra = $this->competition->requirements()->create(['label' => 'Extras', 'accepted_types' => 'zip', 'max_size_mb' => 5, 'is_required' => false]);
});

function submittedEntry(Competition $competition, ?User $user = null): CompetitionEntry
{
    $entry = new CompetitionEntry(['title' => 'Proposal']);
    $entry->competition()->associate($competition);
    $entry->user()->associate($user ?? User::factory()->create());
    $entry->submitted_at = now();
    $entry->save();

    Storage::disk('local')->put("docs/{$entry->id}.pdf", '%PDF-1.4 test');
    $entry->documents()->create([
        'competition_requirement_id' => $competition->requirements()->first()->id,
        'path' => "docs/{$entry->id}.pdf",
        'original_name' => 'boards.pdf',
        'mime_type' => 'application/pdf',
        'size' => 13,
    ]);

    return $entry;
}

test('the admin creates a competition with required documents and a jury', function () {
    $admin = User::factory()->admin()->create();
    $jury = User::factory()->jury()->create();

    Livewire::actingAs($admin)->test('pages::admin.competitions.form')
        ->set('title', 'Harbour Pavilion')
        ->set('starts_at', now()->format('Y-m-d\TH:i'))
        ->set('deadline_at', now()->addMonth()->format('Y-m-d\TH:i'))
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 1600, 1000))
        ->set('requirements.0.label', 'Boards')
        ->set('requirements.0.accepted_types', ['pdf'])
        ->set('juryIds', [(string) $jury->id])
        ->set('is_published', true)
        ->call('save')
        ->assertHasNoErrors();

    $competition = Competition::where('title', 'Harbour Pavilion')->firstOrFail();
    expect($competition->requirements)->toHaveCount(1)
        ->and($competition->jury->pluck('id')->all())->toBe([$jury->id]);
});

test('a participant uploads the required documents and submits an entry', function () {
    $participant = User::factory()->create();

    $component = Livewire::actingAs($participant)->test('pages::entries.edit', ['competition' => $this->competition])
        ->set('title', 'Floating gardens')
        ->call('save', true);

    // Submitting without the required document keeps it a draft.
    $entry = CompetitionEntry::firstOrFail();
    expect($entry->isSubmitted())->toBeFalse();

    $component
        ->set("files.{$this->boards->id}", UploadedFile::fake()->create('boards.pdf', 300, 'application/pdf'))
        ->call('save', true)
        ->assertHasNoErrors();

    $entry->refresh();
    expect($entry->isSubmitted())->toBeTrue()
        ->and($entry->documents)->toHaveCount(1);
    Storage::disk('local')->assertExists($entry->documents->first()->path);
});

test('uploads must match the accepted file types', function () {
    Livewire::actingAs(User::factory()->create())->test('pages::entries.edit', ['competition' => $this->competition])
        ->set('title', 'Wrong file')
        ->set("files.{$this->boards->id}", UploadedFile::fake()->image('boards.png'))
        ->call('save')
        ->assertHasErrors("files.{$this->boards->id}");
});

test('jury members and the admin cannot attend competitions', function () {
    $this->actingAs(User::factory()->jury()->create())->get(route('entries.edit', $this->competition))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('entries.edit', $this->competition))->assertForbidden();
});

test('participants cannot attend once the deadline has passed', function () {
    $closed = Competition::factory()->closedForEntries()->create();

    $this->actingAs(User::factory()->create())->get(route('entries.edit', $closed))->assertForbidden();
});

test('assigned jury can review entries and open documents', function () {
    $jury = User::factory()->jury()->create();
    $this->competition->jury()->attach($jury);
    $entry = submittedEntry($this->competition);
    $document = $entry->documents->first();

    $this->actingAs($jury)->get(route('review.competition', $this->competition->id))->assertOk()->assertSee($entry->code);
    $this->actingAs($jury)->get(route('review.entries.show', $entry))->assertOk()->assertDontSee($entry->user->name);
    $this->actingAs($jury)->get(route('documents.show', $document))->assertOk();
});

test('jury members not assigned to the competition cannot see its documents', function () {
    $entry = submittedEntry($this->competition);
    $outsider = User::factory()->jury()->create();

    $this->actingAs($outsider)->get(route('review.competition', $this->competition->id))->assertForbidden();
    $this->actingAs($outsider)->get(route('documents.show', $entry->documents->first()))->assertForbidden();
});

test('other participants cannot open someone else’s documents', function () {
    $entry = submittedEntry($this->competition);

    $this->actingAs(User::factory()->create())->get(route('documents.show', $entry->documents->first()))->assertForbidden();
    $this->actingAs($entry->user)->get(route('documents.show', $entry->documents->first()))->assertOk();
});

test('jury does not see unsubmitted drafts', function () {
    $jury = User::factory()->jury()->create();
    $this->competition->jury()->attach($jury);
    $draft = submittedEntry($this->competition);
    $draft->forceFill(['submitted_at' => null])->save();

    $this->actingAs($jury)->get(route('review.entries.show', $draft))->assertForbidden();
    $this->actingAs($jury)->get(route('documents.show', $draft->documents->first()))->assertForbidden();
});
