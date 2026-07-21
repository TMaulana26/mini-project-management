<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Acl\Models\User;
use Modules\Company\Models\Company;
use Modules\Project\Models\Project;
use Modules\Project\Models\Task;
use Spatie\Permission\PermissionRegistrar;

class SaaSFixtureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Companies (Tenants)
        $dimension = Company::firstOrCreate(
            ['slug' => 'dimension-software'],
            ['name' => 'Dimension Software']
        );

        $acme = Company::firstOrCreate(
            ['slug' => 'acme-corp'],
            ['name' => 'Acme Corp']
        );

        // 2. Create Users & Assign Team-Scoped Roles
        // -- Dimension Users --
        $dimensionAdmin = User::firstOrCreate(
            ['email' => 'admin@dimension.com'],
            [
                'name' => 'Dimension Admin',
                'company_id' => $dimension->id,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        app(PermissionRegistrar::class)->setPermissionsTeamId($dimension->id);
        $dimensionAdmin->assignRole('Admin');

        $dimensionMember = User::firstOrCreate(
            ['email' => 'member@dimension.com'],
            [
                'name' => 'Dimension Member',
                'company_id' => $dimension->id,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $dimensionMember->assignRole('Member');

        // -- Acme Users --
        $acmeAdmin = User::firstOrCreate(
            ['email' => 'admin@acme.com'],
            [
                'name' => 'Acme Admin',
                'company_id' => $acme->id,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        app(PermissionRegistrar::class)->setPermissionsTeamId($acme->id);
        $acmeAdmin->assignRole('Admin');

        $acmeMember = User::firstOrCreate(
            ['email' => 'member@acme.com'],
            [
                'name' => 'Acme Member',
                'company_id' => $acme->id,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $acmeMember->assignRole('Member');

        // 3. Create Projects (using company_id injection)
        // -- Dimension Projects --
        // To bypass global scope filters during seeding if we aren't logged in, we set company_id directly.
        $dimensionProject1 = Project::firstOrCreate(
            ['name' => 'SaaS Core API', 'company_id' => $dimension->id],
            ['description' => 'Core APIs for multi-tenant SaaS project management application.']
        );

        $dimensionProject2 = Project::firstOrCreate(
            ['name' => 'Internal Tooling', 'company_id' => $dimension->id],
            ['description' => 'Internal administrative utility interfaces.']
        );

        // -- Acme Projects --
        $acmeProject1 = Project::firstOrCreate(
            ['name' => 'Acme Web Portal', 'company_id' => $acme->id],
            ['description' => 'Acme Corporation public facing web presence.']
        );

        // 4. Create Tasks
        // -- Dimension Tasks --
        Task::firstOrCreate(
            [
                'title' => 'Implement Multi-Tenancy Scoping',
                'project_id' => $dimensionProject1->id,
                'company_id' => $dimension->id,
            ],
            [
                'description' => 'Design global scopes and traits for robust data isolation.',
                'status' => 'in_progress',
                'assigned_to_user_id' => $dimensionAdmin->id,
            ]
        );

        Task::firstOrCreate(
            [
                'title' => 'Configure Global Exception Envelope',
                'project_id' => $dimensionProject1->id,
                'company_id' => $dimension->id,
            ],
            [
                'description' => 'Ensure all validation, auth, and model exceptions render JSON envelopes.',
                'status' => 'todo',
                'assigned_to_user_id' => $dimensionMember->id,
            ]
        );

        Task::firstOrCreate(
            [
                'title' => 'Write Pest Integration Tests',
                'project_id' => $dimensionProject1->id,
                'company_id' => $dimension->id,
            ],
            [
                'description' => 'Coverage for RBAC, scopes, and queue jobs.',
                'status' => 'todo',
            ]
        );

        // -- Acme Tasks --
        Task::firstOrCreate(
            [
                'title' => 'Design Acme Landing Page',
                'project_id' => $acmeProject1->id,
                'company_id' => $acme->id,
            ],
            [
                'description' => 'Interactive landing pages with tailwind styles.',
                'status' => 'in_progress',
                'assigned_to_user_id' => $acmeAdmin->id,
            ]
        );

        Task::firstOrCreate(
            [
                'title' => 'Setup Acme Auth',
                'project_id' => $acmeProject1->id,
                'company_id' => $acme->id,
            ],
            [
                'description' => 'Lightweight cookie session logic.',
                'status' => 'todo',
                'assigned_to_user_id' => $acmeMember->id,
            ]
        );
    }
}
