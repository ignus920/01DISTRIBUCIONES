<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Livewire\Tenant\VntCompany\VntCompanyForm;
use App\Models\Tenant\Customer\VntCompany;
use Livewire\Livewire;

class CustomerFormValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function identification_must_be_at_least_5_characters()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('typeIdentificationId', 1)
            ->set('identification', '1234') // Solo 4 caracteres
            ->call('save')
            ->assertHasErrors(['identification' => 'min']);
    }

    /** @test */
    public function identification_must_be_numeric()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('typeIdentificationId', 1)
            ->set('identification', '12345ABC') // Contiene letras
            ->call('save')
            ->assertHasErrors(['identification' => 'regex']);
    }

    /** @test */
    public function verification_digit_must_be_numeric_for_nit()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('typeIdentificationId', 2) // NIT
            ->set('identification', '123456789')
            ->set('verification_digit', 'A') // Letra en lugar de número
            ->call('save')
            ->assertHasErrors(['verification_digit' => 'regex']);
    }

    /** @test */
    public function business_phone_must_have_valid_format()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('business_phone', '123') // Muy corto
            ->call('save')
            ->assertHasErrors(['business_phone' => 'min']);
    }

    /** @test */
    public function business_phone_accepts_valid_formats()
    {
        $validFormats = [
            '+57 300 123 4567',
            '3001234567',
            '(300) 123-4567',
            '+57-300-123-4567',
        ];

        foreach ($validFormats as $format) {
            Livewire::test(VntCompanyForm::class)
                ->set('business_phone', $format)
                ->assertHasNoErrors('business_phone');
        }
    }

    /** @test */
    public function first_name_must_contain_only_letters()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('typePerson', 'Natural')
            ->set('firstName', 'Juan123') // Contiene números
            ->call('save')
            ->assertHasErrors(['firstName' => 'regex']);
    }

    /** @test */
    public function first_name_accepts_spanish_characters()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('typePerson', 'Natural')
            ->set('firstName', 'José María')
            ->assertHasNoErrors('firstName');
    }

    /** @test */
    public function code_ciiu_must_be_numeric()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('code_ciiu', '4711ABC') // Contiene letras
            ->call('save')
            ->assertHasErrors(['code_ciiu' => 'regex']);
    }

    /** @test */
    public function district_is_required()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('district', '')
            ->call('save')
            ->assertHasErrors(['district' => 'required']);
    }

    /** @test */
    public function district_must_be_at_least_3_characters()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('district', 'AB') // Solo 2 caracteres
            ->call('save')
            ->assertHasErrors(['district' => 'min']);
    }

    /** @test */
    public function warehouse_address_must_be_at_least_5_characters()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('warehouseAddress', 'Cll') // Solo 3 caracteres
            ->call('save')
            ->assertHasErrors(['warehouseAddress' => 'min']);
    }

    /** @test */
    public function warehouse_city_is_required()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('warehouseCityId', null)
            ->call('save')
            ->assertHasErrors(['warehouseCityId' => 'required']);
    }

    /** @test */
    public function postal_code_must_be_numeric()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('warehousePostcode', '110ABC') // Contiene letras
            ->call('save')
            ->assertHasErrors(['warehousePostcode' => 'regex']);
    }

    /** @test */
    public function business_name_must_be_at_least_3_characters_for_juridical_person()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('typePerson', 'Juridica')
            ->set('businessName', 'AB') // Solo 2 caracteres
            ->call('save')
            ->assertHasErrors(['businessName' => 'min']);
    }

    /** @test */
    public function email_must_have_valid_format()
    {
        Livewire::test(VntCompanyForm::class)
            ->set('billingEmail', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['billingEmail' => 'email']);
    }
}
