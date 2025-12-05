<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_quotes')) {
            Schema::create('vnt_quotes', function (Blueprint $table) {
                $table->id('id');
                $table->integer('consecutive');
                $table->string('status');
                $table->string('typeQuote');
                $table->integer('customerId')->nullable();
                $table->integer('warehouseId')->nullable();
                $table->integer('userId')->nullable();
                $table->text('observations')->nullable();
                $table->integer('branchId')->nullable();
                $table->index('customerId');
                $table->index('warehouseId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_quotes');
    }
};
