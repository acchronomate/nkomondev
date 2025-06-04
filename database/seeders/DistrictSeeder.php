<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $districts = [
            // Cotonou, Bénin
            'Cotonou' => [
                ['name' => ['fr' => 'Akpakpa', 'en' => 'Akpakpa']],
                ['name' => ['fr' => 'Cadjèhoun', 'en' => 'Cadjehoun']],
                ['name' => ['fr' => 'Ganhi', 'en' => 'Ganhi']],
                ['name' => ['fr' => 'Haie Vive', 'en' => 'Haie Vive']],
                ['name' => ['fr' => 'Jéricho', 'en' => 'Jericho']],
                ['name' => ['fr' => 'Gbégamey', 'en' => 'Gbegamey']],
                ['name' => ['fr' => 'Fidjrossè', 'en' => 'Fidjrosse']],
                ['name' => ['fr' => 'Sainte Rita', 'en' => 'Sainte Rita']],
                ['name' => ['fr' => 'Vèdoko', 'en' => 'Vedoko']],
                ['name' => ['fr' => 'Agla', 'en' => 'Agla']],
                ['name' => ['fr' => 'Mènontin', 'en' => 'Menontin']],
                ['name' => ['fr' => 'Zogbo', 'en' => 'Zogbo']],
            ],

            // Porto-Novo, Bénin
            'Porto-Novo' => [
                ['name' => ['fr' => 'Adjarra', 'en' => 'Adjarra']],
                ['name' => ['fr' => 'Avrankou', 'en' => 'Avrankou']],
                ['name' => ['fr' => 'Akron', 'en' => 'Akron']],
                ['name' => ['fr' => 'Djassin', 'en' => 'Djassin']],
                ['name' => ['fr' => 'Louho', 'en' => 'Louho']],
            ],

            // Abomey-Calavi, Bénin
            'Abomey-Calavi' => [
                ['name' => ['fr' => 'Calavi', 'en' => 'Calavi']],
                ['name' => ['fr' => 'Godomey', 'en' => 'Godomey']],
                ['name' => ['fr' => 'Akassato', 'en' => 'Akassato']],
                ['name' => ['fr' => 'Zogbadjè', 'en' => 'Zogbadje']],
                ['name' => ['fr' => 'Zinvié', 'en' => 'Zinvié']],
                ['name' => ['fr' => 'Godomey', 'en' => 'Godomey']],
                ['name' => ['fr' => 'Hêvié', 'en' => 'Hêvié']],
                ['name' => ['fr' => 'Ouèdo', 'en' => 'Ouèdo']],
                ['name' => ['fr' => 'Togba', 'en' => 'Togba']],
                ['name' => ['fr' => 'Golo-Djigbé', 'en' => 'Golo-Djigbé']],
            ],

            // Parakou, Bénin
            'Parakou' => [
                ['name' => ['fr' => 'Alaga', 'en' => 'Alaga']],
                ['name' => ['fr' => 'Camp Adagbe', 'en' => 'Camp Adagbe']],
                ['name' => ['fr' => 'Tourou', 'en' => 'Tourou']],
                ['name' => ['fr' => 'Madina', 'en' => 'Madina']],
                ['name' => ['fr' => 'Banikanni', 'en' => 'Banikanni']],
                ['name' => ['fr' => 'Albarika', 'en' => 'Albarika']],
                ['name' => ['fr' => 'Depot', 'en' => 'Depot']],
                ['name' => ['fr' => 'Titirou', 'en' => 'Titirou']],
                ['name' => ['fr' => 'Tourou', 'en' => 'Tourou']],
            ],

            // Lomé, Togo
            'Lomé' => [
                ['name' => ['fr' => 'Bè', 'en' => 'Be']],
                ['name' => ['fr' => 'Tokoin', 'en' => 'Tokoin']],
                ['name' => ['fr' => 'Nyékonakpoè', 'en' => 'Nyekonakpoe']],
                ['name' => ['fr' => 'Agbalépédogan', 'en' => 'Agbalepedogan']],
                ['name' => ['fr' => 'Kodjoviakopé', 'en' => 'Kodjoviakope']],
                ['name' => ['fr' => 'Hédzranawoé', 'en' => 'Hedzranawoe']],
            ],

            // Abidjan, Côte d'Ivoire
            'Abidjan' => [
                ['name' => ['fr' => 'Cocody', 'en' => 'Cocody']],
                ['name' => ['fr' => 'Plateau', 'en' => 'Plateau']],
                ['name' => ['fr' => 'Marcory', 'en' => 'Marcory']],
                ['name' => ['fr' => 'Yopougon', 'en' => 'Yopougon']],
                ['name' => ['fr' => 'Treichville', 'en' => 'Treichville']],
                ['name' => ['fr' => 'Abobo', 'en' => 'Abobo']],
                ['name' => ['fr' => 'Adjamé', 'en' => 'Adjame']],
                ['name' => ['fr' => 'Port-Bouët', 'en' => 'Port-Bouet']],
                ['name' => ['fr' => 'Koumassi', 'en' => 'Koumassi']],
            ],

            // Dakar, Sénégal
            'Dakar' => [
                ['name' => ['fr' => 'Dakar-Plateau', 'en' => 'Dakar-Plateau']],
                ['name' => ['fr' => 'Médina', 'en' => 'Medina']],
                ['name' => ['fr' => 'Grand Dakar', 'en' => 'Grand Dakar']],
                ['name' => ['fr' => 'Almadies', 'en' => 'Almadies']],
                ['name' => ['fr' => 'Parcelles Assainies', 'en' => 'Parcelles Assainies']],
                ['name' => ['fr' => 'Sicap', 'en' => 'Sicap']],
                ['name' => ['fr' => 'HLM', 'en' => 'HLM']],
                ['name' => ['fr' => 'Mermoz', 'en' => 'Mermoz']],
            ],
        ];

        foreach ($districts as $cityName => $cityDistricts) {
            $city = City::whereJsonContains('name->fr', $cityName)->first();

            if (!$city) {
                continue;
            }

            foreach ($cityDistricts as $districtData) {
                $districtData['city_id'] = $city->id;

                District::updateOrCreate(
                    [
                        'city_id' => $city->id,
                        'name->fr' => $districtData['name']['fr'],
                    ],
                    $districtData
                );
            }
        }

        $this->command->info('Districts seeded successfully!');
    }
}
