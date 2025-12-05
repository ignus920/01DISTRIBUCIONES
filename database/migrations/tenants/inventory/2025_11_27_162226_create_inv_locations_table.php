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
        Schema::create('inv_locations', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->string('name', 100)->default(0);
            $table->tinyInteger('status')->default(1); // tinyint, default 1
            $table->timestamps();        // created_at, updated_at
            $table->softDeletes();       // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_locations');
    }
};
