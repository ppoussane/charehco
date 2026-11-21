<?php

use App\Models\Competition;
use App\Models\Project;
use App\Models\User;
use App\Support\HomePage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the home page shows two projects and one competition', function () {
    Project::factory()->count(3)->create(['show_on_home' => true]);
    $competition = Competition::factory()->create(['show_on_home' => true, 'title' => 'Lakeside Living']);

    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('Lakeside Living')
        ->assertSee('Selected projects');
    expect(substr_count($response->getContent(), 'class="project-card__content'))->toBe(2);
});

test('the home page works without any content', function () {
    $this->get(route('home'))->assertOk();
});

test('home image changes stay in draft until the admin publishes them', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $before = HomePage::published();

    Livewire::actingAs($admin)->test('pages::admin.home-page')
        ->set('heroUploads', [UploadedFile::fake()->image('new-hero.jpg', 1920, 1080)])
        ->assertHasNoErrors();

    $draftHero = HomePage::draft()['hero'];
    $newImage = end($draftHero);

    expect(HomePage::published())->toBe($before)
        ->and(HomePage::hasUnpublishedChanges())->toBeTrue();

    // Visitors still see the old images; the admin preview shows the new one.
    $this->get(route('home'))->assertDontSee($newImage);
    $this->actingAs($admin)->get(route('admin.home-page.preview'))->assertSee($newImage);

    Livewire::actingAs($admin)->test('pages::admin.home-page')->call('publish');

    expect(HomePage::published()['hero'])->toContain($newImage)
        ->and(HomePage::hasUnpublishedChanges())->toBeFalse();
    $this->get(route('home'))->assertSee($newImage);
});

test('discarding the draft restores the published images', function () {
    $admin = User::factory()->admin()->create();
    $before = HomePage::published();

    Livewire::actingAs($admin)->test('pages::admin.home-page')
        ->call('remove', 'hero', 0)
        ->call('discard');

    expect(HomePage::draft())->toBe($before);
});

test('the hero always keeps at least one image', function () {
    $admin = User::factory()->admin()->create();
    HomePage::saveDraft(['hero' => ['demo/hero-1.jpg'], 'spotlight' => []]);

    Livewire::actingAs($admin)->test('pages::admin.home-page')->call('remove', 'hero', 0);

    expect(HomePage::draft()['hero'])->toBe(['demo/hero-1.jpg']);
});
