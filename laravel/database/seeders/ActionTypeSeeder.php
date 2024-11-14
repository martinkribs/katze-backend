<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActionType;

class ActionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actionTypes = [
            // Cat actions
            [
                'name' => 'cat_kill',
                'description' => 'Kill a player when alone (15m distance/out of sight)',
                'usage_limit' => 1, // Once per day
                'effect' => 'kill',
                'target_type' => 'single',
                'is_day_action' => false // Cats kill at night
            ],

            // Amor actions
            [
                'name' => 'create_love_pair',
                'description' => 'Choose two players to become a love pair',
                'usage_limit' => 1, // Once at game start
                'effect' => 'other',
                'target_type' => 'multiple',
                'is_day_action' => false // Before game starts
            ],
            [
                'name' => 'force_vote',
                'description' => 'Force a player to vote against a specific target',
                'usage_limit' => null, // Once before each vote
                'effect' => 'other',
                'target_type' => 'multiple',
                'is_day_action' => true
            ],

            // Witch actions
            [
                'name' => 'death_potion',
                'description' => 'Kill a player with death potion',
                'usage_limit' => 1,
                'effect' => 'kill',
                'target_type' => 'single',
                'is_day_action' => true
            ],
            [
                'name' => 'life_potion',
                'description' => 'Revive a player who died in voting',
                'usage_limit' => 1,
                'effect' => 'heal',
                'target_type' => 'single',
                'is_day_action' => true
            ],
            [
                'name' => 'healing_potion',
                'description' => 'Heal a player from HIV or gasoline',
                'usage_limit' => 1,
                'effect' => 'heal',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Detective actions
            [
                'name' => 'investigate_team',
                'description' => 'Investigate if two players are on the same team',
                'usage_limit' => null, // After each vote
                'effect' => 'reveal',
                'target_type' => 'multiple',
                'is_day_action' => true
            ],
            [
                'name' => 'force_information',
                'description' => 'Force a player to present specific information',
                'usage_limit' => null, // Before each vote
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Groupie actions
            [
                'name' => 'choose_idol',
                'description' => 'Choose an idol to follow',
                'usage_limit' => 1,
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => false // At start
            ],
            [
                'name' => 'groupie_kill',
                'description' => 'Kill as evil groupie after idol dies',
                'usage_limit' => null, // Based on chosen evil path
                'effect' => 'kill',
                'target_type' => 'single',
                'is_day_action' => false
            ],

            // Henker actions
            [
                'name' => 'receive_target',
                'description' => 'Receive new target after each vote',
                'usage_limit' => null,
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Mute actions
            [
                'name' => 'mute_player',
                'description' => 'Mute a player until next vote',
                'usage_limit' => null, // After each vote
                'effect' => 'block',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Oracle actions
            [
                'name' => 'send_message',
                'description' => 'Send encrypted message to player',
                'usage_limit' => null,
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => true
            ],
            [
                'name' => 'choose_next_oracle',
                'description' => 'Choose the next oracle',
                'usage_limit' => 1,
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Peter actions
            [
                'name' => 'steal_role',
                'description' => 'Steal another player\'s role',
                'usage_limit' => 1,
                'effect' => 'convert',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Pyromane actions
            [
                'name' => 'pour_gasoline',
                'description' => 'Cover a player in gasoline',
                'usage_limit' => null, // After Sunday votes
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => true
            ],
            [
                'name' => 'ignite',
                'description' => 'Kill all gasoline-covered players',
                'usage_limit' => 1,
                'effect' => 'kill',
                'target_type' => 'multiple',
                'is_day_action' => true
            ],
            [
                'name' => 'check_gasoline',
                'description' => 'Check if a player is covered in gasoline',
                'usage_limit' => null, // After Sunday votes (as warning system)
                'effect' => 'reveal',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Schlampe actions
            [
                'name' => 'sleep_with',
                'description' => 'Sleep with another player',
                'usage_limit' => null, // After each vote, max 2x per player
                'effect' => 'other',
                'target_type' => 'single',
                'is_day_action' => false
            ],
            [
                'name' => 'inherit_ability',
                'description' => 'Inherit dead host\'s abilities',
                'usage_limit' => null,
                'effect' => 'other',
                'target_type' => 'self',
                'is_day_action' => true
            ],

            // Seher actions
            [
                'name' => 'check_role',
                'description' => 'Check a player\'s role',
                'usage_limit' => null, // After each vote
                'effect' => 'reveal',
                'target_type' => 'single',
                'is_day_action' => true
            ],

            // Serienkiller actions
            [
                'name' => 'serial_kill',
                'description' => 'Kill a player (twice per week possible)',
                'usage_limit' => 2, // Per week
                'effect' => 'kill',
                'target_type' => 'single',
                'is_day_action' => false
            ],

            // Zivi actions
            [
                'name' => 'throw_holy_water',
                'description' => 'Throw holy water to identify/kill cat',
                'usage_limit' => 1,
                'effect' => 'kill',
                'target_type' => 'single',
                'is_day_action' => true
            ],
            [
                'name' => 'protect_player',
                'description' => 'Protect a player from physical damage',
                'usage_limit' => null, // After each vote
                'effect' => 'protect',
                'target_type' => 'single',
                'is_day_action' => true
            ],
        ];

        foreach ($actionTypes as $type) {
            ActionType::create($type);
        }
    }
}
