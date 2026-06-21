<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /** @test */
    public function admin_can_retrieve_list_of_all_users()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->role = 'admin';
        $admin->save();

        // create a few non-admin users
        $users = User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
        foreach ($users as $user) {
            $response->assertJsonFragment(['id' => $user->id, 'email' => $user->email]);
        }
    }

    /** @test */
    public function user_without_workspace_cannot_access_user_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function user_with_workspace_can_access_users_in_their_workspaces()
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        $workspace->users()->attach($user->id, ['role' => 'member']);

        // Create another user in the same workspace
        $otherUser = User::factory()->create();
        $workspace->users()->attach($otherUser->id, ['role' => 'member']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
        $response->assertJsonFragment(['id' => $otherUser->id]);
    }

    /** @test */
    public function user_creation_assigns_correct_role()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertEquals('admin', $user->role);
        $user->delete();
    }
}
