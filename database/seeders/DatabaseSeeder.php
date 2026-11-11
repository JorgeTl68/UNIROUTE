<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Datos de desarrollo y demo. Se puede correr varias veces sin duplicar.
     */
    public function run(): void
    {
        $this->call([
            CampusSeeder::class,
            TransitNetworkSeeder::class,
            DemoUserSeeder::class,
            FleetSeeder::class,
        ]);
    }
}
