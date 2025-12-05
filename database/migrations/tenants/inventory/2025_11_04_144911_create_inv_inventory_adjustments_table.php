<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_inventory_adjustments')) {
            Schema::create('inv_inventory_adjustments', function (Blueprint $table) {
                $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
                $table->dateTime('date');
                $table->text('observations');
                $table->string('type', 255);
                $table->integer('status')->nullable()->default(1);
                $table->string('api_data_id', 255)->nullable();
                $table->integer('storeId')->nullable()->default(1);
                $table->integer('reasonId')->nullable();
                $table->integer('consecutive');
                $table->integer('userId');
                $table->index('reasonId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_inventory_adjustments');
    }
};
