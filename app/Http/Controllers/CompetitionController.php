<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::published()->orderByDesc('deadline_at')->get();

        return view('site.competitions.index', [
            'current' => $competitions->filter(fn (Competition $c) => $c->deadline_at->isFuture())->sortBy('deadline_at')->values(),
            'past' => $competitions->filter(fn (Competition $c) => $c->deadline_at->isPast())->values(),
        ]);
    }

    public function show(Competition $competition): View
    {
        Gate::authorize('view', $competition);

        $user = Auth::user();

        return view('site.competitions.show', [
            'competition' => $competition->load('requirements'),
            'entry' => $user ? $competition->entries()->where('user_id', $user->id)->first() : null,
        ]);
    }
}
