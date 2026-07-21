<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Acl\Models\Role;
use Modules\Acl\Models\User;
use Modules\Company\Models\AuditLog;
use Modules\Company\Models\Company;
use Modules\Project\Jobs\TaskAssignedNotificationJob;
use Modules\Project\Models\Project;
use Modules\Project\Models\Task;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Create Companies
    $this->companyA = Company::create(['name' => 'Company A', 'slug' => 'company-a']);
    $this->companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b']);

    // Ensure roles exist
    Role::firstOrCreate(['name' => 'Admin']);
    Role::firstOrCreate(['name' => 'Member']);

    // Create Company A users & assign roles
    $this->adminA = User::factory()->create(['company_id' => $this->companyA->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->companyA->id);
    $this->adminA->assignRole('Admin');

    $this->memberA = User::factory()->create(['company_id' => $this->companyA->id]);
    $this->memberA->assignRole('Member');

    // Create Company B users & assign roles
    $this->adminB = User::factory()->create(['company_id' => $this->companyB->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->companyB->id);
    $this->adminB->assignRole('Admin');

    $this->memberB = User::factory()->create(['company_id' => $this->companyB->id]);
    $this->memberB->assignRole('Member');
});

test('tenant isolation: user cannot read or modify resources of another company', function () {
    // 1. Authenticate as Company A Admin and create a project
    $this->actingAs($this->adminA, 'sanctum');
    $project = Project::create([
        'name' => 'Company A Project',
        'description' => 'A unique project',
    ]);

    // 2. Authenticate as Company B Admin and attempt to access/modify Project A
    $this->actingAs($this->adminB, 'sanctum');

    // Show request should return 404 Not Found (since global scope filters it)
    $this->getJson("/api/v1/projects/{$project->id}")
        ->assertStatus(404);

    // Update request should return 404
    $this->patchJson("/api/v1/projects/{$project->id}", ['name' => 'Hacked Project'])
        ->assertStatus(404);

    // Delete request should return 404
    $this->deleteJson("/api/v1/projects/{$project->id}")
        ->assertStatus(404);
});

test('rbac: member cannot create or delete projects', function () {
    // 1. Authenticate as Company A Member
    $this->actingAs($this->memberA, 'sanctum');

    // Member cannot create a project
    $this->postJson('/api/v1/projects', [
        'name' => 'Member Project',
    ])->assertStatus(403);

    // Create project as Admin to try and delete as Member
    $project = Project::create(['company_id' => $this->companyA->id, 'name' => 'Admin Project']);

    // Member cannot delete the project
    $this->actingAs($this->memberA, 'sanctum')
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertStatus(403);
});

test('rbac: member can only update tasks explicitly assigned to them', function () {
    // 1. Create a project
    $project = Project::create(['company_id' => $this->companyA->id, 'name' => 'Shared Project']);

    // 2. Create two tasks: Task 1 assigned to Member, Task 2 assigned to Admin (unassigned to Member)
    $taskAssigned = Task::create([
        'project_id' => $project->id,
        'company_id' => $this->companyA->id,
        'assigned_to_user_id' => $this->memberA->id,
        'title' => 'Task Assigned to Member',
        'status' => 'todo',
    ]);

    $taskUnassigned = Task::create([
        'project_id' => $project->id,
        'company_id' => $this->companyA->id,
        'assigned_to_user_id' => $this->adminA->id,
        'title' => 'Task Assigned to Admin',
        'status' => 'todo',
    ]);

    // Authenticate as Member
    $this->actingAs($this->memberA, 'sanctum');

    // Member CAN update status of their assigned task
    $this->patchJson("/api/v1/projects/{$project->id}/tasks/{$taskAssigned->id}", [
        'status' => 'in_progress',
    ])->assertStatus(200)
        ->assertJsonPath('data.status', 'in_progress');

    // Member CANNOT update status of unassigned task
    $this->patchJson("/api/v1/projects/{$project->id}/tasks/{$taskUnassigned->id}", [
        'status' => 'in_progress',
    ])->assertStatus(403);
});

test('async job: task assignment dispatches notification job', function () {
    Queue::fake();

    $project = Project::create(['company_id' => $this->companyA->id, 'name' => 'Notification Project']);

    $this->actingAs($this->adminA, 'sanctum');

    // 1. Creating a task with an assignee dispatches the job
    $this->postJson("/api/v1/projects/{$project->id}/tasks", [
        'title' => 'New Task',
        'assigned_to_user_id' => $this->memberA->id,
        'status' => 'todo',
    ])->assertStatus(201);

    Queue::assertPushed(TaskAssignedNotificationJob::class);

    // 2. Updating a task to assign a new user dispatches the job
    $task = Task::create([
        'project_id' => $project->id,
        'company_id' => $this->companyA->id,
        'title' => 'Unassigned Task',
        'status' => 'todo',
    ]);

    $this->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
        'assigned_to_user_id' => $this->memberA->id,
    ])->assertStatus(200);

    Queue::assertPushed(TaskAssignedNotificationJob::class, function ($job) {
        return $job->user->id === $this->memberA->id;
    });
});

test('audit log: modifications create tenant isolated logs', function () {
    // Authenticate as Company A Admin
    $this->actingAs($this->adminA, 'sanctum');

    // 1. Create a project
    $project = Project::create([
        'name' => 'Audit Project',
        'description' => 'Will be logged',
    ]);

    // Assert audit log exists in Company A's context
    $this->assertDatabaseHas('audit_logs', [
        'company_id' => $this->companyA->id,
        'auditable_type' => Project::class,
        'auditable_id' => $project->id,
        'event' => 'created',
        'user_id' => $this->adminA->id,
    ]);

    // 2. Authenticate as Company B Admin and assert they cannot query Company A's audit logs
    $this->actingAs($this->adminB, 'sanctum');

    // Querying AuditLog through Eloquent model (which uses BelongsToTenant) should return empty since no Company B logs exist
    $this->assertCount(0, AuditLog::all());
});
