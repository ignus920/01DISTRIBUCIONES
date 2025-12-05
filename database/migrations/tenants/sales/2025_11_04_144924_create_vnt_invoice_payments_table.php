<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_invoice_payments')) {
            Schema::create('vnt_invoice_payments', function (Blueprint $table) {
                $table->id('id');
                $table->decimal('value', 10, 2);
                $table->integer('invoiceId')->nullable();
                $table->integer('methodPaymentId')->nullable();
                $table->index('invoiceId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_invoice_payments');
    }
};
