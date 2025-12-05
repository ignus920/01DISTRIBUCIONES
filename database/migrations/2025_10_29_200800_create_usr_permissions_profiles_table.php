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
        Schema::create('usr_permissions_profiles', function (Blueprint $table) {
            $table->integer('id', true);
            $table->tinyInteger('creater');
            $table->tinyInteger('deleter');
            $table->tinyInteger('editer');
            $table->tinyInteger('show');
            $table->integer('profileId')->nullable()->index('profileid');
            $table->integer('permissionId')->nullable()->index('permissionid');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usr_permissions_profiles');
    }
};
