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
        Schema::table('vnt_contacts', function (Blueprint $table) {
            $table->foreign(['warehouseId'], 'vnt_contacts_ibfk_1')->references(['id'])->on('vnt_warehouses')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['positionId'], 'vnt_contacts_ibfk_2')->references(['id'])->on('cnf_positions')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vnt_contacts', function (Blueprint $table) {
            $table->dropForeign('vnt_contacts_ibfk_1');
            $table->dropForeign('vnt_contacts_ibfk_2');
        });
    }
};
