<?php

namespace App\Policies;

use App\Models\CompetitionEntry;
use App\Models\User;

class CompetitionEntryPolicy
{
    /**
     * The participant who owns the entry, the admin, and the competition's
     * assigned jury (once the entry is submitted) can see an entry and its documents.
     */
    public function view(User $user, CompetitionEntry $entry): bool
    {
        if ($user->id === $entry->user_id || $user->isAdmin()) {
            return true;
        }

        return $entry->isSubmitted() && $user->isJuryFor($entry->competition);
    }

    /**
     * Participants can change their own entry while the competition accepts entries.
     */
    public function update(User $user, CompetitionEntry $entry): bool
    {
        return $user->id === $entry->user_id && $entry->competition->isAcceptingEntries();
    }
}
