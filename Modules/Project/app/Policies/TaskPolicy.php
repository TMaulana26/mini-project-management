<?php

declare(strict_types=1);

namespace Modules\Project\Policies;

use Modules\Acl\Models\User;
use Modules\Project\Models\Task;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->company_id === $task->company_id;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->company_id !== $task->company_id) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        // Members can ONLY update tasks explicitly assigned to them
        return $task->assigned_to_user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole('Admin') && $user->company_id === $task->company_id;
    }
}
