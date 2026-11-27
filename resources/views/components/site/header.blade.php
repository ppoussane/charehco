{{--
    overlay: false for a solid bar; true for a see-through bar with white text
    over a dark picture; 'dark' for a see-through bar with dark text over a
    light background. See-through bars turn solid once the page scrolls.
--}}
@props(['overlay' => false])

@php
    // Competitions first: most visitors come to take part in one.
    $left = [
        ['label' => __('Competitions'), 'route' => 'competitions.index', 'active' => 'competitions.*'],
        ['label' => __('Projects'), 'route' => 'projects.index', 'active' => 'projects.*'],
        ['label' => __('About us'), 'route' => 'about', 'active' => 'about'],
    ];
@endphp

<header data-site-header @if ($overlay) data-overlay="{{ $overlay === 'dark' ? 'dark' : 'light' }}" @endif @class(['site-header', 'is-solid' => ! $overlay])>
    <div class="container-site grid grid-cols-[1fr_auto_1fr] items-center gap-4">
        {{-- Left navigation --}}
        <nav class="hidden items-center gap-9 xl:flex" aria-label="{{ __('Primary') }}">
            @foreach ($left as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-link', 'is-current' => request()->routeIs($item['active'])])>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <x-site.locale-switch class="text-[15px] xl:hidden" />

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="justify-self-center" aria-label="{{ config('app.name') }} — {{ __('Home') }}">
            <x-site.logo />
        </a>

        {{-- Right navigation --}}
        <div class="flex items-center justify-end gap-8">
            <nav class="hidden items-center gap-9 xl:flex" aria-label="{{ __('Account') }}">
                <a href="{{ route('contact') }}" @class(['nav-link', 'is-current' => request()->routeIs('contact')])>{{ __('Contact') }}</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="nav-link">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link">{{ __('Log in') }}</a>
                @endauth
            </nav>

            <x-site.locale-switch class="hidden text-[15px] xl:flex" />

            <a href="{{ route('competitions.index') }}" class="btn hidden font-medium hover:text-gold xl:inline-flex">
                {{ __('Enter a competition') }}
                <x-site.arrow />
            </a>

            <button type="button" data-menu-toggle aria-expanded="false" aria-controls="site-menu"
                class="flex size-11 items-center justify-center rounded-full border border-current xl:hidden">
                <span class="sr-only">{{ __('Menu') }}</span>
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 8h16M4 16h16"/></svg>
            </button>
        </div>
    </div>
</header>

{{-- Mobile menu --}}
<div id="site-menu" data-menu-panel inert
    class="fixed inset-0 z-40 flex flex-col overflow-y-auto bg-sand pt-28 pb-10 opacity-0 transition-opacity duration-300 [&.is-open]:opacity-100 [&:not(.is-open)]:pointer-events-none xl:hidden">
    <nav class="container-site flex flex-col gap-1 font-serif text-3xl" aria-label="{{ __('Mobile') }}">
        <a href="{{ route('home') }}" class="py-2">{{ __('Home') }}</a>
        @foreach ($left as $item)
            <a href="{{ route($item['route']) }}" class="py-2">{{ $item['label'] }}</a>
        @endforeach
        <a href="{{ route('contact') }}" class="py-2">{{ __('Contact') }}</a>
    </nav>
    <nav class="container-site mt-8 flex flex-col gap-3 border-t border-ink/10 pt-8 text-[17px]" aria-label="{{ __('Account') }}">
        @auth
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
        @else
            <a href="{{ route('login') }}">{{ __('Log in') }}</a>
            <a href="{{ route('register') }}">{{ __('Register') }}</a>
        @endauth
    </nav>
</div>
