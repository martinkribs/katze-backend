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
                'name' => 'cat',
            ],
            [
                'name' => 'witch',
            ],
            [
                'name' => 'seer',
            ],
            [
                'name' => 'detective',
            ],
            [
                'name' => 'villager',
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
