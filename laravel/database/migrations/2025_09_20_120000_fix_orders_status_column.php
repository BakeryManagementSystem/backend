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

        // Now safely modify the column to ENUM
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'shipped', 'delivered'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to string column
            $table->string('status')->default('pending')->change();
        });
    }
};
