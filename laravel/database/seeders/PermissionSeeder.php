<?php

namespace Database\Seeders;

use App\Models\ActionType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles and action types
        $cat = Role::where('name', 'cat')->first();
        $witch = Role::where('name', 'witch')->first();
        $seer = Role::where('name', 'seer')->first();
        $detective = Role::where('name', 'detective')->first();
        $villager = Role::where('name', 'villager')->first();

        $catKill = ActionType::where('name', 'Cat Kill')->first();
        $civilianVote = ActionType::where('name', 'Civilian Vote')->first();
        $witchHeal = ActionType::where('name', 'Witch Heal')->first();
        $witchKill = ActionType::where('name', 'Witch Kill')->first();
        $seerReveal = ActionType::where('name', 'Seer Reveal')->first();
        $detectiveInvestigate = ActionType::where('name', 'Detective Investigate')->first();

        // Assign permissions
        // Cats can kill at night and vote during the day
        $cat->actionTypes()->attach([$catKill->id, $civilianVote->id]);

        // Witch can heal once, kill once, and vote
        $witch->actionTypes()->attach([
            $witchHeal->id,
            $witchKill->id,
            $civilianVote->id
        ]);

        // Seer can reveal roles and vote
        $seer->actionTypes()->attach([$seerReveal->id, $civilianVote->id]);

        // Detective can investigate and vote
        $detective->actionTypes()->attach([$detectiveInvestigate->id, $civilianVote->id]);

        // Villagers can only vote
        $villager->actionTypes()->attach([$civilianVote->id]);
    }
}
