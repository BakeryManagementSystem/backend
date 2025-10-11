<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'shop_name' => null,
                'email' => 'admin@bakery.com',
                'phone' => '+1234567890',
                'date_of_birth' => '1990-01-01',
                'avatar' => null,
                'password' => Hash::make('password'),
                'user_type' => 'admin',
            ],
            [
                'name' => 'Baker Shop Owner',
                'shop_name' => 'The Artisan Bakery',
                'email' => 'baker@bakery.com',
                'phone' => '+1234567891',
                'date_of_birth' => '1985-05-15',
                'avatar' => null,
                'password' => Hash::make('password'),
                'user_type' => 'owner',
            ],
            [
                'name' => 'Sweet Delights Owner',
                'shop_name' => 'Sweet Delights',
                'email' => 'sweet@bakery.com',
                'phone' => '+1234567892',
                'date_of_birth' => '1988-08-20',
                'avatar' => null,
                'password' => Hash::make('password'),
                'user_type' => 'owner',
            ],
            [
                'name' => 'John Buyer',
                'shop_name' => null,
                'email' => 'buyer@example.com',
                'phone' => '+1234567893',
                'date_of_birth' => '1995-03-10',
                'avatar' => null,
                'password' => Hash::make('password'),
                'user_type' => 'buyer',
            ],
            [
                'name' => 'Jane Customer',
                'shop_name' => null,
                'email' => 'customer@example.com',
                'phone' => '+1234567894',
                'date_of_birth' => '1992-07-25',
                'avatar' => null,
                'password' => Hash::make('password'),
                'user_type' => 'buyer',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}

