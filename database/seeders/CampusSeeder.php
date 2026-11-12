<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

/**
 * La Universidad Tecnológica de Coahuila, en Av. Industrial Metalúrgica
 * (Ramos Arizpe). Es el destino de todas las rutas de TECSA.
 */
class CampusSeeder extends Seeder
{
    public const string UTC = 'utc';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Campus::updateOrCreate(['slug' => self::UTC], [
            'name' => 'UTC Ramos Arizpe',
            'latitude' => 25.5576848,
            'longitude' => -100.9356719,
        ]);
    }
}
