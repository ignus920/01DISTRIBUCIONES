<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Customer\VntWarehouse;
use App\Models\Tenant\Customer\VntContacts;
use App\Models\Tenant\VntCustomer\VntCustomer;

class MigrateClientes extends Command
{
    protected $signature = 'migrate:clientes
                            {archivo : Ruta al archivo CSV}
                            {--dry-run : Simula la migración sin escribir en BD}
                            {--separador=, : Separador del CSV (default: coma)}';

    protected $description = 'Migra clientes desde CSV del sistema antiguo a las 4 tablas del sistema nuevo';

    /**
     * Mapeo tipo_identificacion antiguo → typeIdentificationId nuevo
     * Antiguo: 11=RC, 12=TI, 13=CC, 21=TE, 22=CE, 31=NIT, 41=Pasaporte, 42=Doc.Ext
     * Nuevo:    1=CC,  2=NIT, 3=CE,  4=Pasaporte, 5=TI,  6=RC
     */
    const TIPO_MAP = [
        11 => 6, // Registro Civil        → RC
        12 => 5, // Tarjeta de identidad  → TI
        13 => 1, // Cédula de ciudadanía  → CC
        21 => 3, // Tarjeta de extranjería→ CE
        22 => 3, // Cédula de extranjería → CE
        31 => 2, // NIT                   → NIT
        41 => 4, // Pasaporte             → Pasaporte
        42 => 3, // Doc. ID extranjero    → CE
    ];

    /**
     * Mapeo regimen antiguo → regimeId nuevo
     * Antiguo: 1=Simplificado, 2=Común
     * Nuevo:   1=Responsable IVA (Común), 2=No responsable IVA (Simplificado)
     */
    const REGIMEN_MAP = [
        1 => 2, // Simplificado → No responsable de IVA
        2 => 1, // Común        → Responsable de IVA
    ];

    private int $importados = 0;
    private int $omitidos   = 0;
    private int $errores    = 0;
    private array $logErrores = [];

    public function handle(): int
    {
        $archivo   = $this->argument('archivo');
        $dryRun    = $this->option('dry-run');
        $separador = $this->option('separador');

        // Validar archivo
        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return 1;
        }

        // Obtener company_id del dueño (primer registro de vnt_companies en la BD central)
        $ownerCompanyId = DB::table('vnt_companies')->value('id') ?? 0;

        $this->info("========================================");
        $this->info("  MIGRACIÓN DE CLIENTES" . ($dryRun ? " [DRY RUN]" : ""));
        $this->info("  Base de datos: distribuidora");
        $this->info("  Archivo: {$archivo}");
        $this->info("========================================");

        $handle  = fopen($archivo, 'r');
        $headers = fgetcsv($handle, 0, $separador);
        $headers = array_map('trim', $headers);

        $fila = 1;
        while (($row = fgetcsv($handle, 0, $separador)) !== false) {
            $fila++;

            // Ignorar filas vacías
            if (empty(array_filter($row))) continue;

            // Combinar headers con valores
            if (count($headers) !== count($row)) {
                $this->warn("  [SKIP] Fila {$fila}: columnas no coinciden con encabezado");
                $this->errores++;
                continue;
            }

            $data = array_combine($headers, $row);
            $data = array_map('trim', $data);

            try {
                $resultado = $this->migrarCliente($data, $ownerCompanyId, $dryRun);

                if ($resultado === 'omitido') {
                    $this->omitidos++;
                    $this->line("  <fg=yellow>[OMITIDO]</> Fila {$fila}: {$data['r_social']} ({$data['identificacion']}) — ya existe");
                } else {
                    $this->importados++;
                    $this->line("  <fg=green>[OK]</> Fila {$fila}: {$data['r_social']} ({$data['identificacion']})");
                }
            } catch (\Exception $e) {
                $this->errores++;
                $msg = "Fila {$fila}: {$data['r_social']} — " . $e->getMessage();
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

    private function migrarCliente(array $data, int $ownerCompanyId, bool $dryRun): string
    {
        // Saltar si ya existe por identificación
        if (VntCompany::where('identification', $data['identificacion'])->exists()) {
            return 'omitido';
        }

        if ($dryRun) {
            return 'ok';
        }

        DB::connection('tenant')->transaction(function () use ($data, $ownerCompanyId) {

            $typeId    = self::TIPO_MAP[$data['tipo_identificacion']] ?? 1;
            $typePerson = ((int)$data['tipo_identificacion'] === 31) ? 'Juridica' : 'Natural';
            $regimeId  = self::REGIMEN_MAP[$data['regimen']] ?? 2;
            $cityId    = $this->resolveCityId($data['ciudad'] ?? null);
            $status    = (int)($data['activo'] ?? 1);
            $now       = Carbon::now();

            // ── 1. vnt_companies ─────────────────────────────────────────
            $company = VntCompany::create([
                'businessName'         => $data['r_social'],
                'firstName'            => $data['nombre_contacto'] ?: null,
                'lastName'             => $data['apellido_contacto'] ?: null,
                'identification'       => $data['identificacion'],
                'typeIdentificationId' => $typeId,
                'typePerson'           => $typePerson,
                'regimeId'             => $regimeId,
                'billingEmail'         => $data['correo'] ?: null,
                'status'               => $status,
                'integrationDataId'    => $data['id_cliente'] ?: null,
            ]);

            // ── 2. vnt_warehouses (sucursal principal) ───────────────────
            $warehouse = VntWarehouse::create([
                'companyId'   => $company->id,
                'name'        => $data['descripcion'] ?: $data['r_social'],
                'address'     => $data['direccion'] ?: 'Sin dirección',
                'district'    => $data['barrio'] ?: null,
                'cityId'      => $cityId,
                'main'        => 1,
                'branch_type' => 'FIJA',
                'status'      => $status,
            ]);

            // ── 3. vnt_contacts ──────────────────────────────────────────
            VntContacts::create([
                'warehouseId'    => $warehouse->id,
                'firstName'      => $data['nombre_contacto'] ?: $data['r_social'],
                'lastName'       => $data['apellido_contacto'] ?: null,
                'email'          => $data['correo'] ?: null,
                'business_phone' => $data['telefono'] ?: null,
                'positionId'     => 1,
                'status'         => 1,
            ]);

            // ── 4. vnt_customers ─────────────────────────────────────────
            VntCustomer::create([
                'company_id'           => $ownerCompanyId,
                'typePerson'           => $typePerson,
                'typeIdentificationId' => $typeId,
                'identification'       => $data['identificacion'],
                'regimeId'             => $regimeId,
                'cityId'               => $cityId,
                'businessName'         => $data['r_social'],
                'billingEmail'         => $data['correo'] ?: null,
                'firstName'            => $data['nombre_contacto'] ?: null,
                'lastName'             => $data['apellido_contacto'] ?: null,
                'address'              => $data['direccion'] ?: null,
                'business_phone'       => $data['telefono'] ?: null,
                'status'               => $status,
            ]);

            // ── 5. tat_companies_routes (si tiene ruta asignada) ─────────
            if (!empty($data['id_ruta']) && (int)$data['id_ruta'] > 0) {
                // Verificar que la ruta exista en el sistema nuevo antes de asignar
                $routeExists = DB::connection('tenant')
                    ->table('tat_routes')
                    ->where('id', $data['id_ruta'])
                    ->exists();

                if ($routeExists) {
                    DB::connection('tenant')->table('tat_companies_routes')->insert([
                        'company_id' => $company->id,
                        'route_id'   => $data['id_ruta'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                // Si la ruta no existe en el sistema nuevo, se omite silenciosamente
                // (el cliente igual queda creado, solo sin ruta asignada)
            }
        });

        return 'ok';
    }

    /**
     * Busca el cityId por código DANE (campo country_code en tabla cities)
     */
    private function resolveCityId(?string $daneCode): ?int
    {
        if (empty($daneCode)) return null;

        return DB::connection('tenant')
            ->table('cities')
            ->where('country_code', $daneCode)
            ->value('id');
    }
}
