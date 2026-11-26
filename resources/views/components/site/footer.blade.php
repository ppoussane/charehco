<footer class="bg-white">
    <div class="container-site py-16 lg:py-20">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4 lg:col-start-2">
                <x-site.logo class="items-start" />
                <p class="mt-6 max-w-sm text-[15px] leading-7 text-ink/75">
                    {{ __('Charehco organises open architecture competitions and shares the projects that inspire them.') }}
                </p>
            </div>

            <nav class="grid grid-cols-2 gap-x-10 gap-y-3 text-[15px] lg:col-span-4 lg:col-start-7" aria-label="{{ __('Footer') }}">
                <a href="{{ route('home') }}" class="hover:text-gold">{{ __('Home') }}</a>
                <a href="{{ route('about') }}" class="hover:text-gold">{{ __('About us') }}</a>
                <a href="{{ route('projects.index') }}" class="hover:text-gold">{{ __('Projects') }}</a>
                <a href="{{ route('competitions.index') }}" class="hover:text-gold">{{ __('Competitions') }}</a>
                <a href="{{ route('contact') }}" class="hover:text-gold">{{ __('Contact') }}</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-gold">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="hover:text-gold">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" class="hover:text-gold">{{ __('Register') }}</a>
                @endauth
            </nav>
        </div>

        <div class="mt-16 flex flex-wrap justify-between gap-4 border-t border-ink/10 pt-6 text-[13px] text-ink/60 lg:mx-[8.333%]">
            <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
            <x-site.locale-switch />
            <span>{{ __('Architecture & Competitions') }}</span>
        </div>
    </div>
</footer>
