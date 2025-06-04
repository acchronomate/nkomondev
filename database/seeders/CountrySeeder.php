<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => 'BJ',
                'name' => ['fr' => 'Bénin', 'en' => 'Benin'],
                'phone_code' => '+229',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'TG',
                'name' => ['fr' => 'Togo', 'en' => 'Togo'],
                'phone_code' => '+228',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'BF',
                'name' => ['fr' => 'Burkina Faso', 'en' => 'Burkina Faso'],
                'phone_code' => '+226',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'CI',
                'name' => ['fr' => 'Côte d\'Ivoire', 'en' => 'Ivory Coast'],
                'phone_code' => '+225',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'ML',
                'name' => ['fr' => 'Mali', 'en' => 'Mali'],
                'phone_code' => '+223',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'NE',
                'name' => ['fr' => 'Niger', 'en' => 'Niger'],
                'phone_code' => '+227',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'SN',
                'name' => ['fr' => 'Sénégal', 'en' => 'Senegal'],
                'phone_code' => '+221',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'GW',
                'name' => ['fr' => 'Guinée-Bissau', 'en' => 'Guinea-Bissau'],
                'phone_code' => '+245',
                'currency_code' => 'XOF',
                'is_active' => true,
            ],
            [
                'code' => 'NG',
                'name' => ['fr' => 'Nigeria', 'en' => 'Nigeria'],
                'phone_code' => '+234',
                'currency_code' => 'NGN',
                'is_active' => true,
            ],
            [
                'code' => 'GH',
                'name' => ['fr' => 'Ghana', 'en' => 'Ghana'],
                'phone_code' => '+233',
                'currency_code' => 'GHS',
                'is_active' => true,
            ],
            [
                'code' => 'CM',
                'name' => ['fr' => 'Cameroun', 'en' => 'Cameroon'],
                'phone_code' => '+237',
                'currency_code' => 'XAF',
                'is_active' => true,
            ],
            [
                'code' => 'GA',
                'name' => ['fr' => 'Gabon', 'en' => 'Gabon'],
                'phone_code' => '+241',
                'currency_code' => 'XAF',
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country
            );
        }

        $this->command->info('Countries seeded successfully!');
    }
}
