<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VehicleAmenity;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Unidades y conductores.
 *
 * PROVISIONAL: modelos y nombres tomados de los mockups. Se reemplazan con el
 * inventario real que levanta Adaia en S0-06.
 */
class FleetSeeder extends Seeder
{
    public const string DRIVER_EMAIL = 'conductor@uniroute.test';

    /**
     * @var list<array{code: string, plate: string, model: string, capacity: int, status: VehicleStatus, amenities: list<VehicleAmenity>}>
     */
    private const array VEHICLES = [
        ['code' => 'BUS-UTC-01', 'plate' => 'UTC-101-A', 'model' => 'Mercedes Sprinter', 'capacity' => 40, 'status' => VehicleStatus::InService, 'amenities' => [VehicleAmenity::AirConditioning, VehicleAmenity::Wifi]],
        ['code' => 'BUS-UTC-02', 'plate' => 'UTC-102-A', 'model' => 'Volvo Access', 'capacity' => 40, 'status' => VehicleStatus::InService, 'amenities' => [VehicleAmenity::AirConditioning, VehicleAmenity::AccessibleRamp]],
        ['code' => 'BUS-UTC-03', 'plate' => 'UTC-103-A', 'model' => 'Mercedes Sprinter', 'capacity' => 40, 'status' => VehicleStatus::InService, 'amenities' => [VehicleAmenity::AirConditioning]],
        ['code' => 'BUS-UTC-04', 'plate' => 'UTC-104-A', 'model' => 'Marcopolo Boxer', 'capacity' => 45, 'status' => VehicleStatus::Standby, 'amenities' => [VehicleAmenity::Wifi]],
        ['code' => 'BUS-UTC-05', 'plate' => 'UTC-105-A', 'model' => 'Volvo Access', 'capacity' => 40, 'status' => VehicleStatus::Maintenance, 'amenities' => [VehicleAmenity::AccessibleRamp]],
        ['code' => 'BUS-UTC-06', 'plate' => 'UTC-106-A', 'model' => 'Marcopolo Boxer', 'capacity' => 45, 'status' => VehicleStatus::InService, 'amenities' => [VehicleAmenity::AirConditioning, VehicleAmenity::Wifi, VehicleAmenity::AccessibleRamp]],
    ];

    /**
     * @var list<array{email: string, name: string, license_number: string, hired_at: string, rating: float}>
     */
    private const array DRIVERS = [
        ['email' => self::DRIVER_EMAIL, 'name' => 'Manuel González', 'license_number' => 'COA-100201', 'hired_at' => '2020-02-10', 'rating' => 4.9],
        ['email' => 'laura.rivas@uniroute.test', 'name' => 'Laura Rivas', 'license_number' => 'COA-100202', 'hired_at' => '2021-08-16', 'rating' => 4.7],
        ['email' => 'javier.castillo@uniroute.test', 'name' => 'Javier Castillo', 'license_number' => 'COA-100203', 'hired_at' => '2019-05-06', 'rating' => 4.8],
        ['email' => 'esteban.peralta@uniroute.test', 'name' => 'Esteban Peralta', 'license_number' => 'COA-100204', 'hired_at' => '2023-01-09', 'rating' => 4.6],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::VEHICLES as $vehicle) {
            Vehicle::updateOrCreate(['code' => $vehicle['code']], [
                ...$vehicle,
                'amenities' => array_map(fn (VehicleAmenity $amenity): string => $amenity->value, $vehicle['amenities']),
            ]);
        }

        foreach (self::DRIVERS as $driver) {
            $user = DemoUserSeeder::upsert($driver['email'], $driver['name'], UserRole::Driver);

            $user->driverProfile()->updateOrCreate([], [
                'license_number' => $driver['license_number'],
                'hired_at' => $driver['hired_at'],
                'rating' => $driver['rating'],
            ]);
        }
    }
}
