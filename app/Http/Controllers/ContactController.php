<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessage;
use App\Support\HomePage;
use App\Support\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function show(): View
    {
        $hero = HomePage::published()['hero'];

        return view('site.contact', [
            'image' => Media::url(end($hero) ?: null),
            'contact' => config('chareharch.contact'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $backToForm = redirect()->route('contact')->withFragment('contact-form');

        // Bots fill in the hidden "website" field; pretend all went well and drop it.
        if (filled($request->input('website'))) {
            return $backToForm->with('contact_sent', true);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'topic' => ['nullable', Rule::in(array_keys(ContactMessage::TOPICS))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], [], [
            'name' => __('Your name'),
            'email' => __('Email address'),
            'topic' => __('Subject'),
            'message' => __('Message'),
        ]);

        if ($validator->fails()) {
            return $backToForm->withErrors($validator)->withInput();
        }

        $message = ContactMessage::create([...$validator->validated(), 'locale' => App::getLocale()]);

        // A mail problem must never lose the visitor's message: it is already saved.
        rescue(fn () => Notification::send(
            User::where('role', Role::Admin->value)->get(),
            (new NewContactMessage($message))->locale(config('app.locale')),
        ));

        return $backToForm->with('contact_sent', true);
    }
}
