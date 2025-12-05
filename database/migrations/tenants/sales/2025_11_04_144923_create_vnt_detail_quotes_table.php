<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_detail_quotes')) {
            Schema::create('vnt_detail_quotes', function (Blueprint $table) {
                $table->id('id');
                $table->integer('quantity');
                $table->integer('tax');
                $table->integer('value');
                $table->integer('quoteId')->nullable();
                $table->integer('itemId')->nullable();
                $table->string('description', 255);
                $table->integer('priceList');
                $table->index('quoteId');
                $table->index('itemId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_detail_quotes');
    }
};
