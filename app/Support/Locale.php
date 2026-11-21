<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

class Locale
{
    public const COOKIE = 'locale';

    /**
     * @return array<string, array{name: string, short: string, dir: string}>
     */
    public static function supported(): array
    {
        return config('charehco.locales');
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, static::supported());
    }

    public static function current(): string
    {
        return App::getLocale();
    }

    public static function direction(?string $locale = null): string
    {
        return static::supported()[$locale ?? static::current()]['dir'] ?? 'ltr';
    }

    public static function isRtl(): bool
    {
        return static::direction() === 'rtl';
    }

    public static function isFarsi(): bool
    {
        return static::current() === 'fa';
    }

    /**
     * Attributes for the <html> tag: lang and dir.
     */
    public static function htmlAttributes(): string
    {
        return sprintf('lang="%s" dir="%s"', e(str_replace('_', '-', static::current())), static::direction());
    }
}
