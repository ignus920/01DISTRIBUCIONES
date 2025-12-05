<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_petty_cash')) {
            Schema::create('vnt_petty_cash', function (Blueprint $table) {
                $table->id('id');
                $table->integer('base');
                $table->integer('consecutive');
                $table->integer('status')->nullable()->default(1);
                $table->string('dateClose', 255)->nullable();
                $table->integer('userIdClose')->nullable();
                $table->integer('userIdOpen')->nullable();
                $table->integer('warehouseId')->nullable();
                $table->index('warehouseId');
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_petty_cash');
    }
};
