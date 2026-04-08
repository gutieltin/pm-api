<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

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
    public function non_admin_cannot_access_user_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }
}
