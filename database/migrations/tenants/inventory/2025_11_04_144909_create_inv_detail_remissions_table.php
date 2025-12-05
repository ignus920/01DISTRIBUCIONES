<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_detail_remissions')) {
            Schema::create('inv_detail_remissions', function (Blueprint $table) {
                $table->id('id');
                $table->integer('quantity')->nullable()->default(0);
                $table->integer('tax')->nullable();
                $table->integer('value')->nullable()->default(0);
                $table->integer('invoiceId')->nullable();
                $table->integer('itemId')->nullable();
                $table->integer('remissionId')->nullable();
                $table->index('remissionId');
                $table->index('itemId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_detail_remissions');
    }
};
