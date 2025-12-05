<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_status')) {
            Schema::create('inv_status', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 100)->default('name');
                $table->integer('application');
                $table->integer('status');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_status');
    }
};
