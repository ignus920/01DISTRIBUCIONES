<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_reasons_petty_cash')) {
            Schema::create('vnt_reasons_petty_cash', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 255);
                $table->integer('status')->nullable()->default(1);
                $table->string('type', 255);
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_reasons_petty_cash');
    }
};
