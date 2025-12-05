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
        Schema::table('usr_permissions_profiles', function (Blueprint $table) {
            $table->foreign(['profileId'], 'usr_permissions_profiles_ibfk_1')->references(['id'])->on('usr_profiles')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['permissionId'], 'usr_permissions_profiles_ibfk_2')->references(['id'])->on('usr_permissions')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usr_permissions_profiles', function (Blueprint $table) {
            $table->dropForeign('usr_permissions_profiles_ibfk_1');
            $table->dropForeign('usr_permissions_profiles_ibfk_2');
        });
    }
};
