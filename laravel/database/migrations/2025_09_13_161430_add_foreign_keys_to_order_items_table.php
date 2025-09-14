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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign(['order_id'], 'oi_order_id_foreign')->references(['id'])->on('orders')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['owner_id'], 'oi_owner_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['product_id'], 'oi_product_id_foreign')->references(['id'])->on('products')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign('oi_order_id_foreign');
            $table->dropForeign('oi_owner_id_foreign');
            $table->dropForeign('oi_product_id_foreign');
        });
    }
};
