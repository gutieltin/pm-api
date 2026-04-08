<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Task;

class CorporateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
{
    // 1. Create a Workspace Owner
    $owner = User::factory()->create([
        'name' => 'Admin Boss',
        'email' => 'admin@firm.com',
        'role' => 'admin',
    ]);

    // 2. Create a Workspace
    $workspace = Workspace::create([
        'name' => 'Main Headquarters',
        'owner_id' => $owner->id,
        'slug' => 'main-hq',
    ]);

    // 3. Create 5 Employees to assign tasks to
    $employees = User::factory(5)->create(['role' => 'employee']);

    // 4. Create 3 Projects inside that Workspace
    Project::factory(3)
        ->for($workspace)
        ->has(
            // 5. Create 10 Tasks for each Project
            Task::factory(10)->state(function (array $attributes, Project $project) use ($owner, $employees) {
                return [
                    'creator_id' => $owner->id,
                    'assignee_id' => $employees->random()->id,
                ];
            })
        )
        ->create([
            'owner_id' => $owner->id, // Set the owner of the project to the workspace owner
        ]);
}
}
