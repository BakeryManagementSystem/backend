<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing invalid status values to 'pending'
        DB::table('orders')->whereNotIn('status', ['pending', 'processing', 'completed', 'cancelled', 'shipped', 'delivered'])
            ->update(['status' => 'pending']);

        // Also handle null values
        DB::table('orders')->whereNull('status')->update(['status' => 'pending']);

        // Use raw SQL to modify the column to ENUM (Doctrine DBAL doesn't support ENUM changes)
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'processing', 'completed', 'cancelled', 'shipped', 'delivered') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to string column using raw SQL
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
    }
};
