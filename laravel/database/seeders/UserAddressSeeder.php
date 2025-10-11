<?php

namespace Database\Seeders;

use App\Models\UserAddress;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('user_type', 'buyer')->get();

        if ($users->isEmpty()) {
            $this->command->info('No buyers found. Skipping user addresses seeding.');
            return;
        }

        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego'];
        $states = ['NY', 'CA', 'IL', 'TX', 'AZ', 'PA', 'TX', 'CA'];

        foreach ($users as $user) {
            $cityIndex = array_rand($cities);

            // Create default shipping address
            UserAddress::create([
                'user_id' => $user->id,
                'type' => 'shipping',
                'first_name' => explode(' ', $user->name)[0] ?? 'First',
                'last_name' => explode(' ', $user->name)[1] ?? 'Last',
                'company' => null,
                'address_line_1' => rand(100, 9999) . ' Main Street',
                'address_line_2' => rand(0, 1) ? 'Apt ' . rand(1, 500) : null,
                'city' => $cities[$cityIndex],
                'state' => $states[$cityIndex],
                'postal_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                'country' => 'US',
                'phone' => $user->phone,
                'is_default' => true,
            ]);

            // 50% chance to add a billing address
            if (rand(0, 1)) {
                $cityIndex = array_rand($cities);

                UserAddress::create([
                    'user_id' => $user->id,
                    'type' => 'billing',
                    'first_name' => explode(' ', $user->name)[0] ?? 'First',
                    'last_name' => explode(' ', $user->name)[1] ?? 'Last',
                    'company' => null,
                    'address_line_1' => rand(100, 9999) . ' Oak Avenue',
                    'address_line_2' => null,
                    'city' => $cities[$cityIndex],
                    'state' => $states[$cityIndex],
                    'postal_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'country' => 'US',
                    'phone' => $user->phone,
                    'is_default' => false,
                ]);
            }
        }

        $this->command->info('User addresses seeded successfully!');
    }
}

