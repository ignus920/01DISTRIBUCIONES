<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_method_payments')) {
            Schema::create('vnt_method_payments', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 255);
                $table->integer('status')->nullable()->default(1);
                $table->string('description', 255);
                $table->integer('type');
                $table->string('method', 100)->nullable();
                $table->integer('bank')->nullable();
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_method_payments');
    }
};
