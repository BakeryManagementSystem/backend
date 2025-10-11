<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_profiles', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->default(5.0)->after('settings');
            $table->integer('total_reviews')->default(0)->after('average_rating');
            $table->integer('total_products')->default(0)->after('total_reviews');
            $table->integer('total_sales')->default(0)->after('total_products');
            $table->boolean('verified')->default(false)->after('total_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_profiles', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'total_reviews', 'total_products', 'total_sales', 'verified']);
        });
    }
};

