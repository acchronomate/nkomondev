<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCurrency = Currency::where('code', 'XOF')->first();

        // Créer un administrateur
        User::updateOrCreate(
            ['email' => 'admin@'.config('app.domain')],
            [
                'name' => 'Administrateur NKOMON',
                'password' => Hash::make('password'),
                'type' => 'admin',
                'phone' => '+229 97 00 00 00',
                'locale' => 'fr',
                'preferred_currency_id' => $defaultCurrency->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Créer quelques hébergeurs de test
        $hosts = [
            [
                'email' => 'hotel.golden@example.com',
                'name' => 'Hôtel Golden Tulip',
                'phone' => '+229 21 31 45 45',
                'city' => 'Cotonou',
                'country' => 'Bénin',
            ],
            [
                'email' => 'hotel.azalai@example.com',
                'name' => 'Azalaï Hôtel',
                'phone' => '+229 21 30 01 00',
                'city' => 'Cotonou',
                'country' => 'Bénin',
            ],
            [
                'email' => 'hotel.benin-royal@example.com',
                'name' => 'Bénin Royal Hôtel',
                'phone' => '+229 21 30 01 24',
                'city' => 'Cotonou',
                'country' => 'Bénin',
            ],
        ];

        foreach ($hosts as $hostData) {
            User::updateOrCreate(
                ['email' => $hostData['email']],
                [
                    'name' => $hostData['name'],
                    'password' => Hash::make('password'),
                    'type' => 'host',
                    'phone' => $hostData['phone'],
                    'city_id' => DB::table('cities')
                        ->where('name', $hostData['city'])
                        ->value('id'),
                    'country_id' => DB::table('countries')
                        ->where('name->fr', $hostData['country'])
                        ->value('id'),
                    'locale' => 'fr',
                    'preferred_currency_id' => $defaultCurrency->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // Créer quelques clients de test
        $clients = [
            [
                'email' => 'client1@example.com',
                'name' => 'Jean Dupont',
                'phone' => '+229 97 12 34 56',
            ],
            [
                'email' => 'client2@example.com',
                'name' => 'Marie Martin',
                'phone' => '+229 96 78 90 12',
            ],
            [
                'email' => 'client3@example.com',
                'name' => 'Pierre Bernard',
                'phone' => '+229 95 11 22 33',
            ],
        ];

        foreach ($clients as $clientData) {
            User::updateOrCreate(
                ['email' => $clientData['email']],
                [
                    'name' => $clientData['name'],
                    'password' => Hash::make('password'),
                    'type' => 'client',
                    'phone' => $clientData['phone'],
                    'locale' => 'fr',
                    'preferred_currency_id' => $defaultCurrency->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Default passwords: "password"');
    }
}
