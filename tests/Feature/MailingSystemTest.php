<?php

namespace Tests\Feature;

use App\Events\TaskUpdated;
use App\Events\UserCreated;
use App\Mail\TaskProgressMail;
use App\Mail\UserCreatedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        Mail::fake();
        // Use sync queue to process jobs immediately in tests
        config(['queue.default' => 'sync']);
    }

    /** @test */
    public function user_created_email_is_sent_on_registration()
    {
        $response = $this->post('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'workspace_name' => 'My Workspace',
        ]);

        $response->assertStatus(201);

        Mail::assertSent(UserCreatedMail::class, function ($mail) {
            return $mail->hasTo('john@example.com') &&
                   str_contains($mail->subject, 'Welcome to Project Management');
        });
    }

    /** @test */
    public function user_created_email_contains_correct_data()
    {
        $this->post('/api/v1/register', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'workspace_name' => 'Test Workspace',
        ]);

        Mail::assertSent(UserCreatedMail::class, function ($mail) {
            return $mail->user->name === 'Jane Smith' &&
                   $mail->user->email === 'jane@example.com' &&
                   is_null($mail->temporaryPassword);
        });
    }

    /** @test */
    public function task_progress_email_is_sent_on_task_update()
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

        $assignee = User::factory()->create();
        $workspace->users()->attach($assignee->id, ['role' => 'employee']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
        ]);

        // Update the task to trigger the email
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200);

        Mail::assertSent(TaskProgressMail::class, function ($mail) use ($assignee) {
            return $mail->hasTo($assignee->email) &&
                   str_contains($mail->subject, 'Task Progress Update');
        });
    }

    /** @test */
    public function task_progress_email_contains_correct_task_data()
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

        $assignee = User::factory()->create();
        $workspace->users()->attach($assignee->id, ['role' => 'employee']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
            'title' => 'Complete API Documentation',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'status' => 'in_progress',
            ]);

        Mail::assertSent(TaskProgressMail::class, function ($mail) use ($task, $assignee) {
            return $mail->task->title === 'Complete API Documentation' &&
                   $mail->task->status === 'in_progress' &&
                   $mail->task->priority === 'high' &&
                   $mail->task->assignee->id === $assignee->id;
        });
    }

    /** @test */
    public function new_workspace_member_receives_welcome_email()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->id}/members", [
                'email' => 'newmember@example.com',
                'name' => 'New Member',
                'role' => 'employee',
            ]);

        $response->assertStatus(201);

        Mail::assertSent(UserCreatedMail::class, function ($mail) {
            return $mail->hasTo('newmember@example.com') &&
                   ! is_null($mail->temporaryPassword);
        });
    }

    /** @test */
    public function mail_from_address_is_configured()
    {
        $this->post('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'workspace_name' => 'Test Workspace',
        ]);

        Mail::assertSent(UserCreatedMail::class, function ($mail) {
            $envelope = $mail->envelope();
            return ! empty($envelope->from);
        });
    }

    /** @test */
    public function existing_user_is_not_sent_welcome_email_when_added_to_workspace()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin']);

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->id}/members", [
                'email' => 'existing@example.com',
                'role' => 'employee',
            ]);

        Mail::assertNotSent(UserCreatedMail::class);
    }
}
