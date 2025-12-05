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
        Schema::create('inv_values', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->dateTime('date')->useCurrent(); // datetime, default CURRENT_TIMESTAMP
            $table->double('values')->default(0);
            $table->enum('type', ["costo","precio"]);
            $table->unsignedInteger('itemId');
            $table->foreign('itemId')->references('id')->on('inv_items');
            $table->integer('warehouseId')->unique()->nullable();
            $table->enum('label', ["Costo Inicial","Costo", "Precio Base", "Precio Regular", "Precio Crédito"])->nullable();
            $table->dateTime('createdAt')->useCurrent(); // datetime, default CURRENT_TIMESTAMP
            $table->dateTime('updatedAt')->nullable(); // datetime, nullable
            $table->dateTime('deletedAt')->nullable(); // datetime, nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_values');
    }
};
