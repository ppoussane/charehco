<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media
{
    /**
     * Resolve a stored image reference to a URL.
     *
     * Values may be a path on the public disk or an absolute URL.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete an uploaded file from the public disk.
     *
     * Remote URLs and the shared demo images are never deleted.
     */
    public static function delete(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '//', 'demo/'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
