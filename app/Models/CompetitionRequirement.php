<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document participants must (or may) upload to enter a competition.
 *
 * @property int $id
 * @property int $competition_id
 * @property string $label
 * @property string|null $description
 * @property bool $is_required
 * @property string $accepted_types Comma separated extensions, e.g. "pdf,jpg"
 * @property int $max_size_mb
 * @property int $sort
 */
#[Fillable(['label', 'label_fa', 'description', 'description_fa', 'is_required', 'accepted_types', 'max_size_mb', 'sort'])]
class CompetitionRequirement extends Model
{
    use HasTranslations;

    /**
     * Extensions an admin may choose from when defining a requirement.
     */
    public const ALLOWED_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'zip', 'dwg', 'docx', 'mp4'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'max_size_mb' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return array<int, string>
     */
    public function extensions(): array
    {
        return collect(explode(',', $this->accepted_types))
            ->map(fn ($type) => strtolower(trim($type)))
            ->filter(fn ($type) => in_array($type, self::ALLOWED_TYPES, true))
            ->values()
            ->all();
    }

    /**
     * Validation rules for an uploaded file meeting this requirement.
     *
     * @return array<int, string>
     */
    public function fileRules(): array
    {
        $rules = ['file', 'max:'.($this->max_size_mb * 1024)];

        if ($extensions = $this->extensions()) {
            $rules[] = 'extensions:'.implode(',', $extensions);
        }

        return $rules;
    }
}
