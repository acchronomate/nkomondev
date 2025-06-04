<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Utiliser la méthode initializeDefaults du modèle Setting
        Setting::initializeDefaults();

        $this->command->info('Settings initialized successfully!');
    }
}
