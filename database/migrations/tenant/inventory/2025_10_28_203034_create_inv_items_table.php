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
        Schema::create('inv_items', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->integer('api_data_id')->nullable()->comment('id de integracion');
            $table->unsignedInteger('categoryId');
            $table->foreign('categoryId')->references('id')->on('inv_categories');
            $table->string('name', 255);
            $table->string('internal_code', 100);
            $table->string('sku', 255);
            $table->text('description')->nullable();
            $table->enum('type', ["COMBO","COMPRA NACIONAL","IMPORTADO","PRODUCIDO"]);
            $table->unsignedInteger('commandId');
            $table->foreign('commandId')->references('id')->on('inv_command');
            $table->unsignedInteger('brandId');
            $table->foreign('brandId')->references('id')->on('inv_item_brand');
            $table->unsignedInteger('houseId');
            $table->foreign('houseId')->references('id')->on('inv_item_house');
            $table->tinyInteger('inventoriable')->default(1)->comment('1=SI 0=NO');
            $table->unsignedInteger('purchasing_unit');
            $table->foreign('purchasing_unit')->references('id')->on('inv_unit_measurements');
            $table->unsignedInteger('consumption_unit');
            $table->foreign('consumption_unit')->references('id')->on('inv_unit_measurements');
            $table->tinyInteger('status')->default(1); // tinyint, default 1
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
        Schema::dropIfExists('inv_items');
    }
};
