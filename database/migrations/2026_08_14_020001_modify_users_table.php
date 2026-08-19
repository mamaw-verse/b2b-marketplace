<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name', 100)->after('first_name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('photo_path', 255)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'phone', 'photo_path']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });
    }
};
