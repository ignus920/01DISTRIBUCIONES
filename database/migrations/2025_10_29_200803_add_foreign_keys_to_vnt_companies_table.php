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
        Schema::table('vnt_companies', function (Blueprint $table) {
            $table->foreign(['typeIdentificationId'], 'vnt_companies_ibfk_1')->references(['id'])->on('cnf_type_identifications')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['regimeId'], 'vnt_companies_ibfk_2')->references(['id'])->on('cnf_regime')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['fiscalResponsabilityId'], 'vnt_companies_ibfk_3')->references(['id'])->on('cnf_fiscal_responsabilities')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vnt_companies', function (Blueprint $table) {
            $table->dropForeign('vnt_companies_ibfk_1');
            $table->dropForeign('vnt_companies_ibfk_2');
            $table->dropForeign('vnt_companies_ibfk_3');
        });
    }
};
