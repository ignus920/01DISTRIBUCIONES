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
        if (!Schema::hasTable('cnf_positions')) {
            Schema::create('cnf_positions', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name', 50);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();   // created_at, updated_at
                $table->softDeletes();  // deleted_at
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cnf_positions');
    }
};
