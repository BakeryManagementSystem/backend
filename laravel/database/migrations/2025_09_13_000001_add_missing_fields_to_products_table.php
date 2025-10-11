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
            if (!Schema::hasColumn('products', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->unsignedInteger('category_id')->nullable()->after('category');
            }
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->after('category_id');
            }
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 100)->nullable()->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('sku');
            }
            if (!Schema::hasColumn('products', 'dimensions')) {
                $table->string('dimensions')->nullable()->after('weight');
            }
            if (!Schema::hasColumn('products', 'ingredients')) {
                $table->json('ingredients')->nullable()->after('dimensions');
            }
            if (!Schema::hasColumn('products', 'allergens')) {
                $table->json('allergens')->nullable()->after('ingredients');
            }
            if (!Schema::hasColumn('products', 'status')) {
                $table->enum('status', ['active', 'draft', 'out_of_stock'])->default('active')->after('allergens');
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (!Schema::hasColumn('products', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('is_featured');
            }
            if (!Schema::hasColumn('products', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('products', 'images')) {
                $table->json('images')->nullable()->after('image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'discount_price', 'category_id', 'stock_quantity', 'sku', 'weight',
                'dimensions', 'ingredients', 'allergens', 'status', 'is_featured',
                'meta_title', 'meta_description', 'images'
            ];

            $columns_to_drop = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $columns_to_drop[] = $column;
                }
            }

            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};
