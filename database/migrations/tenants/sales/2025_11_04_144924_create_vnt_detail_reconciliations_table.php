<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_detail_reconciliations')) {
            Schema::create('vnt_detail_reconciliations', function (Blueprint $table) {
                $table->id('id');
                $table->integer('value');
                $table->integer('valueSystem');
                $table->integer('methodPaymentId')->nullable();
                $table->integer('reconciliationId');
                $table->index('methodPaymentId');
                $table->index('reconciliationId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_detail_reconciliations');
    }
};
