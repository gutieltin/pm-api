<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Task;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /** @test */
    public function trashed_route_does_not_conflict_with_show_and_returns_tasks()
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

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $admin->id,
            'assignee_id' => $admin->id,
        ]);
        $task->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/tasks/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $task->id]);
    }

    /** @test */
    public function restore_route_can_restore_deleted_task()
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

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $admin->id,
            'assignee_id' => $admin->id,
        ]);
        $task->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
}
