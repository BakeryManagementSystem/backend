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
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign(['buyer_id'], 'p_buyer_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['order_id'], 'p_order_id_foreign')->references(['id'])->on('orders')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['owner_id'], 'p_owner_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['product_id'], 'p_product_id_foreign')->references(['id'])->on('products')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign('p_buyer_id_foreign');
            $table->dropForeign('p_order_id_foreign');
            $table->dropForeign('p_owner_id_foreign');
            $table->dropForeign('p_product_id_foreign');
        });
    }
};
