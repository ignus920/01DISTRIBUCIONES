<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ven_delivery_types')) {
            Schema::create('ven_delivery_types', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 255);
                $table->integer('status')->default(1);
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ven_delivery_types');
    }
};
