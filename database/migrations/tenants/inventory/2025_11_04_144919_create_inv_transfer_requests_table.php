<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_transfer_requests')) {
            Schema::create('inv_transfer_requests', function (Blueprint $table) {
                $table->id('id');
                $table->string('status')->nullable()->default('REGISTRADO');
                $table->string('date', 255);
                $table->integer('quoteId')->nullable();
                $table->integer('warehouseId')->nullable()->default(0);
                $table->string('observations', 255)->nullable();
                $table->index('quoteId');
                $table->timestamps();   // created_at, updated_at
                $table->softDeletes();  // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_transfer_requests');
    }
};
