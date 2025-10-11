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
        Schema::table('shop_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_profiles', 'description')) {
                $table->text('description')->nullable()->after('shop_name');
            }
            if (!Schema::hasColumn('shop_profiles', 'banner_path')) {
                $table->string('banner_path')->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('shop_profiles', 'theme')) {
                $table->json('theme')->nullable()->after('facebook_url');
            }
            if (!Schema::hasColumn('shop_profiles', 'policies')) {
                $table->json('policies')->nullable()->after('theme');
            }
            if (!Schema::hasColumn('shop_profiles', 'social')) {
                $table->json('social')->nullable()->after('policies');
            }
            if (!Schema::hasColumn('shop_profiles', 'settings')) {
                $table->json('settings')->nullable()->after('social');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_profiles', function (Blueprint $table) {
            $columns_to_drop = [];

            if (Schema::hasColumn('shop_profiles', 'description')) {
                $columns_to_drop[] = 'description';
            }
            if (Schema::hasColumn('shop_profiles', 'banner_path')) {
                $columns_to_drop[] = 'banner_path';
            }
            if (Schema::hasColumn('shop_profiles', 'theme')) {
                $columns_to_drop[] = 'theme';
            }
            if (Schema::hasColumn('shop_profiles', 'policies')) {
                $columns_to_drop[] = 'policies';
            }
            if (Schema::hasColumn('shop_profiles', 'social')) {
                $columns_to_drop[] = 'social';
            }
            if (Schema::hasColumn('shop_profiles', 'settings')) {
                $columns_to_drop[] = 'settings';
            }

            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};
