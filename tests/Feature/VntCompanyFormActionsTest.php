<?php

namespace Tests\Feature;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Tenant\VntCompany\VntCompanyForm;

/**
 * Test suite to verify Livewire method integration for VntCompanyForm actions dropdown
 * 
 * This test verifies that all wire:click methods in the actions dropdown menu
 * are properly integrated and callable with the correct parameters.
 */
class VntCompanyFormActionsTest extends TestCase
{
    /** @test */
    public function it_has_open_warehouse_modal_method_that_accepts_company_id()
    {
        // Verify the method exists and accepts a parameter
        $this->assertTrue(
            method_exists(VntCompanyForm::class, 'openWarehouseModal'),
            'openWarehouseModal method does not exist on VntCompanyForm'
        );
        
        $reflection = new \ReflectionMethod(VntCompanyForm::class, 'openWarehouseModal');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters, 'openWarehouseModal should accept exactly 1 parameter');
        $this->assertEquals('companyId', $parameters[0]->getName(), 'Parameter should be named companyId');
    }

    /** @test */
    public function it_has_open_contact_modal_method_that_accepts_company_id()
    {
        // Verify the method exists and accepts a parameter
        $this->assertTrue(
            method_exists(VntCompanyForm::class, 'openContactModal'),
            'openContactModal method does not exist on VntCompanyForm'
        );
        
        $reflection = new \ReflectionMethod(VntCompanyForm::class, 'openContactModal');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters, 'openContactModal should accept exactly 1 parameter');
        $this->assertEquals('companyId', $parameters[0]->getName(), 'Parameter should be named companyId');
    }

    /** @test */
    public function it_has_edit_method_that_accepts_id()
    {
        // Verify the method exists and accepts a parameter
        $this->assertTrue(
            method_exists(VntCompanyForm::class, 'edit'),
            'edit method does not exist on VntCompanyForm'
        );
        
        $reflection = new \ReflectionMethod(VntCompanyForm::class, 'edit');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters, 'edit should accept exactly 1 parameter');
        $this->assertEquals('id', $parameters[0]->getName(), 'Parameter should be named id');
    }

    /** @test */
    public function it_has_delete_method_that_accepts_id()
    {
        // Verify the method exists and accepts a parameter
        $this->assertTrue(
            method_exists(VntCompanyForm::class, 'delete'),
            'delete method does not exist on VntCompanyForm'
        );
        
        $reflection = new \ReflectionMethod(VntCompanyForm::class, 'delete');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters, 'delete should accept exactly 1 parameter');
        $this->assertEquals('id', $parameters[0]->getName(), 'Parameter should be named id');
    }

    /** @test */
    public function it_verifies_open_warehouse_modal_sets_correct_properties()
    {
        // Verify the method implementation sets the correct properties
        $component = new VntCompanyForm();
        
        // Call the method directly
        $component->openWarehouseModal(123);
        
        // Verify the properties are set correctly
        $this->assertTrue($component->showWarehouseModal, 'showWarehouseModal should be true');
        $this->assertEquals(123, $component->selectedCompanyId, 'selectedCompanyId should be 123');
    }

    /** @test */
    public function it_verifies_open_contact_modal_sets_correct_properties()
    {
        // Verify the method implementation sets the correct properties
        $component = new VntCompanyForm();
        
        // Call the method directly
        $component->openContactModal(456);
        
        // Verify the properties are set correctly
        $this->assertTrue($component->showContactModal, 'showContactModal should be true');
        $this->assertEquals(456, $component->selectedCompanyIdForContacts, 'selectedCompanyIdForContacts should be 456');
    }
}
