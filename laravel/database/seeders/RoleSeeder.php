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
                'key' => 'villager',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'cat',
                'team' => 'cats',
                'can_use_night_action' => true,
            ],
            [
                'key' => 'serial_killer',
                'team' => 'serial_killer',
                'can_use_night_action' => true,
            ],
            [
                'key' => 'amor',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'key' => 'detective',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'witch',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'key' => 'hiv',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'doctor',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'mute',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'peter',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'pyro',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'slut',
                'team' => 'villagers',
                'can_use_night_action' => true,
            ],
            [
                'key' => 'seer',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'traitor',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'guard',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'groupie',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'executioner',
                'team' => 'neutral',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'jester',
                'team' => 'neutral',
                'can_use_night_action' => false,
            ],
            [
                'key' => 'king',
                'team' => 'villagers',
                'can_use_night_action' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['key' => $role['key']], // Search criteria
                [
                    'name' => $role['key'],
                    'description' => $role['key'],
                    'team' => $role['team'],
                    'can_use_night_action' => $role['can_use_night_action']
                ]
            );
        }
    }
}
