<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_detail_inv_adjustments', function (Blueprint $table) {
            
            // 1. Cambiar el tipo de dato para que coincida con la tabla padre.
            // Para 'inv_items.id' es unsignedInteger. Para 'inv_inventory_adjustments.id' (asumo) es BigInteger.
            $table->unsignedInteger('inventoryAdjustmentId')->nullable()->change();
            // CORRECCIÓN: Usar unsignedInteger para coincidir con inv_items.id
            $table->unsignedInteger('itemId')->nullable()->change(); 
            
            // 2. Definir las llaves foráneas
            $table->foreign('inventoryAdjustmentId')
                  ->references('id')
                  ->on('inv_inventory_adjustments')
                  ->onDelete('cascade'); 

            $table->foreign('itemId')
                  ->references('id')
                  ->on('inv_items')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('inv_detail_inv_adjustments', function (Blueprint $table) {
            
            // 1. ELIMINAR AMBAS LLAVES FORÁNEAS
            $table->dropForeign(['inventoryAdjustmentId']);
            $table->dropForeign(['itemId']); 

            // 2. REVERTIR EL TIPO DE DATO PARA AMBAS COLUMNAS
            // Asumiendo que el tipo original era 'integer' sin signo.
            $table->unsignedInteger('inventoryAdjustmentId')->nullable()->change(); // O el tipo original
            $table->unsignedInteger('itemId')->nullable()->change(); // O el tipo original
        });
    }
};