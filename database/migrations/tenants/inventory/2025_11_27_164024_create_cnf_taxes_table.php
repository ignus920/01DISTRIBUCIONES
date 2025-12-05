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
        Schema::create('cnf_taxes', function (Blueprint $table) {
            // Clave Primaria (PK) y ID
            // El tipo INT (4 bytes) se representa en Laravel con ->increments() o ->unsignedInteger()
            $table->increments('id'); // int NOT NULL, auto-increment, PK
            
            // Columnas principales
            $table->string('name', 255)->unique(); // varchar(255) NOT NULL (añadí unique, si lo necesitas)
            $table->double('percentage'); // double NOT NULL
            
            // Columna de estado
            $table->tinyInteger('status')->default(1); // tinyint NOT NULL DEFAULT '1'

            // Columna de integración
            $table->integer('api_data_id')->nullable(); // int DEFAULT NULL
            
            // Columnas de cuentas (asumo que hacen referencia a IDs de otras tablas, 
            // por eso uso unsignedInteger, que es la mejor práctica para IDs)
            $table->unsignedInteger('inventoryAccount'); // int NOT NULL
            $table->unsignedInteger('inventariablePurchaseAccount'); // int NOT NULL
            $table->unsignedInteger('categoryAccount'); // int NOT NULL

            // Trazabilidad (created_at, updated_at, deleted_at)
            $table->timestamps(); // created_at, updated_at (datetime DEFAULT NULL)
            $table->softDeletes(); // deleted_at (datetime DEFAULT NULL)

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cnf_taxes');
    }
};