<?php

declare(strict_types=1);

namespace Modules\Project\Policies;

use Modules\Acl\Models\User;
use Modules\Project\Models\Project;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->company_id === $project->company_id;
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasRole('Admin') && $user->company_id === $project->company_id;
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole('Admin') && $user->company_id === $project->company_id;
    }
}
