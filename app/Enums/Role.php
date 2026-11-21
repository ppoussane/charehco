<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Jury = 'jury';
    case Participant = 'participant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Jury => __('Jury'),
            self::Participant => __('Participant'),
        };
    }

    /**
     * Roles the admin may hand out from the user management page.
     *
     * @return array<int, self>
     */
    public static function assignable(): array
    {
        return [self::Participant, self::Jury];
    }
}
