<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use  App\Models\Workspace;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'=>fake()->company() . 'Implementation',
            'owner_id' => \App\Models\User::factory(),
            'description'=>fake()->sentence(),
            'status'=>fake()->randomElement(['active', 'completed', 'archived']),
            'workspace_id' => Workspace::factory(),
        ];
    }
}
