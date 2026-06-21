<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    /** @test */
    public function assignee_can_update_task_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $assignee = User::factory()->create();
        $assignee->assignRole('employee');
        $assignee->role = 'employee';
        $assignee->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);
        $workspace->users()->attach($assignee->id, ['role' => 'employee']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $admin->id,
            'assignee_id' => $assignee->id,
            'status' => 'pending',
        ]);

        // Assignee updates task status
        $response = $this->actingAs($assignee, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
            'title' => $task->title, // Title should remain unchanged
        ]);
    }

    /** @test */
    public function assignee_cannot_update_task_fields_other_than_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $assignee = User::factory()->create();
        $assignee->assignRole('employee');
        $assignee->role = 'employee';
        $assignee->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);
        $workspace->users()->attach($assignee->id, ['role' => 'employee']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $admin->id,
            'assignee_id' => $assignee->id,
            'status' => 'pending',
            'title' => 'Original Title',
            'priority' => 'medium',
        ]);

        // Assignee tries to update title and extra fields along with status
        // Extra fields should be ignored, but status should still be updated
        $response = $this->actingAs($assignee, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'title' => 'New Title',
                'status' => 'in_progress',
                'priority' => 'high',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original Title', // Title should remain unchanged
            'status' => 'in_progress', // Status should be updated
            'priority' => 'medium', // Priority should remain unchanged
        ]);
    }

    /** @test */
    public function assignee_can_transition_to_any_valid_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $assignee = User::factory()->create();
        $assignee->assignRole('employee');
        $assignee->role = 'employee';
        $assignee->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);
        $workspace->users()->attach($assignee->id, ['role' => 'employee']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $admin->id,
            'assignee_id' => $assignee->id,
            'status' => 'pending',
        ]);

        // Transition from pending to in_progress
        $response = $this->actingAs($assignee, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'in_progress']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);

        // Transition from in_progress to review
        $response = $this->actingAs($assignee, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'review']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'review']);

        // Transition from review to done
        $response = $this->actingAs($assignee, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'done']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }

    /** @test */
    public function assignee_gets_422_when_sending_extra_fields()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $assignee = User::factory()->create();
        $assignee->assignRole('employee');
        $assignee->role = 'employee';
        $assignee->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);
        $workspace->users()->attach($assignee->id, ['role' => 'employee']);

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'owner_id' => $admin->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $admin->id,
            'assignee_id' => $assignee->id,
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        // Try to update status with extra fields (e.g., urgency, priority)
        // Extra fields should be ignored, not rejected
        $response = $this->actingAs($assignee, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'status' => 'review',
                'urgency' => 'high',  // Extra field - should be ignored
                'priority' => 'low',  // Extra field - should be ignored
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'review',
            'priority' => 'medium',  // Priority should not change
        ]);
    }
}
