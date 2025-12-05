<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_purchase_request_details')) {
            Schema::create('inv_purchase_request_details', function (Blueprint $table) {
                $table->id('id');
                $table->integer('purchase_requestsId');
                $table->integer('itemId')->nullable();
                $table->integer('quantity_requested')->nullable();
                $table->index('purchase_requestsId');
                $table->index('itemId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_purchase_request_details');
    }
};
