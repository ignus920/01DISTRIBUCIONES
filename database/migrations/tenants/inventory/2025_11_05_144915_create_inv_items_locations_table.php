<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_items_locations')) {
          Schema::create('inv_items_locations', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->unsignedInteger('itemId');
            $table->foreign('itemId')->references('id')->on('inv_items');
            $table->unsignedInteger('storeId');
            $table->foreign('storeId')->references('id')->on('inv_store');
            $table->integer('locationId')->nullable();
            $table->decimal('stock_item_location', total: 12, places: 2);
            $table->timestamps();        // created_at, updated_at
            $table->softDeletes();       // deleted_at
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_items_locations');
    }
};
