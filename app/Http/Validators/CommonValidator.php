<?php

namespace App\Http\Validators;

use App\Models\Auth\User;
use App\Models\Central\VntContact;
use App\Models\Central\VntCompany;
use Illuminate\Validation\Rules;

class CommonValidator
{
    /**
     * Reglas de validación para campos de usuario
     */
    public static function userRules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                'unique:'.User::class.',email',
                'unique:vnt_contacts,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ],
            'password_confirmation' => [
                'required',
                'string'
            ],
        ];
    }

    /**
     * Reglas de validación para contacto
     */
    public static function contactRules(): array
    {
        return [
            'phone_contact' => [
                'required',
                'string',
                'regex:/^3[0-9]{9}$/',
                'digits:10',
                'unique:vnt_contacts,phone_contact'
            ],
        ];
    }

    /**
     * Reglas de validación para empresa
     */
    public static function companyRules(): array
    {
        return [
            'businessName' => [
                'required',
                'string',
                'max:255',
                'unique:vnt_companies,businessName'
            ],
            'countryId' => ['required', 'exists:central.countries,id'],
            'merchant_type_id' => ['required', 'exists:vnt_merchant_types,id'],
        ];
    }

    /**
     * Reglas de validación para términos y condiciones
     */
    public static function termsRules(): array
    {
        return [
            'accept_terms' => ['required', 'accepted'],
        ];
    }

    /**
     * Todas las reglas de registro combinadas
     */
    public static function registrationRules(): array
    {
        return array_merge(
            self::userRules(),
            self::contactRules(),
            self::companyRules(),
            self::termsRules()
        );
    }

    /**
     * Mensajes de error personalizados
     */
    public static function messages(): array
    {
        return [
            // Mensajes para email
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser una dirección de correo válida (ej: usuario@dominio.com).',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',

            // Mensajes para teléfono
            'phone_contact.unique' => 'Este número de teléfono ya está registrado en el sistema.',
            'phone_contact.regex' => 'El número debe ser un celular colombiano válido que inicie con 3 (ej: 3123456789).',
            'phone_contact.digits' => 'El número de celular debe tener exactamente 10 dígitos.',

            // Mensajes para empresa
            'businessName.unique' => 'Ya existe una empresa registrada con este nombre.',

            // Mensajes para términos
            'accept_terms.accepted' => 'Debe aceptar los términos y condiciones para continuar.',

            // Mensajes para campos requeridos
            'firstName.required' => 'El nombre es obligatorio.',
            'lastName.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password_confirmation.required' => 'Debe confirmar su contraseña.',
            'phone_contact.required' => 'El número telefónico es obligatorio.',
            'businessName.required' => 'El nombre del negocio es obligatorio.',
            'countryId.required' => 'Debe seleccionar un país.',
            'merchant_type_id.required' => 'Debe seleccionar un tipo de negocio.',

            // Mensajes para formato
            'email.email' => 'Debe ser una dirección de correo válida.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe contener al menos: 1 minúscula, 1 mayúscula y 1 número.',

            // Mensajes para existencia
            'countryId.exists' => 'El país seleccionado no es válido.',
            'merchant_type_id.exists' => 'El tipo de negocio seleccionado no es válido.',
        ];
    }

    /**
     * Reglas de validación para email (para validación en tiempo real)
     */
    public static function emailValidationRules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                'unique:'.User::class.',email',
                'unique:vnt_contacts,email'
            ]
        ];
    }

    /**
     * Reglas de validación para teléfono (para validación en tiempo real)
     */
    public static function phoneValidationRules(): array
    {
        return [
            'phone_contact' => [
                'required',
                'string',
                'regex:/^3[0-9]{9}$/',
                'digits:10',
                'unique:vnt_contacts,phone_contact'
            ]
        ];
    }

    /**
     * Reglas de validación para nombre de empresa (para validación en tiempo real)
     */
    public static function businessNameValidationRules(): array
    {
        return [
            'businessName' => [
                'required',
                'string',
                'max:255',
                'unique:vnt_companies,businessName'
            ]
        ];
    }

    /**
     * Reglas de validación para contraseña (para validación en tiempo real)
     */
    public static function passwordValidationRules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ]
        ];
    }

    /**
     * Reglas de validación para confirmación de contraseña (para validación en tiempo real)
     */
    public static function passwordConfirmationValidationRules(): array
    {
        return [
            'password_confirmation' => [
                'required',
                'string',
                'same:password'
            ]
        ];
    }

    /**
     * Mensajes específicos para validación en tiempo real
     */
    public static function realtimeMessages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser una dirección de correo válida (ej: usuario@dominio.com).',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',
            'phone_contact.unique' => 'Este número de teléfono ya está registrado en el sistema.',
            'phone_contact.regex' => 'El número debe ser un celular colombiano válido que inicie con 3 (ej: 3123456789).',
            'phone_contact.digits' => 'El número de celular debe tener exactamente 10 dígitos.',
            'businessName.unique' => 'Ya existe una empresa registrada con este nombre.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe contener al menos: 1 minúscula, 1 mayúscula y 1 número.',
            'password_confirmation.required' => 'Debe confirmar su contraseña.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',
        ];
    }
}