<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_detail_inventory')) {
            Schema::create('inv_detail_inventory', function (Blueprint $table) {
                $table->id('id');
                $table->integer('quantity')->default(0);
                $table->string('date', 255);
                $table->integer('storeId')->nullable();
                $table->integer('itemId')->nullable();
                $table->index('itemId');
                $table->index('storeId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_detail_inventory');
    }
};
