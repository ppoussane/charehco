{{-- EN / فا language switch for the signed-in area and auth pages (Flux styling). --}}
<div {{ $attributes->class('flex items-center gap-1') }} role="navigation" aria-label="{{ __('Language') }}">
    <flux:icon name="language" variant="micro" class="text-zinc-400" />
    @foreach (App\Support\Locale::supported() as $code => $locale)
        <a href="{{ route('locale.switch', $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
            @class([
                'rounded-md px-2 py-1 text-sm transition',
                'bg-zinc-800/10 font-medium text-zinc-800 dark:bg-white/15 dark:text-white' => app()->getLocale() === $code,
                'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => app()->getLocale() !== $code,
            ])
            @if (app()->getLocale() === $code) aria-current="true" @endif>{{ $locale['name'] }}</a>
    @endforeach
</div>
