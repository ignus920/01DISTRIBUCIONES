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
        Schema::table('vnt_contacts', function (Blueprint $table) {
            // Cambiar positionId a int (con signo) para que coincida con cnf_positions.id
            // No se agrega la clave foránea porque cnf_positions está en el módulo inventory
            $table->integer('positionId')->nullable()->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vnt_contacts', function (Blueprint $table) {
            // Revertir a unsignedInteger
            $table->unsignedInteger('positionId')->nullable()->default(1)->change();
        });
    }
};