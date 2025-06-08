<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Données de référence
            CurrencySeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            DistrictSeeder::class,
            AmenitySeeder::class,
            SettingSeeder::class,

            // Utilisateurs de test
            UserSeeder::class,

            // Données de test (optionnel)
            // AccommodationSeeder::class,
            // BookingSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Database seeded successfully!');
        $this->command->info('===========================================');
        $this->command->info('');
        $this->command->info('Admin credentials:');
        $this->command->info('Email: admin@nkomon.com');
        $this->command->info('Password: password');
        $this->command->info('');
        $this->command->info('Test host credentials:');
        $this->command->info('Email: hotel.golden@example.com');
        $this->command->info('Password: password');
        $this->command->info('');
        $this->command->info('Test client credentials:');
        $this->command->info('Email: client1@example.com');
        $this->command->info('Password: password');
        $this->command->info('===========================================');
    }
}
