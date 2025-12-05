<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vnt_companies')) {
            Schema::create('vnt_companies', function (Blueprint $table) {
                $table->id('id');
                $table->string('businessName', 255)->nullable();
                $table->string('billingEmail', 255)->nullable();
                $table->string('firstName', 255)->nullable();
                $table->integer('integrationDataId')->nullable();
                $table->string('identification', 15)->nullable()->unique();
                $table->integer('checkDigit')->nullable();
                $table->string('lastName', 255)->nullable();
                $table->string('secondLastName', 255)->nullable();
                $table->string('secondName', 255)->nullable();
                $table->integer('status')->nullable()->default(1);
                $table->string('typePerson', 255)->nullable();
                $table->integer('typeIdentificationId')->nullable();
                $table->integer('regimeId')->nullable();
                $table->string('code_ciiu', 255)->nullable();
                $table->integer('fiscalResponsabilityId')->nullable();
                $table->timestamps();        // created_at, updated_at
                $table->softDeletes();       // deleted_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vnt_companies');
    }
};
