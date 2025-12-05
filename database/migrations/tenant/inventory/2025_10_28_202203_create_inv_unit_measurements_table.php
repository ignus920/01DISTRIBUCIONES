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
        Schema::create('inv_unit_measurements', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->string('description', 255)->default(1);  // varchar(100), not nullable
            $table->tinyInteger('status')->default(1)->nullable(); // tinyint, default 1
            $table->integer('quantity')->default(0);
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
        Schema::dropIfExists('inv_unit_measurements');
    }
};
