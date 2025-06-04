<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CurrencySeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            DistrictSeeder::class,
            UserSeeder::class,
            AmenitySeeder::class,
        ]);
    }
}
