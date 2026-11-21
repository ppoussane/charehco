<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function view(?User $user, Competition $competition): bool
    {
        return $competition->is_published || ($user?->isAdmin() ?? false);
    }

    /**
     * Only the admin can open the create competition page.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Competition $competition): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only the admin assigns jury members to a competition.
     */
    public function assignJury(User $user, Competition $competition): bool
    {
        return $user->isAdmin();
    }

    /**
     * Regular users (participants) can attend open competitions.
     */
    public function enter(User $user, Competition $competition): bool
    {
        return $user->isParticipant() && $competition->isAcceptingEntries();
    }

    /**
     * The admin and the jury members assigned to this competition can review entries.
     */
    public function review(User $user, Competition $competition): bool
    {
        return $user->isAdmin() || $user->isJuryFor($competition);
    }
}
