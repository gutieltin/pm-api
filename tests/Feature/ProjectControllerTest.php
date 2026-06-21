<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // seed roles and permissions so we can assign them
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /** @test */
    public function admin_can_delete_project_using_nested_route()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /** @test */
    public function trashed_route_returns_soft_deleted_projects_and_does_not_hit_update_or_destroy()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);

        // create and soft-delete a project
        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);
        $project->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/workspaces/{$workspace->id}/projects/trashed");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $project->id]);
    }

    /** @test */
    public function update_route_also_accepts_project_binding()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
            // ensure initial status is within enum allowed by migration
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function manager_can_delete_project_in_workspace_they_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $manager->role = 'manager';
        $manager->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);
        $workspace->users()->attach($manager->id, ['role' => 'manager']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /** @test */
    public function manager_can_force_delete_project_in_workspace_they_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $manager->role = 'manager';
        $manager->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);
        $workspace->users()->attach($manager->id, ['role' => 'manager']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        // First soft delete the project
        $project->delete();

        // Now try to force delete as manager
        $response = $this->actingAs($manager, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}/force");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
