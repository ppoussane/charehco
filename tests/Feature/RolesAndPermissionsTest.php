<?php

use App\Enums\Role;
use App\Models\Competition;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Livewire\Livewire;

test('the admin seeder creates the admin account from config', function () {
    config(['charehco.admin_email' => 'boss@example.com', 'charehco.admin_password' => 'secret-password-123']);

    $this->seed(AdminSeeder::class);

    $admin = User::where('email', 'boss@example.com')->firstOrFail();
    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->canEditContent())->toBeTrue()
        ->and($admin->hasVerifiedEmail())->toBeTrue();
});

test('new registrations are regular participants without edit permission', function () {
    $this->post(route('register.store'), [
        'name' => 'New Person',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'new@example.com')->firstOrFail();
    expect($user->role)->toBe(Role::Participant)
        ->and($user->can_edit)->toBeFalse();
});

test('only the admin can open the user management page', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.users'))->assertOk();

    foreach ([User::factory()->create(), User::factory()->editor()->create(), User::factory()->jury()->create()] as $user) {
        $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
    }
});

test('the admin can grant and revoke the edit permission', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)->test('pages::admin.users')->call('toggleEdit', $user->id);
    expect($user->fresh()->can_edit)->toBeTrue();

    Livewire::actingAs($admin)->test('pages::admin.users')->call('toggleEdit', $user->id);
    expect($user->fresh()->can_edit)->toBeFalse();
});

test('the admin can make a user jury, and demoting removes their competitions', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $competition = Competition::factory()->create();

    Livewire::actingAs($admin)->test('pages::admin.users')->call('setRole', $user->id, 'jury');
    expect($user->fresh()->isJury())->toBeTrue();

    $user->juryCompetitions()->attach($competition);

    Livewire::actingAs($admin)->test('pages::admin.users')->call('setRole', $user->id, 'participant');
    expect($user->fresh()->isParticipant())->toBeTrue()
        ->and($user->juryCompetitions()->count())->toBe(0);
});

test('the admin role cannot be handed out or taken away from the users page', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $otherAdmin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test('pages::admin.users')
        ->call('setRole', $user->id, 'admin')
        ->assertHasErrors('role');
    expect($user->fresh()->isAdmin())->toBeFalse();

    Livewire::actingAs($admin)->test('pages::admin.users')
        ->call('toggleEdit', $otherAdmin->id)
        ->assertForbidden();
});

test('users with the edit permission can open the create project page', function () {
    $this->actingAs(User::factory()->editor()->create())->get(route('manage.projects.create'))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('manage.projects.create'))->assertOk();
});

test('users without the edit permission cannot open the create project page', function () {
    $this->actingAs(User::factory()->create())->get(route('manage.projects.create'))->assertForbidden();
    $this->actingAs(User::factory()->jury()->create())->get(route('manage.projects.create'))->assertForbidden();
});

test('editors can only edit their own projects, the admin can edit all', function () {
    $editor = User::factory()->editor()->create();
    $own = Project::factory()->for($editor, 'author')->create();
    $other = Project::factory()->create();

    $this->actingAs($editor)->get(route('manage.projects.edit', $own->id))->assertOk();
    $this->actingAs($editor)->get(route('manage.projects.edit', $other->id))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('manage.projects.edit', $other->id))->assertOk();
});

test('only the admin can open the create competition page', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.competitions.create'))->assertOk();

    foreach ([User::factory()->create(), User::factory()->editor()->create(), User::factory()->jury()->create()] as $user) {
        $this->actingAs($user)->get(route('admin.competitions.create'))->assertForbidden();
    }
});

test('only the admin can open the home page editor and its preview', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.home-page'))->assertOk();
    $this->actingAs($admin)->get(route('admin.home-page.preview'))->assertOk()->assertSee('Preview');

    $this->actingAs(User::factory()->editor()->create())->get(route('admin.home-page'))->assertForbidden();
    $this->actingAs(User::factory()->editor()->create())->get(route('admin.home-page.preview'))->assertForbidden();
});
