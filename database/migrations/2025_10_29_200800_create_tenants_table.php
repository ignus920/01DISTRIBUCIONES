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
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('db_name');
            $table->string('db_user')->nullable();
            $table->string('db_password')->nullable();
            $table->string('db_host')->default('127.0.0.1');
            $table->integer('db_port')->default(3306);
            $table->boolean('is_active')->default(true);
            $table->longText('settings')->nullable();
            $table->timestamps();
            $table->longText('data')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('merchant_type_id')->nullable();
            $table->integer('plain_id')->nullable();
            $table->dateTime('afiliation_date')->nullable();
            $table->dateTime('renovation_date')->nullable();
            $table->date('end_test')->nullable();
            $table->text('tenant_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
