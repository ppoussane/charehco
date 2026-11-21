<?php

namespace App\Enums;

enum CompetitionStatus: string
{
    case Upcoming = 'upcoming';
    case Open = 'open';
    case Judging = 'judging';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => __('Opening soon'),
            self::Open => __('Open for entries'),
            self::Judging => __('In judging'),
            self::Closed => __('Closed'),
        };
    }

    /**
     * Flux badge colour for the admin/app area.
     */
    public function color(): string
    {
        return match ($this) {
            self::Upcoming => 'sky',
            self::Open => 'green',
            self::Judging => 'amber',
            self::Closed => 'zinc',
        };
    }
}
