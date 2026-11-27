@props([
    'title' => null,
    'description' => null,
    // See-through header: true over a dark picture (white text), 'dark' over a light background.
    'overlayHeader' => false,
    'preview' => false,
])
<!DOCTYPE html>
<html {!! App\Support\Locale::htmlAttributes() !!}>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ filled($title) ? $title.' | '.config('app.name') : config('app.name').' | '.__('Architecture & competitions') }}</title>
        <meta name="description" content="{{ $description ?? __('Chareharch organises architecture competitions and shares inspiring projects.') }}">
        @if ($preview)
            <meta name="robots" content="noindex, nofollow">
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <script>
            // Enable scroll animations only when motion is welcome; never leave content hidden if scripts fail.
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.classList.add('js-anim');
                setTimeout(() => { if (!window.__siteReady) document.documentElement.classList.remove('js-anim'); }, 3000);
            }
        </script>

        @fonts
        @vite(['resources/css/site.css', 'resources/js/site.js'])
    </head>
    <body>
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded focus:bg-white focus:px-4 focus:py-2">{{ __('Skip to content') }}</a>

        @if ($preview)
            <div class="fixed bottom-4 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-ink/90 px-5 py-2 text-sm leading-none font-normal text-white shadow-lg">
                {{ __('Preview — not published yet') }}
            </div>
        @endif

        <x-site.header :overlay="$overlayHeader" />

        <main id="main">
            {{ $slot }}
        </main>

        <x-site.footer />
    </body>
</html>
