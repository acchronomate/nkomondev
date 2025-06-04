<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@nkomonapp.com',
                'password' => Hash::make('Jesul@vo87'),
                'email_verified_at' => now(),
                'type' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Host User',
                'email' => 'host@nkomonapp.com',
                'password' => Hash::make('Jesul@vo87'),
                'email_verified_at' => now(),
                'type' => 'host',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Client User',
                'email' => 'client@nkomonapp.com',
                'password' => Hash::make('Jesul@vo87'),
                'email_verified_at' => now(),
                'type' => 'client',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
