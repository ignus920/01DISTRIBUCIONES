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
        Schema::create('vnt_contacts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('firstName')->nullable();
            $table->string('secondName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('secondLastName')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_contact')->nullable();
            $table->string('contact')->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->integer('integrationDataId')->nullable();
            $table->integer('warehouseId')->nullable()->index('warehouseid');
            $table->integer('positionId')->nullable()->index('positionid')->comment('cargo del contacto');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnt_contacts');
    }
};
