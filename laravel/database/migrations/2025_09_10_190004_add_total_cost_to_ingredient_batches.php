<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only run if the ingredient_batches table exists
        if (Schema::hasTable('ingredient_batches')) {
            Schema::table('ingredient_batches', function (Blueprint $table) {
                if (!Schema::hasColumn('ingredient_batches', 'total_cost')) {
                    $table->decimal('total_cost', 12, 2)->default(0)->after('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ingredient_batches')) {
            Schema::table('ingredient_batches', function (Blueprint $table) {
                if (Schema::hasColumn('ingredient_batches', 'total_cost')) {
                    $table->dropColumn('total_cost');
                }
            });
        }
    }
};
