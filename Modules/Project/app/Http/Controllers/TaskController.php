<?php

declare(strict_types=1);

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Project\Http\Requests\TaskRequest;
use Modules\Project\Jobs\TaskAssignedNotificationJob;
use Modules\Project\Models\Project;
use Modules\Project\Models\Task;
use Modules\Project\Transformers\TaskResource;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks for a specific project.
     */
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        // Retrieve tasks for this project, eager loading assignedTo to prevent N+1 queries
        $tasks = Task::where('project_id', $project->id)
            ->with(['assignedTo', 'project'])
            ->latest()
            ->get();

        return $this->successResponse(
            TaskResource::collection($tasks),
            'Tasks retrieved successfully.'
        );
    }

    /**
     * Store a newly created task for a specific project.
     */
    public function store(TaskRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);
        Gate::authorize('create', Task::class);

        $task = DB::transaction(function () use ($request, $project) {
            $data = $request->validated();
            $data['project_id'] = $project->id;

            $task = Task::create($data);

            if ($task->assigned_to_user_id) {
                $task->load('assignedTo');
                TaskAssignedNotificationJob::dispatch($task, $task->assignedTo);
            }

            return $task;
        });

        return $this->resourceResponse(
            new TaskResource($task),
            'Task created successfully.',
            201
        );
    }

    /**
     * Display the specified task.
     */
    public function show(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        Gate::authorize('view', $task);

        $task->load(['assignedTo', 'project']);

        return $this->resourceResponse(
            new TaskResource($task),
            'Task retrieved successfully.'
        );
    }

    /**
     * Update the specified task.
     */
    public function update(TaskRequest $request, Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        Gate::authorize('update', $task);

        $updatedTask = DB::transaction(function () use ($request, $task) {
            // Apply pessimistic lock to avoid race conditions during concurrent updates
            $lockedTask = Task::lockForUpdate()->findOrFail($task->id);
            $oldAssignee = $lockedTask->assigned_to_user_id;

            $lockedTask->update($request->validated());

            // Dispatch notification if assignee has been changed or set
            if ($lockedTask->assigned_to_user_id && $lockedTask->assigned_to_user_id !== $oldAssignee) {
                $lockedTask->load('assignedTo');
                TaskAssignedNotificationJob::dispatch($lockedTask, $lockedTask->assignedTo);
            }

            return $lockedTask;
        });

        $updatedTask->load(['assignedTo', 'project']);

        return $this->resourceResponse(
            new TaskResource($updatedTask),
            'Task updated successfully.'
        );
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);

        if ($task->project_id !== $project->id) {
            return $this->errorResponse('Task does not belong to the specified project.', 404);
        }

        Gate::authorize('delete', $task);

        $task->delete();

        return $this->successResponse(
            null,
            'Task deleted successfully.'
        );
    }
}
