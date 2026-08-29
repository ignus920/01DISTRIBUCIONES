<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateItemsCost extends Command
{
    protected $signature = 'items:update-cost
                            {archivo : Ruta al archivo CSV}
                            {--dry-run : Simula sin escribir en BD}
                            {--con-precio : También actualiza los registros de precio en inv_values}
                            {--crear : Crea el item si no existe (requiere --command-id, --brand-id, --house-id, --unit-id)}
                            {--command-id=1 : ID por defecto para commandId al crear items}
                            {--brand-id=1 : ID por defecto para brandId al crear items}
                            {--house-id=1 : ID por defecto para houseId al crear items}
                            {--unit-id=1 : ID por defecto para purchasing_unit y consumption_unit al crear items}
                            {--tax-id=1 : ID por defecto para taxId al crear items}';

    protected $description = 'Actualiza costo (y precio) en inv_values desde el CSV del sistema anterior';

    private int $actualizados = 0;
    private int $creados      = 0;
    private int $omitidos     = 0;
    private int $errores      = 0;
    private array $logErrores = [];

    const LABELS_COSTO  = ['Costo Inicial', 'Costo'];
    const LABELS_PRECIO = ['Precio Base', 'Precio Regular', 'Precio Crédito'];

    public function handle(): int
    {
        $archivo   = $this->argument('archivo');
        $dryRun    = $this->option('dry-run');
        $conPrecio = $this->option('con-precio');
        $crear     = $this->option('crear');

        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return 1;
        }

        $this->info("========================================");
        $this->info("  ACTUALIZACIÓN DE COSTOS — inv_values" . ($dryRun ? " [DRY RUN]" : ""));
        $this->info("  Archivo  : {$archivo}");
        $this->info("  Campos   : costo" . ($conPrecio ? " + precio" : ""));
        $this->info("  Crear    : " . ($crear ? "sí" : "no"));
        $this->info("========================================");

        $handle  = fopen($archivo, 'r');
        $headers = fgetcsv($handle, 0, ';');
        $headers = array_map(fn($h) => trim(str_replace('"', '', $h)), $headers);

        $fila = 1;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $fila++;

            if (empty(array_filter($row))) continue;

            if (count($headers) !== count($row)) {
                $this->warn("  [SKIP] Fila {$fila}: columnas no coinciden");
                $this->errores++;
                continue;
            }

            $data = array_combine($headers, $row);
            $data = array_map(fn($v) => trim(str_replace('"', '', $v)), $data);

            try {
                $resultado = $this->procesarItem($data, $dryRun, $conPrecio, $crear);

                $costo = number_format((float) $data['costo'], 2);
                $extra = $conPrecio ? " | precio=" . number_format((float) $data['precio'], 2) : '';

                match ($resultado) {
                    'omitido' => (function () use ($fila, $data) {
                        $this->omitidos++;
                        $this->line("  <fg=yellow>[OMITIDO]</> Fila {$fila}: id={$data['iditem']} — no existe en inv_items");
                    })(),
                    'creado' => (function () use ($fila, $data, $costo, $extra) {
                        $this->creados++;
                        $this->line("  <fg=cyan>[CREADO]</>  Fila {$fila}: id={$data['iditem']} {$data['nombre']} — costo={$costo}{$extra}");
                    })(),
                    default => (function () use ($fila, $data, $costo, $extra) {
                        $this->actualizados++;
                        $this->line("  <fg=green>[OK]</>     Fila {$fila}: id={$data['iditem']} {$data['nombre']} — costo={$costo}{$extra}");
                    })(),
                };
            } catch (\Exception $e) {
                $this->errores++;
                $msg = "Fila {$fila} (id={$data['iditem']}): " . $e->getMessage();
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
            ['✅ Actualizados', '🆕 Creados', '⏭  Omitidos', '❌ Errores', 'Total filas'],
            [[$this->actualizados, $this->creados, $this->omitidos, $this->errores, $fila - 1]]
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

    private function procesarItem(array $data, bool $dryRun, bool $conPrecio, bool $crear): string
    {
        $id    = (int) $data['iditem'];
        $costo = (float) $data['costo'];
        $precio = (float) $data['precio'];
        $now   = now();

        $existe = DB::connection('tenant')
            ->table('inv_items')
            ->where('id', $id)
            ->exists();

        if (!$existe && !$crear) {
            return 'omitido';
        }

        if ($dryRun) {
            return $existe ? 'ok' : 'creado';
        }

        if (!$existe) {
            $this->crearItem($id, $data, $now);
            $this->crearValores($id, $costo, $precio, $conPrecio, $now);
            return 'creado';
        }

        // Actualizar o insertar registros de costo en inv_values
        $this->upsertValores($id, $costo, self::LABELS_COSTO, 'costo', $now);

        if ($conPrecio) {
            $this->upsertValores($id, $precio, self::LABELS_PRECIO, 'precio', $now);
        }

        return 'ok';
    }

    private function upsertValores(int $itemId, float $valor, array $labels, string $type, \Carbon\Carbon $now): void
    {
        foreach ($labels as $label) {
            $existe = DB::connection('tenant')
                ->table('inv_values')
                ->where('itemId', $itemId)
                ->where('label', $label)
                ->exists();

            if ($existe) {
                DB::connection('tenant')
                    ->table('inv_values')
                    ->where('itemId', $itemId)
                    ->where('label', $label)
                    ->update(['values' => $valor, 'updated_at' => $now]);
            } else {
                DB::connection('tenant')
                    ->table('inv_values')
                    ->insert([
                        'itemId'     => $itemId,
                        'values'     => $valor,
                        'type'       => $type,
                        'label'      => $label,
                        'date'       => $now,
                        'created_at' => $now,
                    ]);
            }
        }
    }

    private function crearItem(int $id, array $data, \Carbon\Carbon $now): void
    {
        $taxId = ($data['impuesto'] !== '' && (int) $data['impuesto'] > 0)
            ? (int) $data['impuesto']
            : (int) $this->option('tax-id');

        DB::connection('tenant')->table('inv_items')->insert([
            'id'               => $id,
            'name'             => $data['nombre'],
            'categoryId'       => $data['categoria'] !== '' ? (int) $data['categoria'] : 1,
            'internal_code'    => (string) $id,
            'sku'              => (string) $id,
            'type'             => 'COMPRA NACIONAL',
            'taxId'            => $taxId,
            'commandId'        => (int) $this->option('command-id'),
            'brandId'          => (int) $this->option('brand-id'),
            'houseId'          => (int) $this->option('house-id'),
            'purchasing_unit'  => (int) $this->option('unit-id'),
            'consumption_unit' => (int) $this->option('unit-id'),
            'inventoriable'    => 1,
            'status'           => (int) $data['activo'],
            'created_at'       => $now,
        ]);
    }

    private function crearValores(int $itemId, float $costo, float $precio, bool $conPrecio, \Carbon\Carbon $now): void
    {
        $this->upsertValores($itemId, $costo, self::LABELS_COSTO, 'costo', $now);

        if ($conPrecio) {
            $this->upsertValores($itemId, $precio, self::LABELS_PRECIO, 'precio', $now);
        }
    }
}
