<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_unit_measurements')) {
           Schema::create('inv_unit_measurements', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->string('description', 255)->default(1);  // varchar(100), not nullable
            $table->tinyInteger('status')->default(1)->nullable(); // tinyint, default 1
            $table->integer('quantity')->default(0);
            $table->timestamps();        // created_at, updated_at
            $table->softDeletes();       // deleted_at
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_unit_measurements');
    }
};
