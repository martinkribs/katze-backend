<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActionType;

/**
 * Class ActionTypeSeeder
 *
 * Seeds the database with predefined action types.
 *
 * @package Database\Seeders
 */
class ActionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $actionTypes = [
            [
                'name' => 'Cat Kill',
                'description' => 'Cats can kill once per day.',
                'usage_limit' => 1,
                'effect' => 'kill',
                'target_type' => 'single',
            ],
            [
                'name' => 'Serial Killer Kill',
                'description' => 'Serial Killer can kill twice per week, even twice in one day.',
                'usage_limit' => 2,
                'effect' => 'kill',
                'target_type' => 'single',
            ],
            [
                'name' => 'Witch Heal',
                'description' => 'Witch can use a healing potion once per game.',
                'usage_limit' => 1,
                'effect' => 'heal',
                'target_type' => 'single',
            ],
            [
                'name' => 'Witch Kill',
                'description' => 'Witch can use a death potion once per game.',
                'usage_limit' => 1,
                'effect' => 'kill',
                'target_type' => 'single',
            ],
            [
                'name' => 'Seer Reveal',
                'description' => 'Seer can reveal the role of one player after each vote.',
                'usage_limit' => null,
                'effect' => 'reveal',
                'target_type' => 'single',
            ],
            [
                'name' => 'Detective Investigate',
                'description' => 'Detective can investigate if two players are on the same team after each vote.',
                'usage_limit' => null,
                'effect' => 'reveal',
                'target_type' => 'multiple',
            ],
            [
                'name' => 'Pyromane Douse',
                'description' => 'Pyromane can douse a player with gasoline after each Sunday vote.',
                'usage_limit' => null,
                'effect' => 'other',
                'target_type' => 'single',
            ],
            [
                'name' => 'Pyromane Ignite',
                'description' => 'Pyromane can ignite all doused players once per game.',
                'usage_limit' => 1,
                'effect' => 'kill',
                'target_type' => 'multiple',
            ],
            [
                'name' => 'Cupid Link',
                'description' => 'Cupid can link two players as lovers at the start of the game.',
                'usage_limit' => 1,
                'effect' => 'other',
                'target_type' => 'multiple',
            ],
            [
                'name' => 'Civilian Vote',
                'description' => 'Regular voting action for all players.',
                'usage_limit' => null,
                'effect' => 'other',
                'target_type' => 'single',
            ],
        ];

        foreach ($actionTypes as $actionType) {
            ActionType::create($actionType);
        }
    }
}
