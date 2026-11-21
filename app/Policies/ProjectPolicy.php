<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(?User $user, Project $project): bool
    {
        return $project->is_published || ($user !== null && $this->update($user, $project));
    }

    /**
     * Admins and users with the "Edit" permission can open the create project page.
     */
    public function create(User $user): bool
    {
        return $user->canEditContent();
    }

    /**
     * Admins manage every project; editors manage the projects they created.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin() || ($user->can_edit && $project->user_id === $user->id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Only the admin decides what is featured on the home page.
     */
    public function feature(User $user): bool
    {
        return $user->isAdmin();
    }
}
