<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\ActionType;
use App\Models\RoleActionType;

class RoleActionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles and action types
        $roles = Role::all()->keyBy('key');
        $actionTypes = ActionType::all()->keyBy('name');

        // Define role to action type mappings
        $roleActionTypes = [
            'cat' => ['cat_kill'],
            'amor' => ['create_love_pair', 'force_vote'],
            'witch' => ['death_potion', 'life_potion', 'healing_potion'],
            'detective' => ['investigate_team', 'force_information'],
            'groupie' => ['choose_idol', 'groupie_kill'],
            'executioner' => ['receive_target'],
            'mute' => ['mute_player'],
            'oracle' => ['send_message', 'choose_next_oracle'],
            'peter' => ['steal_role'],
            'pyro' => ['pour_gasoline', 'ignite', 'check_gasoline'],
            'slut' => ['sleep_with', 'inherit_ability'],
            'seer' => ['check_role'],
            'serial_killer' => ['serial_kill'],
            'guard' => ['throw_holy_water', 'protect_player'],
        ];

        // Create the relationships
        foreach ($roleActionTypes as $roleKey => $actionTypeNames) {
            if (!isset($roles[$roleKey])) {
                continue; // Skip if role doesn't exist
            }

            foreach ($actionTypeNames as $actionTypeName) {
                if (!isset($actionTypes[$actionTypeName])) {
                    continue; // Skip if action type doesn't exist
                }

                // Create the relationship if it doesn't exist
                RoleActionType::firstOrCreate([
                    'role_id' => $roles[$roleKey]->id,
                    'action_type_id' => $actionTypes[$actionTypeName]->id
                ]);
            }
        }
    }
}
