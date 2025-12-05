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
        Schema::create('vnt_warehouses', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('companyId')->default(0);
            $table->string('name');
            $table->string('address');
            $table->string('postcode', 10)->nullable();
            $table->integer('cityId')->nullable();
            $table->integer('billingFormat')->default(16);
            $table->integer('is_credit')->default(0);
            $table->integer('termId')->default(1)->comment('forma de pago');
            $table->string('creditLimit', 20)->default('0')->comment('cupo de credito');
            $table->tinyInteger('status')->nullable()->default(1);
            $table->integer('integrationDataId')->nullable();
            $table->tinyInteger('main')->nullable()->default(1);
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnt_warehouses');
    }
};
