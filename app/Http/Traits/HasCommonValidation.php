<?php

namespace App\Http\Traits;

use App\Http\Validators\CommonValidator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

trait HasCommonValidation
{
    /**
     * Valida email en tiempo real
     */
    public function validateEmailRealtime()
    {
        Log::info('=== Validando email en tiempo real ===', ['email' => $this->email]);

        // Limpiar errores si el campo está vacío
        if (empty($this->email)) {
            Log::info('Email vacío, saltando validación');
            return;
        }

        try {
            $this->validateOnly('email',
                CommonValidator::emailValidationRules(),
                CommonValidator::realtimeMessages()
            );
            Log::info('Email validado correctamente');
        } catch (ValidationException $e) {
            Log::info('Error de validación capturado (esto es normal)', ['errors' => $e->errors()]);
            throw $e; // Re-lanzar para que Livewire lo maneje
        }
    }

    /**
     * Valida teléfono en tiempo real
     */
    public function validatePhoneRealtime()
    {
        // Limpiar errores si el campo está vacío
        if (empty($this->phone_contact)) {
            return;
        }

        $this->validateOnly('phone_contact',
            CommonValidator::phoneValidationRules(),
            CommonValidator::realtimeMessages()
        );
    }

    /**
     * Valida nombre de empresa en tiempo real
     */
    public function validateBusinessNameRealtime()
    {
        // Limpiar errores si el campo está vacío
        if (empty($this->businessName)) {
            return;
        }

        $this->validateOnly('businessName',
            CommonValidator::businessNameValidationRules(),
            CommonValidator::realtimeMessages()
        );
    }

    /**
     * Valida todos los campos de registro
     */
    public function validateRegistration()
    {
        return $this->validate(
            CommonValidator::registrationRules(),
            CommonValidator::messages()
        );
    }

    /**
     * Valida solo campos de usuario
     */
    public function validateUser()
    {
        return $this->validate(
            CommonValidator::userRules(),
            CommonValidator::messages()
        );
    }

    /**
     * Valida solo campos de empresa
     */
    public function validateCompany()
    {
        return $this->validate(
            CommonValidator::companyRules(),
            CommonValidator::messages()
        );
    }

    /**
     * Valida solo campos de contacto
     */
    public function validateContact()
    {
        return $this->validate(
            CommonValidator::contactRules(),
            CommonValidator::messages()
        );
    }

    /**
     * Valida contraseña en tiempo real
     */
    public function validatePasswordRealtime()
    {
        // Limpiar errores si el campo está vacío
        if (empty($this->password)) {
            return;
        }

        $this->validateOnly('password',
            CommonValidator::passwordValidationRules(),
            CommonValidator::realtimeMessages()
        );
    }

    /**
     * Valida confirmación de contraseña en tiempo real
     */
    public function validatePasswordConfirmationRealtime()
    {
        // Limpiar errores si el campo está vacío
        if (empty($this->password_confirmation)) {
            return;
        }

        $this->validateOnly('password_confirmation',
            CommonValidator::passwordConfirmationValidationRules(),
            CommonValidator::realtimeMessages()
        );
    }
}