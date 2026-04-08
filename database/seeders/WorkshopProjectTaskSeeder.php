<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

class WorkshopProjectTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles & permissions are seeded first (only if missing)
        if (! \Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
            $this->call(RoleSeeder::class);
        }

        DB::transaction(function () {
            // Create or reuse an admin user
            $admin = \App\Models\User::where('email', 'admin@example.com')->first();
            if (! $admin) {
                $admin = \App\Models\User::factory()->create([
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                ]);
            }

            // Ensure Spatie admin role
            if (! $admin->hasRole('admin')) {
                $admin->assignRole('admin');
            }
            // Ensure users.role column reflects admin
            if ($admin->role !== 'admin') {
                $admin->role = 'admin';
                $admin->save();
            }

            // Create or reuse one workspace owned by the admin
            $workspace = \App\Models\Workspace::where('slug', 'demo-workspace')->first();
            if (! $workspace) {
                $workspace = \App\Models\Workspace::factory()->create([
                    'owner_id' => $admin->id,
                    'name' => 'Demo Workspace',
                    'slug' => 'demo-workspace',
                ]);
            }

            // Attach admin to the workspace with pivot role 'admin' (idempotent)
            $workspace->users()->syncWithoutDetaching([$admin->id => ['role' => 'admin']]);

            // Create 5 different project owners and a project for each
            $owners = \App\Models\User::factory()->count(5)->create();

            foreach ($owners as $owner) {
                // Assign manager role via Spatie
                if (! $owner->hasRole('manager')) {
                    $owner->assignRole('manager');
                }
                // Ensure users.role column reflects manager
                if ($owner->role !== 'manager') {
                    $owner->role = 'manager';
                    $owner->save();
                }

                // Add owner to workspace as manager in pivot (idempotent)
                $workspace->users()->syncWithoutDetaching([$owner->id => ['role' => 'manager']]);

                // Create a project in this workspace with this owner
                $project = \App\Models\Project::factory()->create([
                    'workspace_id' => $workspace->id,
                    'owner_id' => $owner->id,
                ]);

                // Create 10 tasks for the project, created and assigned to the project owner
                \App\Models\Task::factory()->count(10)->create([
                    'project_id' => $project->id,
                    'creator_id' => $owner->id,
                    'assignee_id' => $owner->id,
                ]);
            }
        });
    }
}
