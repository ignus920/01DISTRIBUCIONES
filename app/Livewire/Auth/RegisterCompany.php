<?php

namespace App\Livewire\Auth;

use App\Models\Auth\User;
use App\Models\Central\VntMerchantType;
use App\Models\Central\VntModul;
use App\Models\Central\VntMerchantModul;
use App\Models\Central\VntCompany;
use App\Models\Central\VntContact;
use App\Models\Central\VntWarehouse;
use App\Models\Central\VntPlain;
use App\Models\Central\CnfCountry;
use App\Services\Tenant\TenantManager;
use App\Services\WhatsApp\WhatsAppService;
use App\Http\Traits\HasCommonValidation;
use App\Mail\WhatsAppTokenMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class RegisterCompany extends Component
{
    use HasCommonValidation;

    protected $listeners = ['country-changed' => 'updateCountry'];

    // Datos del contacto
    public string $firstName = '';
    public string $lastName = '';
    public string $phone_contact = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Datos de la empresa
    public string $businessName = '';
    public $countryId = null;
    public $merchant_type_id = null;

    // Datos de aceptación
    public bool $accept_terms = false;

    // Estados de notificaciones
    public string $successMessage = '';

    // Estados de progreso UX
    public bool $isRegistering = false;
    public string $currentStep = '';
    public int $progressPercentage = 0;

    // Colecciones
    public $merchant_types = [];
    public $countries = [];
    public $plains = [];

    public function mount()
    {
        $this->merchant_types = VntMerchantType::where('status', 1)->get();
        // Los módulos no se cargan automáticamente - son administrados por administradores globales
        $this->countries = CnfCountry::where('status', 1)->get();
        $this->plains = VntPlain::where('status', 1)->get();
    }

    public function updatedMerchantTypeId()
    {
        // Filtrar planes según el tipo de comercio seleccionado
        $this->plains = VntPlain::where('status', 1)
            ->where('merchantTypeId', $this->merchant_type_id)
            ->get();
    }

    public function updateCountry($countryId)
    {
        $this->countryId = $countryId;
        Log::info('País seleccionado', ['countryId' => $countryId]);
    }

    public function updatedEmail()
    {
        $this->validateEmailRealtime();
    }

    public function updatedPhoneContact()
    {
        $this->validatePhoneRealtime();
    }

    public function updatedBusinessName()
    {
        $this->validateBusinessNameRealtime();
    }

    public function updatedPassword()
    {
        $this->validatePasswordRealtime();
    }

    public function updatedPasswordConfirmation()
    {
        $this->validatePasswordConfirmationRealtime();
    }

    /**
     * Método de prueba para verificar que los mensajes funcionen
     */
    public function testMessages()
    {
        $this->isLoading = true;
        $this->loadingMessage = 'Probando mensaje de carga...';

        Log::info('Método testMessages ejecutado', [
            'isLoading' => $this->isLoading,
            'loadingMessage' => $this->loadingMessage
        ]);
    }

    public function testSuccess()
    {
        $this->isLoading = false;
        $this->successMessage = 'Mensaje de éxito funcionando correctamente!';

        Log::info('Método testSuccess ejecutado', [
            'isLoading' => $this->isLoading,
            'successMessage' => $this->successMessage
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        // Iniciar proceso de registro - Mostrar SweetAlert de carga
        Log::info('🚀 Iniciando registro - Emitiendo evento registration-started');
        $this->dispatch('registration-started');

        // Pequeño delay para asegurar que el SweetAlert se muestre
        sleep(1);

        $this->isRegistering = true;
        $this->progressPercentage = 10;
        $this->currentStep = 'Validando información...';

        // Aumentar tiempo de ejecución para creación de tenant
        set_time_limit(300); // 5 minutos

        Log::info('🔍 Validando información del registro');
        $validated = $this->validateRegistration();

        try {
            DB::beginTransaction();

            // Paso 1: Crear empresa
            $this->progressPercentage = 25;
            $this->currentStep = 'Configurando empresa...';
            sleep(1); // Simular procesamiento para mostrar progreso

            // 1. Crear la empresa
            $company = VntCompany::create([
                'businessName' => $this->businessName,
                'billingEmail' => $this->email,
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'status' => 1,
                'created_at' => now(),
            ]);

            // Paso 2: Crear contacto
            $this->progressPercentage = 40;
            $this->currentStep = 'Creando perfil de usuario...';

            $contact = VntContact::create([
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
                'phone_contact' => $this->phone_contact,
                'status' => 1,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);

            // 3. Crear warehouse principal
            $warehouse = VntWarehouse::create([
                'companyId' => $company->id,
                'name' => 'Principal',
                'address' => 'Dirección principal',
            ]);

            // Paso 3: Crear usuario
            $this->progressPercentage = 55;
            $this->currentStep = 'Configurando cuenta de acceso...';

            $validated['password'] = Hash::make($validated['password']);
            $userData = [
                'name' => $this->firstName . ' ' . $this->lastName,
                'email' => $this->email,
                'phone' => $this->phone_contact,
                'password' => $validated['password'],
                'profile_id' => 2,
                'contact_id' => $contact->id,
            ];
            $user = User::create($userData);
            event(new Registered($user));

            // Paso 4: Generar códigos de verificación
            $this->progressPercentage = 70;
            $this->currentStep = 'Enviando código de verificación...';

            $whatsappToken = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'whatsapp_token' => $whatsappToken,
                'whatsapp_token_expires_at' => now()->addMinutes(15),
            ]);

            // Enviar por WhatsApp
            try {
                $whatsappService = app(WhatsAppService::class);
                $whatsappService->enviarCodigoVerificacion(
                    $this->phone_contact,
                    $this->firstName . ' ' . $this->lastName,
                    $whatsappToken,
                    config('whatsapp.empresa.telefono'),
                    config('whatsapp.empresa.nombre')
                );
            } catch (\Exception $e) {
                // No interrumpir registro por errores de WhatsApp
            }

            // Enviar por email
            try {

                Mail::to($this->email)->send(new WhatsAppTokenMail(
                    $this->firstName . ' ' . $this->lastName,
                    $whatsappToken
                ));

                Log::info('✅ Código enviado por correo exitosamente', [
                    'user_email' => $this->email
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Error enviando email', [
                    'error' => $e->getMessage(),
                    'email' => $this->email
                ]);
                // No interrumpimos el registro por errores de email
            }

            // 5. Obtener plan por defecto para el tipo de comercio
            Log::info('📋 Configurando plan de servicio...');
            $defaultPlain = VntPlain::where('merchantTypeId', $this->merchant_type_id)
                ->where('status', 1)
                ->first();

            // 6. Crear SOLO el registro del tenant (sin base de datos física)
            Log::info('📝 Creando registro del tenant (sin base de datos física para agilizar el proceso)');
            $tenantManager = app(TenantManager::class);
            $tenant = $tenantManager->createTenantRecord([
                'name' => $this->businessName,
                'email' => $this->email,
                'company_id' => $company->id,
                'merchant_type_id' => $this->merchant_type_id,
                'plain_id' => $defaultPlain?->id,
                'afiliation_date' => now(),
                'end_test' => now()->addDays(30), // 30 días de prueba
            ], $user);

            // 7. Los módulos se asignan solo por administradores globales
            // No creamos automáticamente registros en vnt_merchant_moduls
            Log::info('🔧 Registro del tenant creado exitosamente. La base de datos se configurará cuando completes tus datos de empresa.');

            DB::commit();

            // Finalizar con éxito
            $this->successMessage = '¡Registro completado exitosamente! Tu cuenta ha sido creada. La base de datos se configurará cuando completes los datos de tu empresa.';

            // TODO: Enviar token por email o WhatsApp aquí

            Log::info('✅ Registro completado - Preparando para emitir evento registration-complete');
            session()->flash('status', '¡Cuenta creada exitosamente! Se ha enviado un token de verificación.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear registro completo: ' . $e->getMessage(), [
                'email' => $this->email,
                'businessName' => $this->businessName,
                'trace' => $e->getTraceAsString()
            ]);

            // Emitir evento de error para cerrar loading y mostrar error
            $this->dispatch('registration-error', [
                'title' => 'Error en el registro',
                'message' => 'Error al crear la cuenta: ' . $e->getMessage()
            ]);

            session()->flash('error', 'Error al crear la cuenta: ' . $e->getMessage());
            return;
        }

        // Log::info('🎉 Registro completado exitosamente', ['user_id' => $user->id, 'email' => $user->email]);

        // Auth::login($user); // Comentado: no hacer autologin automático

        // Redirigir a verificación de token
        // $this->redirect(route('verify-token'), navigate: true);

        Log::info('🚀 Emitiendo evento registration-complete');
        $this->dispatch('registration-complete', [
            'title' => '¡Registro Exitoso!',
            'message' => 'Tu cuenta ha sido creada. Revisa tu correo (o WhatsApp) para obtener tu código de verificación.',
            'redirectUrl' => route('verify-token')
        ]);
        Log::info('✅ Evento registration-complete emitido correctamente');

        // Limpiar el formulario después de un registro exitoso
        $this->clearForm();
    }

    /**
     * Limpiar todos los campos del formulario
     */
    private function clearForm(): void
    {
        Log::info('🧹 Limpiando formulario después del registro exitoso');

        // Limpiar datos del contacto
        $this->firstName = '';
        $this->lastName = '';
        $this->phone_contact = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';

        // Limpiar datos de la empresa
        $this->businessName = '';
        $this->countryId = null;
        $this->merchant_type_id = null;

        // Limpiar datos de aceptación
        $this->accept_terms = false;

        // Reset estados de progreso
        $this->isRegistering = false;
        $this->progressPercentage = 0;
        $this->currentStep = '';

        Log::info('✅ Formulario limpiado correctamente');
    }

    public function render()
    {
        return view('livewire.auth.register-company');
    }
}