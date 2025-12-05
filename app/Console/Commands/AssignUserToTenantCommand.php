<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\User;
use App\Models\Auth\Tenant;

class AssignUserToTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:assign-user
                            {user-email : Email del usuario}
                            {tenant-id : ID del tenant}
                            {--role=user : Rol del usuario en el tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar un usuario a un tenant';

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
        $this->info('👤 Asignando usuario a tenant...');

        $userEmail = $this->argument('user-email');
        $tenantId = $this->argument('tenant-id');
        $role = $this->option('role');

        try {
            // Buscar usuario
            $user = User::where('email', $userEmail)->first();

            if (!$user) {
                $this->error("❌ Usuario con email '{$userEmail}' no encontrado.");
                return 1;
            }

            // Buscar tenant
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->error("❌ Tenant con ID '{$tenantId}' no encontrado.");
                return 1;
            }

            // Verificar si ya está asignado
            if ($user->hasAccessToTenant($tenantId)) {
                if ($this->confirm('⚠️  El usuario ya tiene acceso a este tenant. ¿Desea actualizar el rol?', true)) {
                    $user->tenants()->updateExistingPivot($tenantId, ['role' => $role]);
                    $this->info("✅ Rol actualizado exitosamente a '{$role}'.");
                } else {
                    return 0;
                }
            } else {
                // Asignar usuario al tenant
                $this->tenantManager->assignUser($tenant, $user, $role);
                $this->info('✅ Usuario asignado exitosamente!');
            }

            $this->info('');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Usuario', $user->name],
                    ['Email', $user->email],
                    ['Tenant', $tenant->name],
                    ['Rol', $role],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
