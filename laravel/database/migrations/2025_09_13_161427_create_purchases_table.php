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
        Schema::create('purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('owner_id')->index('p_owner_id_index');
            $table->unsignedBigInteger('buyer_id')->index('p_buyer_id_index');
            $table->unsignedBigInteger('order_id')->index('p_order_id_index');
            $table->unsignedBigInteger('product_id')->index('p_product_id_index');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10);
            $table->decimal('line_total', 10);
            $table->timestamp('sold_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
