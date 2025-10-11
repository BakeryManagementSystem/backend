<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('date_of_birth');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columns_to_drop = [];

            if (Schema::hasColumn('users', 'phone')) {
                $columns_to_drop[] = 'phone';
            }
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $columns_to_drop[] = 'date_of_birth';
            }
            if (Schema::hasColumn('users', 'avatar')) {
                $columns_to_drop[] = 'avatar';
            }

            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};
