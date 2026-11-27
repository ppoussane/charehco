<?php

namespace App\Http\Controllers;

use App\Support\HomePage;
use App\Support\Media;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    /**
     * The About page reuses the photos managed in Admin → Home page,
     * so replacing them there updates this page too.
     */
    public function __invoke(): View
    {
        $published = HomePage::published();

        $images = collect([...$published['hero'], ...$published['spotlight']])
            ->unique()
            ->values()
            ->map(Media::url(...));

        return view('site.about', [
            'heroImage' => $images->get(1) ?? $images->first(),
            'gridImages' => $images->slice(2, 3)->values()->pad(3, $images->first())->filter()->all(),
        ]);
    }
}
