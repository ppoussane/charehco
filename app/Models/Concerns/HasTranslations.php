<?php

namespace App\Models\Concerns;

use App\Support\Locale;

/**
 * Content stored in English with optional Farsi versions in `<field>_fa` columns.
 */
trait HasTranslations
{
    /**
     * The field in the current language, falling back to English when the
     * Farsi version is empty.
     */
    public function t(string $field): ?string
    {
        if (Locale::isFarsi()) {
            $farsi = $this->getAttribute($field.'_fa');

            if (filled($farsi)) {
                return $farsi;
            }
        }

        return $this->getAttribute($field);
    }
}
