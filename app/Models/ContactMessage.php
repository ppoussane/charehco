<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A message sent through the public contact form.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $topic
 * @property string $message
 * @property string|null $locale
 * @property Carbon|null $read_at
 */
#[Fillable(['name', 'email', 'topic', 'message', 'locale'])]
class ContactMessage extends Model
{
    /**
     * Topics visitors can pick on the contact form (key => English label).
     */
    public const TOPICS = [
        'general' => 'General question',
        'competitions' => 'Competitions',
        'partnership' => 'Partnership',
        'press' => 'Press',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ContactMessage>  $query
     */
    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function topicLabel(): string
    {
        return __(self::TOPICS[$this->topic] ?? 'General question');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
