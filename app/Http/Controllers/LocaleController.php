<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * Switch the interface language and return to the previous page.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locale::isSupported($locale), 404);

        $request->session()->put('locale', $locale);
        Cookie::queue(Locale::COOKIE, $locale, 60 * 24 * 365);

        return redirect()->back(fallback: route('home'));
    }
}
