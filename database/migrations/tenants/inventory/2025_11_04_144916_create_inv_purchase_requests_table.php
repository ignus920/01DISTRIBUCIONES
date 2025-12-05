<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_purchase_requests')) {
            Schema::create('inv_purchase_requests', function (Blueprint $table) {
                $table->id('id');
                $table->integer('consecutive');
                $table->integer('userRealize');
                $table->integer('userApprove')->nullable();
                $table->dateTime('dateApprove')->nullable();
                $table->string('status');
                $table->text('observations');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_purchase_requests');
    }
};
