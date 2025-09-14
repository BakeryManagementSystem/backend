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
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->index('oi_order_id_index');
            $table->unsignedBigInteger('product_id')->index('oi_product_id_index');
            $table->unsignedBigInteger('owner_id')->index('oi_owner_id_index');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10);
            $table->decimal('line_total', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
