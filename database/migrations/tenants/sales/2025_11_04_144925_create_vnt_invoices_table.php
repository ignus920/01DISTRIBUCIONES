<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_invoices')) {
            Schema::create('vnt_invoices', function (Blueprint $table) {
                $table->id('id');
                $table->integer('consecutive');
                $table->string('status');
                $table->string('status_payment');
                $table->integer('api_data_id')->nullable();
                $table->integer('api_data_id_pay')->nullable();
                $table->integer('partialPayment')->nullable();
                $table->integer('quoteId')->nullable();
                $table->integer('warehouseId')->nullable();
                $table->integer('remission');
                $table->integer('creditNoteId')->nullable();
                $table->string('invoiceNumber', 100);
                $table->integer('retentionFuente')->nullable();
                $table->integer('retentionIca')->nullable();
                $table->integer('retentionIva')->nullable();
                $table->integer('creditNote')->nullable()->default(0);
                $table->string('orderNumber', 255)->nullable();
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_invoices');
    }
};
