<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_inventory_count')) {
            Schema::create('inv_inventory_count', function (Blueprint $table) {
                $table->id('id');
                $table->integer('status')->default(0);
                $table->integer('warehouseId')->nullable();
                $table->integer('consecutive');
                $table->integer('userId');
                $table->integer('itemId')->nullable();
                $table->integer('quantityDig');
                $table->integer('quantityCal');
                $table->integer('quantityInv');
                $table->integer('quantityTotal');
                $table->integer('unitMeasurementId')->nullable();
                $table->index('itemId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_inventory_count');
    }
};
