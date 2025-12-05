<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_reasons')) {
            Schema::create('inv_reasons', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 255);
                $table->string('type', 255);
                $table->integer('status')->default(1);
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_reasons');
    }
};
