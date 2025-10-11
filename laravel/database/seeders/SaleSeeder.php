<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owners = User::where('user_type', 'owner')->get();

        if ($owners->isEmpty()) {
            $this->command->info('No owners found. Skipping sales seeding.');
            return;
        }

        $buyers = User::where('user_type', 'buyer')->get();
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping sales seeding.');
            return;
        }

        $paymentMethods = ['cash', 'credit_card', 'debit_card', 'mobile_payment'];
        $paymentStatuses = ['pending', 'completed', 'refunded'];

        foreach ($owners as $owner) {
            // Create 3-7 sales per owner
            $salesCount = rand(3, 7);

            for ($i = 0; $i < $salesCount; $i++) {
                $totalAmount = 0;
                $discountAmount = rand(0, 20) + (rand(0, 99) / 100);
                $taxAmount = 0;

                // Create the sale
                $sale = Sale::create([
                    'order_id' => null,
                    'customer_id' => $buyers->isNotEmpty() ? $buyers->random()->id : null,
                    'sale_date' => Carbon::now()->subDays(rand(1, 60)),
                    'total_amount' => 0, // Will calculate
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                    'discount_amount' => $discountAmount,
                    'tax_amount' => 0, // Will calculate
                    'notes' => 'In-store sale',
                    'cashier_id' => $owner->id,
                ]);

                // Add 1-4 items to the sale
                $itemCount = rand(1, 4);
                $ownerProducts = $products->where('owner_id', $owner->id);

                if ($ownerProducts->isEmpty()) {
                    $ownerProducts = $products;
                }

                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $ownerProducts->random();
                    $quantity = rand(1, 5);
                    $unitPrice = $product->price;
                    $itemDiscount = rand(0, 5) + (rand(0, 99) / 100);
                    $lineTotal = ($unitPrice * $quantity) - $itemDiscount;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'discount_amount' => $itemDiscount,
                    ]);

                    $totalAmount += $lineTotal;
                }

                // Calculate tax (8% tax rate example)
                $taxAmount = round($totalAmount * 0.08, 2);
                $totalAmount = $totalAmount + $taxAmount - $discountAmount;

                // Update the sale totals
                $sale->update([
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                ]);
            }
        }

        $this->command->info('Sales seeded successfully!');
    }
}

