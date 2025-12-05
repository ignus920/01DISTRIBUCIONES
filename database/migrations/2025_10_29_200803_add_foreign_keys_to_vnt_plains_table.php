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
        Schema::table('vnt_plains', function (Blueprint $table) {
            $table->foreign(['merchantTypeId'], 'vnt_plains_ibfk_1')->references(['id'])->on('vnt_merchant_types')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vnt_plains', function (Blueprint $table) {
            $table->dropForeign('vnt_plains_ibfk_1');
        });
    }
};
