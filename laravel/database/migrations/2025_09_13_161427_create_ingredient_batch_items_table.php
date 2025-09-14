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
        Schema::create('ingredient_batch_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('batch_id')->index('ibi_batch_id_index');
            $table->unsignedBigInteger('ingredient_id')->index('ibi_ingredient_id_index');
            $table->decimal('quantity_used', 12, 4);
            $table->decimal('unit_price_snapshot', 10);
            $table->decimal('line_cost', 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_batch_items');
    }
};
