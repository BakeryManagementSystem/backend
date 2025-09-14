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
        Schema::table('ingredient_batch_items', function (Blueprint $table) {
            $table->foreign(['batch_id'], 'ibi_batch_fk')->references(['id'])->on('ingredient_batches')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['ingredient_id'], 'ibi_ingredient_fk')->references(['id'])->on('ingredients')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_batch_items', function (Blueprint $table) {
            $table->dropForeign('ibi_batch_fk');
            $table->dropForeign('ibi_ingredient_fk');
        });
    }
};
