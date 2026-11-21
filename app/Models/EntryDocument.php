<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A file a participant uploaded for a competition entry. Stored on the private disk.
 *
 * @property int $id
 * @property int $competition_entry_id
 * @property int|null $competition_requirement_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $size
 */
#[Fillable(['competition_requirement_id', 'path', 'original_name', 'mime_type', 'size'])]
class EntryDocument extends Model
{
    public const DISK = 'local';

    protected static function booted(): void
    {
        static::deleted(function (EntryDocument $document) {
            Storage::disk(self::DISK)->delete($document->path);
        });
    }

    /**
     * @return BelongsTo<CompetitionEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompetitionEntry::class, 'competition_entry_id');
    }

    /**
     * @return BelongsTo<CompetitionRequirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(CompetitionRequirement::class, 'competition_requirement_id');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);
    }
}
