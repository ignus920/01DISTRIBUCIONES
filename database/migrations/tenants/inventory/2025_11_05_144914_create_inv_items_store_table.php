<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_items_store')) {
           Schema::create('inv_items_store', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->unsignedInteger('itemId');
            $table->foreign('itemId')->references('id')->on('inv_items');
            $table->unsignedInteger('storeId');
            $table->foreign('storeId')->references('id')->on('inv_store');
            $table->decimal('initial_stock', total: 12, places: 2)->default(0.00)->nullable();
            $table->decimal('stock_items_store', total: 12, places: 2)->default(0.00)->nullable();
            $table->decimal('stock_min', total: 12, places: 2)->default(0.00);
            $table->decimal('stock_max', total: 12, places: 2)->default(0.00);
            $table->timestamps();        // created_at, updated_at
            $table->softDeletes();       // deleted_at
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_items_store');
    }
};
