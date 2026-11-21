<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('site.projects.index', [
            'projects' => Project::published()->latest('published_at')->paginate(12),
        ]);
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('site.projects.show', [
            'project' => $project->load('images', 'author'),
            'more' => Project::published()->whereKeyNot($project->id)->latest('published_at')->take(2)->get(),
        ]);
    }
}
