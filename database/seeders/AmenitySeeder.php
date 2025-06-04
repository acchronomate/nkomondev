<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            // Équipements pour hébergements
            [
                'name' => ['fr' => 'Wi-Fi gratuit', 'en' => 'Free Wi-Fi'],
                'icon' => 'wifi',
                'type' => 'accommodation',
                'order' => 1,
            ],
            [
                'name' => ['fr' => 'Parking gratuit', 'en' => 'Free parking'],
                'icon' => 'parking',
                'type' => 'accommodation',
                'order' => 2,
            ],
            [
                'name' => ['fr' => 'Piscine', 'en' => 'Swimming pool'],
                'icon' => 'pool',
                'type' => 'accommodation',
                'order' => 3,
            ],
            [
                'name' => ['fr' => 'Restaurant', 'en' => 'Restaurant'],
                'icon' => 'restaurant',
                'type' => 'accommodation',
                'order' => 4,
            ],
            [
                'name' => ['fr' => 'Bar', 'en' => 'Bar'],
                'icon' => 'bar',
                'type' => 'accommodation',
                'order' => 5,
            ],
            [
                'name' => ['fr' => 'Salle de sport', 'en' => 'Gym'],
                'icon' => 'gym',
                'type' => 'accommodation',
                'order' => 6,
            ],
            [
                'name' => ['fr' => 'Spa', 'en' => 'Spa'],
                'icon' => 'spa',
                'type' => 'accommodation',
                'order' => 7,
            ],
            [
                'name' => ['fr' => 'Réception 24h/24', 'en' => '24-hour reception'],
                'icon' => 'reception_24h',
                'type' => 'accommodation',
                'order' => 8,
            ],
            [
                'name' => ['fr' => 'Service de blanchisserie', 'en' => 'Laundry service'],
                'icon' => 'laundry',
                'type' => 'accommodation',
                'order' => 9,
            ],
            [
                'name' => ['fr' => 'Navette aéroport', 'en' => 'Airport shuttle'],
                'icon' => 'airport_shuttle',
                'type' => 'accommodation',
                'order' => 10,
            ],
            [
                'name' => ['fr' => 'Animaux acceptés', 'en' => 'Pets allowed'],
                'icon' => 'pets',
                'type' => 'accommodation',
                'order' => 11,
            ],
            [
                'name' => ['fr' => 'Accès handicapés', 'en' => 'Disabled access'],
                'icon' => 'accessible',
                'type' => 'accommodation',
                'order' => 12,
            ],

            // Équipements pour chambres
            [
                'name' => ['fr' => 'Climatisation', 'en' => 'Air conditioning'],
                'icon' => 'air_conditioning',
                'type' => 'room',
                'order' => 1,
            ],
            [
                'name' => ['fr' => 'Télévision', 'en' => 'Television'],
                'icon' => 'tv',
                'type' => 'room',
                'order' => 2,
            ],
            [
                'name' => ['fr' => 'Mini-bar', 'en' => 'Mini-bar'],
                'icon' => 'minibar',
                'type' => 'room',
                'order' => 3,
            ],
            [
                'name' => ['fr' => 'Coffre-fort', 'en' => 'Safe'],
                'icon' => 'safe',
                'type' => 'room',
                'order' => 4,
            ],
            [
                'name' => ['fr' => 'Sèche-cheveux', 'en' => 'Hair dryer'],
                'icon' => 'hair_dryer',
                'type' => 'room',
                'order' => 5,
            ],
            [
                'name' => ['fr' => 'Bureau', 'en' => 'Desk'],
                'icon' => 'desk',
                'type' => 'room',
                'order' => 6,
            ],
            [
                'name' => ['fr' => 'Balcon', 'en' => 'Balcony'],
                'icon' => 'balcony',
                'type' => 'room',
                'order' => 7,
            ],
            [
                'name' => ['fr' => 'Vue mer', 'en' => 'Sea view'],
                'icon' => 'sea_view',
                'type' => 'room',
                'order' => 8,
            ],
            [
                'name' => ['fr' => 'Machine à café', 'en' => 'Coffee machine'],
                'icon' => 'coffee',
                'type' => 'room',
                'order' => 9,
            ],
            [
                'name' => ['fr' => 'Réfrigérateur', 'en' => 'Refrigerator'],
                'icon' => 'fridge',
                'type' => 'room',
                'order' => 10,
            ],

            // Équipements mixtes (both)
            [
                'name' => ['fr' => 'Non-fumeur', 'en' => 'Non-smoking'],
                'icon' => 'no_smoking',
                'type' => 'both',
                'order' => 1,
            ],
            [
                'name' => ['fr' => 'Service de chambre', 'en' => 'Room service'],
                'icon' => 'room_service',
                'type' => 'both',
                'order' => 2,
            ],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['icon' => $amenity['icon']],
                $amenity
            );
        }

        $this->command->info('Amenities seeded successfully!');
    }
}
