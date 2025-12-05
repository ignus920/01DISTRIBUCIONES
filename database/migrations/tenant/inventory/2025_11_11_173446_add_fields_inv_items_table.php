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
        Schema::table('inv_items',  function (Blueprint $table){
            $table->tinyInteger('generic')->default(0)->comment('1=SI 0=NO');
            $table->unsignedBigInteger('taxId')->nullable();
            $table->foreign('taxId')->references('id')->on('cnf_taxes');
            $table->unsignedInteger('handles_serial')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropForeign(['taxId']);
            $table->dropColumn(['generic', 'taxId', 'handles_serial']);
        });
    }
};
