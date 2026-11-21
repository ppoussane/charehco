<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

/**
 * Formats dates for the current language: Gregorian in English,
 * the Persian (Solar Hijri / Jalali) calendar with Persian digits in Farsi.
 *
 * Usage: $date->localDate('date'), or LocalDate::format($date, 'short').
 */
class LocalDate
{
    /**
     * style => [PHP format for English, ICU pattern for Farsi]
     */
    public const STYLES = [
        'date' => ['j F Y', 'd MMMM y'],
        'datetime' => ['j F Y, H:i', 'd MMMM y، ساعت HH:mm'],
        'short' => ['M j, Y', 'd MMMM y'],
        'short_time' => ['M j, Y H:i', 'd MMMM y، HH:mm'],
        'full' => ['l j F Y, H:i', 'EEEE d MMMM y، ساعت HH:mm'],
    ];

    public static function format(?DateTimeInterface $date, string $style = 'date'): string
    {
        if ($date === null) {
            return '';
        }

        [$php, $icu] = self::STYLES[$style] ?? self::STYLES['date'];

        if (Locale::isFarsi() && class_exists(IntlDateFormatter::class)) {
            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $date->getTimezone(),
                IntlDateFormatter::TRADITIONAL,
                $icu,
            );

            $formatted = $formatter->format($date);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $date->format($php);
    }
}
