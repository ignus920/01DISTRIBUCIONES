<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsApp\WhatsAppService;

class TestWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test {phone} {--name=Usuario} {--token=123456}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el envío de mensajes de WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $name = $this->option('name');
        $token = $this->option('token');

        $this->info('🚀 Iniciando prueba de WhatsApp...');
        $this->info("📱 Teléfono: {$phone}");
        $this->info("👤 Nombre: {$name}");
        $this->info("🔐 Token: {$token}");

        // Verificar configuración
        $this->info('📋 Verificando configuración...');
        $this->table(
            ['Configuración', 'Valor'],
            [
                ['API URL', config('whatsapp.api_url')],
                ['JWT Secret', config('whatsapp.jwt_secret') ? '***configurado***' : 'NO CONFIGURADO'],
                ['Empresa Email', config('whatsapp.empresa_email')],
                ['Empresa Teléfono', config('whatsapp.empresa.telefono')],
                ['Empresa Nombre', config('whatsapp.empresa.nombre')],
                ['Template', config('whatsapp.default_template')],
                ['Imagen', config('whatsapp.default_image')],
            ]
        );

        // Validar teléfono
        $whatsappService = app(WhatsAppService::class);

        if (!$whatsappService->validarTelefono($phone)) {
            $this->error("❌ El teléfono {$phone} no es válido");
            $this->info("💡 Formato esperado: 3001234567 (10 dígitos que empiecen con 3)");
            return 1;
        }

        $this->info("✅ Teléfono válido");

        // Enviar mensaje
        $this->info('📤 Enviando mensaje de WhatsApp...');

        $result = $whatsappService->enviarCodigoVerificacion(
            $phone,
            $name,
            $token,
            config('whatsapp.empresa.telefono'),
            config('whatsapp.empresa.nombre')
        );

        if ($result['success']) {
            $this->info('✅ Mensaje enviado exitosamente!');
            $this->info('📄 Respuesta de la API:');
            $this->line(json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Error al enviar mensaje:');
            $this->error($result['error']);

            if (isset($result['details'])) {
                $this->error('📄 Detalles:');
                $this->error($result['details']);
            }
        }

        return $result['success'] ? 0 : 1;
    }
}