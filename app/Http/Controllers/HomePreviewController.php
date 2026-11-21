<?php

namespace App\Http\Controllers;

use App\Support\HomePage;
use Illuminate\Contracts\View\View;

/**
 * Renders the home page with the admin's unpublished draft images.
 */
class HomePreviewController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            ...HomePage::viewData(HomePage::draft()),
            'preview' => true,
        ]);
    }
}
