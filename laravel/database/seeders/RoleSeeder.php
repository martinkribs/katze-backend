<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Villager',
                'description' => 'A regular villager. Your goal is to find and eliminate the Cats.',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'name' => 'Cat',
                'description' => 'Each night, work with other Cats to eliminate a villager.',
                'team' => 'cats',
                'can_use_night_action' => true,
            ],
            [
                'name' => 'Guardian',
                'description' => 'Each night, choose a player to protect from the Cats.',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'name' => 'Seer',
                'description' => 'Each night, investigate one player to learn if they are a Cat.',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'name' => 'Doctor',
                'description' => 'Once per game, you can revive an eliminated player during the night.',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'name' => 'Medium',
                'description' => 'When eliminated, you can continue to communicate with living players through visions.',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'name' => 'Bodyguard',
                'description' => 'Once per game, you can choose to protect yourself from elimination during the night.',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'name' => 'Jester',
                'description' => 'You win if you get eliminated by village vote. If eliminated by Cats, you join their team.',
                'team' => 'neutral',
                'can_use_night_action' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
