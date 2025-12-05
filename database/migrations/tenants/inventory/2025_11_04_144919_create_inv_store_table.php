<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_store')) {
          Schema::create('inv_store', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->string('name', 100)->default(1);
            $table->unsignedInteger('warehouseId')->nullable();
            $table->unsignedInteger('store_manager')->nullable();
            $table->tinyInteger('status')->default(1); // tinyint, default 1
            $table->timestamps();        // created_at, updated_at
            $table->softDeletes();       // deleted_at
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_store');
    }
};
