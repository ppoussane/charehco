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

test('the home page has the interactive 3D villa in English and Farsi', function () {
    // The script fills in :degrees itself, so the placeholder must reach the page untouched.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-villa', false)
        ->assertSee('A two-level villa in 3D')
        ->assertSee('Hover over the villa and scroll to turn it.')
        ->assertSee('data-text-right=":degrees° to the right"', false);

    $this->withSession(['locale' => 'fa'])->get(route('home'))
        ->assertSee('ویلایی دو طبقه، سه‌بعدی')
        ->assertSee('نشانگر را روی ویلا ببرید و اسکرول کنید تا بچرخد.')
        ->assertSee('data-text-right=":degrees درجه به راست"', false);
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
