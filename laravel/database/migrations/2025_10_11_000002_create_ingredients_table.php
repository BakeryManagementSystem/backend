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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('unit', 30);
            $table->decimal('current_unit_price', 10, 2)->default(0.00);
            $table->decimal('current_stock', 10, 3)->default(0.000);
            $table->decimal('minimum_stock_level', 10, 3)->default(0.000);
            $table->decimal('maximum_stock_level', 10, 3)->default(0.000);
            $table->string('supplier_name')->nullable();
            $table->string('supplier_contact')->nullable();
            $table->string('storage_location')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('category')->default('other');
            $table->text('description')->nullable();
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('current_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
