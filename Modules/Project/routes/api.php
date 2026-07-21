<?php

declare(strict_types=1);

use App\Http\Middleware\TenantTeamMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\ProjectController;
use Modules\Project\Http\Controllers\TaskController;

Route::middleware(['auth:sanctum', TenantTeamMiddleware::class])->prefix('v1')->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class);
});
