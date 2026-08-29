<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MigrateUsuarios extends Command
{
    protected $signature = 'migrate:usuarios
                            {archivo : Ruta al archivo CSV}
                            {--dry-run : Simula la migración sin escribir en BD}
                            {--separador=, : Separador del CSV (default: coma)}';

    protected $description = 'Migra usuarios desde CSV del sistema antiguo a users, vnt_contacts y user_tenants';

    /**
     * Mapeo cargo antiguo → profile_id nuevo
     */
    const CARGO_PROFILE = [
        'administrador' => 2,
        'vendedor'      => 4,
        'entregas'      => 13,
    ];

    /**
     * Bodega interna del sistema nuevo (warehouseId fijo para usuarios internos)
     */
    const WAREHOUSE_ID = 8;

    /**
     * Contraseña por defecto para todos los usuarios migrados
     */
    const PASSWORD_DEFAULT = '12345678';

    private int $importados = 0;
    private int $omitidos   = 0;
    private int $errores    = 0;
    private array $logErrores = [];

    public function handle(): int
    {
        $archivo   = $this->argument('archivo');
        $dryRun    = $this->option('dry-run');
        $separador = $this->option('separador');

        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return 1;
        }

        // Obtener tenant_id
        $tenantId = DB::table('tenants')->value('id');
        if (!$tenantId) {
            $this->error("No se encontró ningún tenant en la BD.");
            return 1;
        }

        // Verificar que la bodega existe
        $warehouseExists = DB::table('vnt_warehouses')->where('id', self::WAREHOUSE_ID)->exists();
        if (!$warehouseExists) {
            $this->error("La bodega ID=" . self::WAREHOUSE_ID . " no existe en vnt_warehouses.");
            return 1;
        }

        $this->info("========================================");
        $this->info("  MIGRACIÓN DE USUARIOS" . ($dryRun ? " [DRY RUN]" : ""));
        $this->info("  Tenant: {$tenantId} | Bodega: " . self::WAREHOUSE_ID);
        $this->info("  Contraseña por defecto: " . self::PASSWORD_DEFAULT);
        $this->info("  Archivo: {$archivo}");
        $this->info("========================================");

        $handle  = fopen($archivo, 'r');

        // Eliminar BOM UTF-8 si existe (\xEF\xBB\xBF)
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 0, $separador);
        $headers = array_map(fn($h) => trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)), $headers);

        $fila = 1;
        while (($row = fgetcsv($handle, 0, $separador)) !== false) {
            $fila++;

            if (empty(array_filter($row))) continue;

            if (count($headers) !== count($row)) {
                $this->warn("  [SKIP] Fila {$fila}: columnas no coinciden con encabezado");
                $this->errores++;
                continue;
            }

            $data = array_combine($headers, $row);
            $data = array_map('trim', $data);

            try {
                $resultado = $this->migrarUsuario($data, $tenantId, $dryRun);

                $nombre = $data['nombre'] ?? 'N/A';
                $cargo  = $data['cargo']  ?? 'N/A';

                if ($resultado === 'omitido') {
                    $this->omitidos++;
                    $this->line("  <fg=yellow>[OMITIDO]</> Fila {$fila}: {$nombre} — ya existe");
                } else {
                    $this->importados++;
                    $this->line("  <fg=green>[OK]</> Fila {$fila}: {$nombre} — {$cargo}");
                }
            } catch (\Exception $e) {
                $this->errores++;
                $msg = "Fila {$fila}: " . ($data['nombre'] ?? 'N/A') . " — " . $e->getMessage();
                $this->logErrores[] = $msg;
                $this->error("  [ERROR] {$msg}");
            }
        }

        fclose($handle);

        $this->newLine();
        $this->info("========================================");
        $this->info("  RESUMEN" . ($dryRun ? " (DRY RUN — nada fue escrito)" : ""));
        $this->info("========================================");
        $this->table(
            ['✅ Importados', '⏭  Omitidos', '❌ Errores', 'Total filas'],
            [[$this->importados, $this->omitidos, $this->errores, $fila - 1]]
        );

        if (!empty($this->logErrores)) {
            $this->newLine();
            $this->warn("Detalle de errores:");
            foreach ($this->logErrores as $err) {
                $this->warn("  • {$err}");
            }
        }

        return $this->errores > 0 ? 1 : 0;
    }

    private function migrarUsuario(array $data, string $tenantId, bool $dryRun): string
    {
        // Usar email si es válido, si no construir uno desde el login
        $emailRaw = trim($data['email'] ?? '');
        $login    = trim($data['login'] ?? '');

        if (!empty($emailRaw) && filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
            $email = $emailRaw;
        } elseif (!empty($login)) {
            // Construir email placeholder desde el login
            $slug  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $login));
            $email = $slug . '@migrado.local';
        } else {
            throw new \Exception("Sin email ni login para identificar el usuario");
        }

        // Saltar si ya existe
        if (DB::table('users')->where('email', $email)->exists()) {
            return 'omitido';
        }

        if ($dryRun) {
            return 'ok';
        }

        $cargoKey  = strtolower(trim($data['cargo'] ?? ''));
        $profileId = self::CARGO_PROFILE[$cargoKey] ?? 4; // default: Vendedor
        $status    = (int)($data['condicion'] ?? 1);
        $now       = Carbon::now();

        DB::beginTransaction();
        try {
            // ── 1. vnt_contacts ──────────────────────────────────────────
            $contact = DB::table('vnt_contacts')->insertGetId([
                'firstName'      => $data['nombre'],
                'secondName'     => null,
                'lastName'       => null,
                'secondLastName' => null,
                'email'          => $email,
                'business_phone' => $data['telefono'] ?: null,
                'warehouseId'    => self::WAREHOUSE_ID,
                'positionId'     => $this->resolvePositionId($cargoKey),
                'status'         => $status,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // ── 2. users ─────────────────────────────────────────────────
            $userId = DB::table('users')->insertGetId([
                'name'                       => $data['nombre'],
                'email'                      => $email,
                'password'                   => Hash::make(self::PASSWORD_DEFAULT),
                'phone'                      => $data['telefono'] ?: null,
                'profile_id'                 => $profileId,
                'contact_id'                 => $contact,
                'avatar'                     => null,
                'two_factor_enabled'         => 0,
                'two_factor_failed_attempts' => 0,
                'two_factor_locked_until'    => null,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);

            // ── 3. user_tenants ──────────────────────────────────────────
            DB::table('user_tenants')->insert([
                'user_id'   => $userId,
                'tenant_id' => $tenantId,
                'role'      => $profileId,
                'is_active' => $status,
                'created_at'=> $now,
                'updated_at'=> $now,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return 'ok';
    }

    /**
     * Mapea el cargo al positionId de cnf_positions
     * Si no existe el cargo, retorna 1 como default
     */
    private function resolvePositionId(string $cargo): int
    {
        $position = DB::table('cnf_positions')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . $cargo . '%'])
            ->value('id');

        return $position ?? 1;
    }
}
