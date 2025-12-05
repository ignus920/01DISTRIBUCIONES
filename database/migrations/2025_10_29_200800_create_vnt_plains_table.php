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
        Schema::create('vnt_plains', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100)->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->enum('type', ['Vendido', 'Saas'])->nullable();
            $table->integer('merchantTypeId')->nullable()->index('merchanttypeid');
            $table->integer('warehoseQty')->default(1);
            $table->integer('usersQty')->default(2);
            $table->integer('storesQty')->default(1);
            $table->dateTime('create_at');
            $table->dateTime('update_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnt_plains');
    }
};
