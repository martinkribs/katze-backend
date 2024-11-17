<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ResultType;

class ResultTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resultTypes = [
            ['result' => 'death'],              // Player dies
            ['result' => 'protection'],         // Player is protected
            ['result' => 'infection'],          // Player gets HIV
            ['result' => 'role_reveal'],        // Role is revealed
            ['result' => 'team_reveal'],        // Team affiliation is revealed
            ['result' => 'conversion'],         // Role/team conversion (e.g., Verräter)
            ['result' => 'muted'],             // Player is muted
            ['result' => 'gasoline_covered'],   // Player is covered in gasoline
            ['result' => 'role_stolen'],        // Role is stolen by Peter
            ['result' => 'love_linked'],        // Players are linked by Amor
            ['result' => 'vote_manipulation'],  // Vote is manipulated
            ['result' => 'healing'],           // Player is healed
            ['result' => 'sleeping_with'],      // Schlampe sleeping effect
            ['result' => 'ability_inherited'],  // Schlampe inherits abilities
            ['result' => 'failed'],            // Action failed
            ['result' => 'blocked'],           // Action was blocked
        ];

        foreach ($resultTypes as $type) {
            ResultType::firstOrCreate(['result' => $type['result']], $type);
        }
    }
}
