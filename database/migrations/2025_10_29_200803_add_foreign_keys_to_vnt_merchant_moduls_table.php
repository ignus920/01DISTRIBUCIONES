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
        Schema::table('vnt_merchant_moduls', function (Blueprint $table) {
            $table->foreign(['merchantId'], 'vnt_merchant_moduls_ibfk_1')->references(['id'])->on('vnt_merchant_types')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['modulId'], 'vnt_merchant_moduls_ibfk_2')->references(['id'])->on('vnt_moduls')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vnt_merchant_moduls', function (Blueprint $table) {
            $table->dropForeign('vnt_merchant_moduls_ibfk_1');
            $table->dropForeign('vnt_merchant_moduls_ibfk_2');
        });
    }
};
