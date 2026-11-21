<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen language (session, then cookie, then the default).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;
        $locale ??= $request->cookie(Locale::COOKIE);

        if (! Locale::isSupported($locale)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Date::setLocale($locale);

        return $next($request);
    }
}
