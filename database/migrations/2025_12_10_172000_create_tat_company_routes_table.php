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
        Schema::create('tat_company_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('route_id');
            $table->integer('sales_order')->default(1);
            $table->integer('delivery_order')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('company_id')
                  ->references('id')
                  ->on('vnt_companies')
                  ->onDelete('cascade');
            
            $table->foreign('route_id')
                  ->references('id')
                  ->on('tat_routes')
                  ->onDelete('cascade');

            // Indexes for better query performance
            $table->index(['route_id', 'company_id']);
            $table->index('sales_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tat_company_routes');
    }
};
