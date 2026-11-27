<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EntryDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomePreviewController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('language/{locale}', LocaleController::class)
    ->whereIn('locale', array_keys(config('chareharch.locales')))
    ->name('locale.switch');

Route::get('about', AboutController::class)->name('about');

Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('competitions', [CompetitionController::class, 'index'])->name('competitions.index');
Route::get('competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');

/*
|--------------------------------------------------------------------------
| Signed-in area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    // Participants: attend competitions and upload the required documents.
    Route::livewire('my/entries', 'pages::entries.index')->name('entries.index');
    Route::livewire('competitions/{competition}/enter', 'pages::entries.edit')->name('entries.edit');

    // Admin and assigned jury: review entries and their documents.
    Route::livewire('review', 'pages::review.index')->name('review.index');
    Route::livewire('review/competitions/{competition:id}', 'pages::review.competition')->name('review.competition');
    Route::livewire('review/entries/{entry}', 'pages::review.entry')->name('review.entries.show');

    Route::get('documents/{document}', EntryDocumentController::class)->name('documents.show');

    // Admin and users with the "Edit" permission: share projects.
    Route::middleware('can:edit-content')->prefix('manage')->name('manage.')->group(function () {
        Route::livewire('projects', 'pages::manage.projects.index')->name('projects.index');
        Route::livewire('projects/create', 'pages::manage.projects.form')->name('projects.create');
        Route::livewire('projects/{project:id}/edit', 'pages::manage.projects.form')->name('projects.edit');
    });

    // Admin only.
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('users', 'pages::admin.users')->name('users');
        Route::livewire('messages', 'pages::admin.messages')->name('messages');

        Route::livewire('competitions', 'pages::admin.competitions.index')->name('competitions.index');
        Route::livewire('competitions/create', 'pages::admin.competitions.form')->name('competitions.create');
        Route::livewire('competitions/{competition:id}/edit', 'pages::admin.competitions.form')->name('competitions.edit');

        Route::livewire('home-page', 'pages::admin.home-page')->name('home-page');
        Route::get('home-page/preview', HomePreviewController::class)->name('home-page.preview');
    });
});

require __DIR__.'/settings.php';
