<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\User;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create
                            {name : Nombre de la empresa}
                            {email : Email de la empresa}
                            {--phone= : Teléfono de la empresa}
                            {--address= : Dirección de la empresa}
                            {--owner-email= : Email del propietario}
                            {--db-name= : Nombre de la base de datos (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un nuevo tenant (empresa) con su base de datos';

    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        parent::__construct();
        $this->tenantManager = $tenantManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Creando nuevo tenant...');

        $data = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'phone' => $this->option('phone'),
            'address' => $this->option('address'),
            'db_name' => $this->option('db-name'),
        ];

        try {
            // Buscar propietario si se proporciona
            $owner = null;
            if ($ownerEmail = $this->option('owner-email')) {
                $owner = User::where('email', $ownerEmail)->first();

                if (!$owner) {
                    $this->error("❌ Usuario con email '{$ownerEmail}' no encontrado.");

                    if ($this->confirm('¿Desea crear el usuario?', true)) {
                        $name = $this->ask('Nombre del usuario');
                        $password = $this->secret('Contraseña del usuario');

                        $owner = User::create([
                            'name' => $name,
                            'email' => $ownerEmail,
                            'password' => bcrypt($password),
                        ]);

                        $this->info("✅ Usuario creado exitosamente.");
                    } else {
                        return 1;
                    }
                }
            }

            // Crear tenant
            $tenant = $this->tenantManager->create($data, $owner);

            $this->info('');
            $this->info('✅ Tenant creado exitosamente!');
            $this->info('');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $tenant->id],
                    ['Nombre', $tenant->name],
                    ['Email', $tenant->email],
                    ['Base de Datos', $tenant->db_name],
                    ['Propietario', $owner ? $owner->email : 'N/A'],
                ]
            );

            $this->info('');
            $this->info('🎉 El tenant está listo para usar!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error al crear tenant: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
