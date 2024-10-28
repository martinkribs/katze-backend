<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Cat permissions
            ['role' => 'cat', 'permission' => 'kill'],
            ['role' => 'cat', 'permission' => 'vote'],
            ['role' => 'cat', 'permission' => 'view_night_chat'],
            
            // Witch permissions
            ['role' => 'witch', 'permission' => 'heal'],
            ['role' => 'witch', 'permission' => 'vote'],
            ['role' => 'witch', 'permission' => 'use_poison'],
            
            // Villager permissions
            ['role' => 'villager', 'permission' => 'vote'],
            
            // Seer permissions
            ['role' => 'seer', 'permission' => 'see_role'],
            ['role' => 'seer', 'permission' => 'vote'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'role' => $permission['role'],
                'permission' => $permission['permission'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
