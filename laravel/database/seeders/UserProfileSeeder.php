<?php

namespace Database\Seeders;

use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found. Skipping user profiles seeding.');
            return;
        }

        foreach ($users as $user) {
            // Check if profile already exists
            if (UserProfile::where('user_id', $user->id)->exists()) {
                continue;
            }

            UserProfile::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_path' => null,
                'address' => rand(100, 9999) . ' Main Street, City, State ' . rand(10000, 99999),
                'facebook_url' => null,
            ]);
        }

        $this->command->info('User profiles seeded successfully!');
    }
}
