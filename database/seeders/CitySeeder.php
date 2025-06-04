<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            // Bénin
            'BJ' => [
                ['name' => ['fr' => 'Cotonou', 'en' => 'Cotonou'], 'is_popular' => true, 'latitude' => 6.3654, 'longitude' => 2.4183],
                ['name' => ['fr' => 'Porto-Novo', 'en' => 'Porto-Novo'], 'is_popular' => true, 'latitude' => 6.4969, 'longitude' => 2.6289],
                ['name' => ['fr' => 'Abomey-Calavi', 'en' => 'Abomey-Calavi'], 'is_popular' => true, 'latitude' => 6.4500, 'longitude' => 2.3556],
                ['name' => ['fr' => 'Parakou', 'en' => 'Parakou'], 'is_popular' => true, 'latitude' => 9.3370, 'longitude' => 2.6303],
                ['name' => ['fr' => 'Djougou', 'en' => 'Djougou'], 'is_popular' => false, 'latitude' => 9.7089, 'longitude' => 1.6669],
                ['name' => ['fr' => 'Bohicon', 'en' => 'Bohicon'], 'is_popular' => false, 'latitude' => 7.1783, 'longitude' => 2.0667],
                ['name' => ['fr' => 'Natitingou', 'en' => 'Natitingou'], 'is_popular' => false, 'latitude' => 10.3040, 'longitude' => 1.3796],
                ['name' => ['fr' => 'Ouidah', 'en' => 'Ouidah'], 'is_popular' => true, 'latitude' => 6.3633, 'longitude' => 2.0857],
            ],

            // Togo
            'TG' => [
                ['name' => ['fr' => 'Lomé', 'en' => 'Lome'], 'is_popular' => true, 'latitude' => 6.1375, 'longitude' => 1.2123],
                ['name' => ['fr' => 'Sokodé', 'en' => 'Sokode'], 'is_popular' => false, 'latitude' => 8.9837, 'longitude' => 1.1333],
                ['name' => ['fr' => 'Kara', 'en' => 'Kara'], 'is_popular' => false, 'latitude' => 9.5511, 'longitude' => 1.1861],
                ['name' => ['fr' => 'Kpalimé', 'en' => 'Kpalime'], 'is_popular' => false, 'latitude' => 6.9000, 'longitude' => 0.6333],
            ],

            // Côte d'Ivoire
            'CI' => [
                ['name' => ['fr' => 'Abidjan', 'en' => 'Abidjan'], 'is_popular' => true, 'latitude' => 5.3600, 'longitude' => -4.0083],
                ['name' => ['fr' => 'Yamoussoukro', 'en' => 'Yamoussoukro'], 'is_popular' => true, 'latitude' => 6.8276, 'longitude' => -5.2893],
                ['name' => ['fr' => 'Bouaké', 'en' => 'Bouake'], 'is_popular' => false, 'latitude' => 7.6937, 'longitude' => -5.0301],
                ['name' => ['fr' => 'San-Pédro', 'en' => 'San-Pedro'], 'is_popular' => false, 'latitude' => 4.7485, 'longitude' => -6.6363],
            ],

            // Burkina Faso
            'BF' => [
                ['name' => ['fr' => 'Ouagadougou', 'en' => 'Ouagadougou'], 'is_popular' => true, 'latitude' => 12.3714, 'longitude' => -1.5197],
                ['name' => ['fr' => 'Bobo-Dioulasso', 'en' => 'Bobo-Dioulasso'], 'is_popular' => true, 'latitude' => 11.1771, 'longitude' => -4.2979],
            ],

            // Sénégal
            'SN' => [
                ['name' => ['fr' => 'Dakar', 'en' => 'Dakar'], 'is_popular' => true, 'latitude' => 14.7167, 'longitude' => -17.4677],
                ['name' => ['fr' => 'Saint-Louis', 'en' => 'Saint-Louis'], 'is_popular' => false, 'latitude' => 16.0179, 'longitude' => -16.4896],
                ['name' => ['fr' => 'Thiès', 'en' => 'Thies'], 'is_popular' => false, 'latitude' => 14.7889, 'longitude' => -16.9260],
            ],
        ];

        foreach ($cities as $countryCode => $countryCities) {
            $country = Country::where('code', $countryCode)->first();

            if (!$country) {
                continue;
            }

            foreach ($countryCities as $cityData) {
                $cityData['country_id'] = $country->id;

                City::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name->fr' => $cityData['name']['fr'],
                    ],
                    $cityData
                );
            }
        }

        $this->command->info('Cities seeded successfully!');
    }
}
