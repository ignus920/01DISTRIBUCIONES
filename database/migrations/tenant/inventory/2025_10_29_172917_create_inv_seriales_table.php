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
        Schema::create('inv_seriales', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->unsignedInteger('itemId');
            $table->foreign('itemId')->references('id')->on('inv_items');
            $table->dateTime('date')->useCurrent(); // datetime, default CURRENT_TIMESTAMP
            $table->string('serial', 100)->nullable();
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
        Schema::dropIfExists('inv_seriales');
    }
};
