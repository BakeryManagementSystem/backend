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
            $table->text('description')->nullable()->after('shop_name');
            $table->string('banner_path')->nullable()->after('logo_path');
            $table->json('theme')->nullable()->after('facebook_url');
            $table->json('policies')->nullable()->after('theme');
            $table->json('social')->nullable()->after('policies');
            $table->json('settings')->nullable()->after('social');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'banner_path',
                'theme',
                'policies',
                'social',
                'settings'
            ]);
        });
    }
};
