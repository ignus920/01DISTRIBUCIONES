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
        Schema::create('vnt_companies', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('businessName')->nullable();
            $table->string('billingEmail')->nullable();
            $table->string('firstName')->nullable();
            $table->integer('integrationDataId')->nullable();
            $table->string('identification', 15)->nullable()->unique('identification');
            $table->integer('checkDigit')->nullable()->comment('digito de verificacion');
            $table->string('lastName')->nullable();
            $table->string('secondLastName')->nullable();
            $table->string('secondName')->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->string('typePerson')->nullable();
            $table->integer('typeIdentificationId')->nullable()->index('typeidentificationid');
            $table->integer('regimeId')->nullable()->index('regimeid');
            $table->string('code_ciiu')->nullable();
            $table->integer('fiscalResponsabilityId')->nullable()->index('fiscalresponsabilityid');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnt_companies');
    }
};
