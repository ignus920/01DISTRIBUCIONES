<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_remissions')) {
            Schema::create('inv_remissions', function (Blueprint $table) {
                $table->id('id');
                $table->integer('consecutive');
                $table->string('status')->nullable()->default('REGISTRADO');
                $table->integer('quoteId')->nullable();
                $table->integer('warehouseId')->nullable();
                $table->integer('deliveryTypeId')->nullable();
                $table->integer('methodPaymentId');
                $table->integer('userId');
                $table->string('deliveryDate', 50);
                $table->integer('expiration')->nullable();
                $table->integer('modify')->nullable();
                $table->index('quoteId');
                $table->index('warehouseId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_remissions');
    }
};
