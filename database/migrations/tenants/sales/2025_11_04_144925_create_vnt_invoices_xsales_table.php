<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_invoicesXsales')) {
            Schema::create('vnt_invoicesXsales', function (Blueprint $table) {
                $table->id('id');
                $table->integer('remissionId')->nullable();
                $table->integer('quoteId')->nullable();
                $table->integer('invoiceId')->nullable();
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_invoicesXsales');
    }
};
