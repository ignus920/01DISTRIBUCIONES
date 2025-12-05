<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la clave foránea ya existe
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'vnt_contacts' 
            AND CONSTRAINT_NAME = 'vnt_contacts_warehouseid_foreign'
        ");

        if (empty($foreignKeys)) {
            // 1. Actualizar registros con valores 0 o null para evitar errores de clave foránea
            DB::table('vnt_contacts')
                ->whereNull('warehouseId')
                ->orWhere('warehouseId', 0)
                ->update(['warehouseId' => 1]);

            DB::table('vnt_contacts')
                ->whereNull('positionId')
                ->orWhere('positionId', 0)
                ->update(['positionId' => 1]);

            // 2. Cambiar tipos de columnas para que coincidan con las tablas referenciadas
            Schema::table('vnt_contacts', function (Blueprint $table) {
                // warehouseId debe ser unsignedBigInteger para coincidir con vnt_warehouses.id
                $table->unsignedBigInteger('warehouseId')->nullable()->default(1)->change();
                // positionId debe ser unsignedInteger (se corregirá en la siguiente migración)
                $table->unsignedInteger('positionId')->nullable()->default(1)->change();
            });

            // 3. Añadir solo la clave foránea de warehouseId
            Schema::table('vnt_contacts', function (Blueprint $table) {
                // Clave foránea para 'vnt_warehouses'
                $table->foreign('warehouseId')
                    ->references('id')
                    ->on('vnt_warehouses')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vnt_contacts', function (Blueprint $table) {
            $table->dropForeign(['warehouseId']);
            // Revertir tipos de columnas a los originales
            $table->bigInteger('warehouseId')->nullable()->default(0)->change();
            $table->unsignedInteger('positionId')->nullable()->default(0)->change();
        });
    }
};