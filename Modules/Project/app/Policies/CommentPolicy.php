<?php

declare(strict_types=1);

namespace Modules\Project\Policies;

use Modules\Acl\Models\User;
use Modules\Project\Models\Comment;

class CommentPolicy
{
    /**
     * Determine whether the user can view any comments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(User $user, Comment $comment): bool
    {
        return $user->company_id === $comment->company_id;
    }

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        if ($user->company_id !== $comment->company_id) {
            return false;
        }

        // Only the comment author can edit
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($user->company_id !== $comment->company_id) {
            return false;
        }

        // Only the author or company Admin can delete
        return $user->id === $comment->user_id || $user->hasRole('Admin');
    }
}
