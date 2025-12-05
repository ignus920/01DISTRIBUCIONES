<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vnt_merchant_moduls', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('merchantId')->nullable()->index('merchantid');
            $table->integer('modulId')->nullable()->index('modulid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnt_merchant_moduls');
    }
};
