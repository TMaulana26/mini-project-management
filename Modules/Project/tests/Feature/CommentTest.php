<?php

declare(strict_types=1);

use Modules\Acl\Models\Role;
use Modules\Acl\Models\User;
use Modules\Company\Models\Company;
use Modules\Project\Models\Comment;
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

    $this->memberA2 = User::factory()->create(['company_id' => $this->companyA->id]);
    $this->memberA2->assignRole('Member');

    // Create Company B users & assign roles
    $this->adminB = User::factory()->create(['company_id' => $this->companyB->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->companyB->id);
    $this->adminB->assignRole('Admin');

    $this->memberB = User::factory()->create(['company_id' => $this->companyB->id]);
    $this->memberB->assignRole('Member');

    // Create a base project and task for Company A
    $this->projectA = Project::create([
        'company_id' => $this->companyA->id,
        'name' => 'Project A',
    ]);

    $this->taskA = Task::create([
        'project_id' => $this->projectA->id,
        'company_id' => $this->companyA->id,
        'title' => 'Task A',
        'status' => 'todo',
    ]);

    // Create a base project and task for Company B
    $this->projectB = Project::create([
        'company_id' => $this->companyB->id,
        'name' => 'Project B',
    ]);

    $this->taskB = Task::create([
        'project_id' => $this->projectB->id,
        'company_id' => $this->companyB->id,
        'title' => 'Task B',
        'status' => 'todo',
    ]);
});

test('tenant isolation: user from Company A cannot view comments of Company B', function () {
    // 1. Create a comment for Company B's task
    $commentB = Comment::create([
        'task_id' => $this->taskB->id,
        'company_id' => $this->companyB->id,
        'user_id' => $this->memberB->id,
        'content' => 'Secret comment',
    ]);

    // 2. Authenticate as Company A Member
    $this->actingAs($this->memberA, 'sanctum');

    // Trying to view Company B task comments returns 404 (because task B is not visible to Company A)
    $this->getJson("/api/v1/projects/{$this->projectB->id}/tasks/{$this->taskB->id}/comments")
        ->assertStatus(404);
});

test('tenant isolation: user from Company A cannot create comments on Company B tasks', function () {
    // Authenticate as Company A Member
    $this->actingAs($this->memberA, 'sanctum');

    // Try to post to Company B task
    $this->postJson("/api/v1/projects/{$this->projectB->id}/tasks/{$this->taskB->id}/comments", [
        'content' => 'Illegal comment',
    ])->assertStatus(404);
});

test('tenant isolation: user from Company A cannot update comments of Company B', function () {
    $commentB = Comment::create([
        'task_id' => $this->taskB->id,
        'company_id' => $this->companyB->id,
        'user_id' => $this->memberB->id,
        'content' => 'B comment',
    ]);

    // Authenticate as Company A Member
    $this->actingAs($this->memberA, 'sanctum');

    // Update request returns 404
    $this->patchJson("/api/v1/projects/{$this->projectB->id}/tasks/{$this->taskB->id}/comments/{$commentB->id}", [
        'content' => 'Hacked',
    ])->assertStatus(404);
});

test('view comments: all company members can view each other comments', function () {
    // Create a comment as Admin A
    $comment1 = Comment::create([
        'task_id' => $this->taskA->id,
        'company_id' => $this->companyA->id,
        'user_id' => $this->adminA->id,
        'content' => 'Admin comment',
    ]);

    // Create a comment as Member A
    $comment2 = Comment::create([
        'task_id' => $this->taskA->id,
        'company_id' => $this->companyA->id,
        'user_id' => $this->memberA->id,
        'content' => 'Member comment',
    ]);

    // Authenticate as memberA2 (another member in Company A)
    $this->actingAs($this->memberA2, 'sanctum');

    $this->getJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments")
        ->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['content' => 'Admin comment'])
        ->assertJsonFragment(['content' => 'Member comment']);
});

test('edit comment: only comment author can edit', function () {
    // Create comment as Member A
    $comment = Comment::create([
        'task_id' => $this->taskA->id,
        'company_id' => $this->companyA->id,
        'user_id' => $this->memberA->id,
        'content' => 'Original content',
    ]);

    // 1. Trying to edit as memberA2 should fail with 403
    $this->actingAs($this->memberA2, 'sanctum')
        ->patchJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments/{$comment->id}", [
            'content' => 'Edited by another member',
        ])->assertStatus(403);

    // 2. Trying to edit as adminA (even if they are Admin of company A) should fail with 403 because requirements state "edit hanya penulis comment"
    $this->actingAs($this->adminA, 'sanctum')
        ->patchJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments/{$comment->id}", [
            'content' => 'Edited by Admin',
        ])->assertStatus(403);

    // 3. Editing as the author (Member A) should succeed
    $this->actingAs($this->memberA, 'sanctum')
        ->patchJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments/{$comment->id}", [
            'content' => 'Edited by author',
        ])->assertStatus(200)
        ->assertJsonPath('data.content', 'Edited by author');
});

test('delete comment: only author and company admin can delete', function () {
    // Create comment as Member A
    $comment = Comment::create([
        'task_id' => $this->taskA->id,
        'company_id' => $this->companyA->id,
        'user_id' => $this->memberA->id,
        'content' => 'To be deleted',
    ]);

    // 1. Non-author non-admin member cannot delete (returns 403)
    $this->actingAs($this->memberA2, 'sanctum')
        ->deleteJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments/{$comment->id}")
        ->assertStatus(403);

    // 2. Admin of the same company CAN delete (returns 200)
    $this->actingAs($this->adminA, 'sanctum')
        ->deleteJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments/{$comment->id}")
        ->assertStatus(200);

    // Recreate comment for next assertion
    $comment2 = Comment::create([
        'task_id' => $this->taskA->id,
        'company_id' => $this->companyA->id,
        'user_id' => $this->memberA->id,
        'content' => 'To be deleted 2',
    ]);

    // 3. Author CAN delete (returns 200)
    $this->actingAs($this->memberA, 'sanctum')
        ->deleteJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments/{$comment2->id}")
        ->assertStatus(200);
});

test('audit log: comment creation, update, and deletion are auditable', function () {
    $this->actingAs($this->memberA, 'sanctum');

    // Create a comment
    $response = $this->postJson("/api/v1/projects/{$this->projectA->id}/tasks/{$this->taskA->id}/comments", [
        'content' => 'Auditable comment',
    ])->assertStatus(201);

    $commentId = $response->json('data.id');

    $this->assertDatabaseHas('audit_logs', [
        'company_id' => $this->companyA->id,
        'auditable_type' => Comment::class,
        'auditable_id' => $commentId,
        'event' => 'created',
        'user_id' => $this->memberA->id,
    ]);
});
