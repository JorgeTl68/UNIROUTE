<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

/**
 * PROVISIONAL: coordenadas aproximadas hasta tener los datos reales de S0-05.
 */
class CampusSeeder extends Seeder
{
    public const string CENTRAL = 'campus-central-saltillo';

    public const string RAMOS_ARIZPE = 'campus-ramos-arizpe';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campuses = [
            ['slug' => self::CENTRAL, 'name' => 'Campus Central Saltillo', 'latitude' => 25.4262, 'longitude' => -100.9925],
            ['slug' => self::RAMOS_ARIZPE, 'name' => 'Campus Ramos Arizpe', 'latitude' => 25.5400, 'longitude' => -100.9470],
        ];

        foreach ($campuses as $campus) {
            Campus::updateOrCreate(['slug' => $campus['slug']], $campus);
        }
    }
}
