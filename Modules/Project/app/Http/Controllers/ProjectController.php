<?php

declare(strict_types=1);

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Project\Http\Requests\ProjectRequest;
use Modules\Project\Models\Project;
use Modules\Project\Transformers\ProjectResource;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Project::class);

        // Scope to tenant and eager load tasks to prevent N+1 queries
        $projects = Project::with('tasks')->latest()->get();

        return $this->successResponse(
            ProjectResource::collection($projects),
            'Projects retrieved successfully.'
        );
    }

    /**
     * Store a newly created project in database.
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $project = Project::create($request->validated());

        return $this->resourceResponse(
            new ProjectResource($project),
            'Project created successfully.',
            201
        );
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $project->load('tasks');

        return $this->resourceResponse(
            new ProjectResource($project),
            'Project retrieved successfully.'
        );
    }

    /**
     * Update the specified project in database.
     */
    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return $this->resourceResponse(
            new ProjectResource($project),
            'Project updated successfully.'
        );
    }

    /**
     * Remove the specified project from database.
     */
    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return $this->successResponse(
            null,
            'Project deleted successfully.'
        );
    }
}
