<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found. Skipping notification seeding.');
            return;
        }

        $notificationTypes = [
            [
                'type' => 'new_order',
                'title' => 'New Order Received',
                'message' => 'You have received a new order.',
            ],
            [
                'type' => 'order_updated',
                'title' => 'Order Status Updated',
                'message' => 'Your order status has been updated to processing.',
            ],
            [
                'type' => 'order_completed',
                'title' => 'Order Completed',
                'message' => 'Your order has been completed successfully.',
            ],
            [
                'type' => 'low_stock',
                'title' => 'Low Stock Alert',
                'message' => 'Some products are running low on stock.',
            ],
            [
                'type' => 'promotion',
                'title' => 'Special Promotion',
                'message' => 'Check out our latest promotion! Get 20% off on selected items.',
            ],
        ];

        // Create 3-5 notifications per user
        foreach ($users->take(5) as $user) {
            $count = rand(3, 5);

            for ($i = 0; $i < $count; $i++) {
                $notif = $notificationTypes[array_rand($notificationTypes)];
                $isRead = rand(0, 1); // 50% chance of being read

                Notification::create([
                    'user_id' => $user->id,
                    'type' => $notif['type'],
                    'title' => $notif['title'],
                    'message' => $notif['message'],
                    'data' => json_encode(['created_at' => now()->toDateTimeString()]),
                    'read_at' => $isRead ? now()->subDays(rand(1, 7)) : null,
                    'created_at' => now()->subDays(rand(0, 14)),
                ]);
            }
        }

        $this->command->info('Notifications seeded successfully!');
    }
}

