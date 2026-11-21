{{-- EN / فا language switch for the public website. --}}
<nav {{ $attributes->class('flex items-center gap-2') }} aria-label="{{ __('Language') }}">
    @foreach (App\Support\Locale::supported() as $code => $locale)
        @if (! $loop->first)
            <span class="opacity-40" aria-hidden="true">/</span>
        @endif
        <a href="{{ route('locale.switch', $code) }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ $locale['name'] }}"
            @class(['nav-link', 'is-current font-normal' => app()->getLocale() === $code])
            @if (app()->getLocale() === $code) aria-current="true" @endif>{{ $locale['short'] }}</a>
    @endforeach
</nav>
