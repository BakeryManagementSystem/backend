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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->unsignedInteger('category_id')->nullable()->after('category');
            $table->integer('stock_quantity')->default(0)->after('category_id');
            $table->string('sku', 100)->nullable()->after('stock_quantity');
            $table->decimal('weight', 8, 2)->nullable()->after('sku');
            $table->string('dimensions')->nullable()->after('weight');
            $table->json('ingredients')->nullable()->after('dimensions');
            $table->json('allergens')->nullable()->after('ingredients');
            $table->enum('status', ['active', 'draft', 'out_of_stock'])->default('active')->after('allergens');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->string('meta_title')->nullable()->after('is_featured');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->json('images')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'discount_price',
                'category_id',
                'stock_quantity',
                'sku',
                'weight',
                'dimensions',
                'ingredients',
                'allergens',
                'status',
                'is_featured',
                'meta_title',
                'meta_description',
                'images'
            ]);
        });
    }
};
