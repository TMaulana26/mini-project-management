<?php

declare(strict_types=1);

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Project\Http\Requests\CommentRequest;
use Modules\Project\Models\Comment;
use Modules\Project\Models\Project;
use Modules\Project\Models\Task;
use Modules\Project\Transformers\CommentResource;

class CommentController extends Controller
{
    /**
     * Display a listing of the comments for a specific task.
     */
    public function index(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);
        Gate::authorize('view', $task);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        $comments = Comment::where('task_id', $task->id)
            ->with(['user'])
            ->latest()
            ->get();

        return $this->successResponse(
            CommentResource::collection($comments),
            'Comments retrieved successfully.'
        );
    }

    /**
     * Store a newly created comment for a specific task.
     */
    public function store(CommentRequest $request, Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);
        Gate::authorize('view', $task);
        Gate::authorize('create', Comment::class);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        $comment = DB::transaction(function () use ($request, $task) {
            $comment = Comment::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'content' => $request->validated('content'),
            ]);

            return $comment;
        });

        $comment->load('user');

        return $this->resourceResponse(
            new CommentResource($comment),
            'Comment added successfully.',
            201
        );
    }

    /**
     * Update the specified comment.
     */
    public function update(CommentRequest $request, Project $project, Task $task, Comment $comment): JsonResponse
    {
        Gate::authorize('view', $project);
        Gate::authorize('view', $task);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        if ($comment->task_id !== $task->id) {
            return $this->errorResponse('Comment does not belong to the specified task.', 404);
        }

        Gate::authorize('update', $comment);

        $updatedComment = DB::transaction(function () use ($request, $comment) {
            $lockedComment = Comment::lockForUpdate()->findOrFail($comment->id);
            $lockedComment->update($request->validated());

            return $lockedComment;
        });

        $updatedComment->load('user');

        return $this->resourceResponse(
            new CommentResource($updatedComment),
            'Comment updated successfully.'
        );
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Project $project, Task $task, Comment $comment): JsonResponse
    {
        Gate::authorize('view', $project);
        Gate::authorize('view', $task);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        if ($comment->task_id !== $task->id) {
            return $this->errorResponse('Comment does not belong to the specified task.', 404);
        }

        Gate::authorize('delete', $comment);

        $comment->delete();

        return $this->successResponse(
            null,
            'Comment deleted successfully.'
        );
    }
}
