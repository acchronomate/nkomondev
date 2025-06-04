<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'XOF',
                'name' => 'Franc CFA (UEMOA)',
                'symbol' => 'FCFA',
                'exchange_rate' => 1.000000, // Devise de base
                'decimal_places' => 0,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'XAF',
                'name' => 'Franc CFA (CEMAC)',
                'symbol' => 'FCFA',
                'exchange_rate' => 1.000000,
                'decimal_places' => 0,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 655.957000, // 1 EUR = 655.957 XOF
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'USD',
                'name' => 'Dollar américain',
                'symbol' => '$',
                'exchange_rate' => 610.500000, // Taux approximatif
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'GBP',
                'name' => 'Livre sterling',
                'symbol' => '£',
                'exchange_rate' => 775.250000, // Taux approximatif
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'NGN',
                'name' => 'Naira nigérian',
                'symbol' => '₦',
                'exchange_rate' => 0.395000, // Taux approximatif
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'GHS',
                'name' => 'Cedi ghanéen',
                'symbol' => 'GH₵',
                'exchange_rate' => 50.125000, // Taux approximatif
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('Currencies seeded successfully!');
    }
}
