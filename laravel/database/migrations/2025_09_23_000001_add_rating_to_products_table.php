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
            if (!Schema::hasColumn('products', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0.00)->after('status');
            }
            if (!Schema::hasColumn('products', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('rating');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns_to_drop = [];

            if (Schema::hasColumn('products', 'rating')) {
                $columns_to_drop[] = 'rating';
            }
            if (Schema::hasColumn('products', 'rating_count')) {
                $columns_to_drop[] = 'rating_count';
            }

            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};
