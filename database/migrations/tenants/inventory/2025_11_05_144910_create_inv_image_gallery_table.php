<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inv_image_gallery')) {
           Schema::create('inv_image_gallery', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary(); // INT, auto-increment, PK
            $table->unsignedInteger('itemId');
            $table->foreign('itemId')->references('id')->on('inv_items');
            $table->text('img_path')->nullable();
            $table->enum('type', ["PRINCIPAL","GALERIA"])->default('PRINCIPAL');
            $table->timestamps();        // created_at, updated_at
            $table->softDeletes();       // deleted_at
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_image_gallery');
    }
};
