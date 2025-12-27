<?php

namespace App\Traits\Livewire;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;
use Illuminate\Support\Str;

trait WithExport
{
    /**
     * Exportar a Excel
     */
    public function exportExcel()
    {
        return Excel::download(
            new GenericExport(
                $this->getExportData(),
                $this->getExportHeadings(),
                $this->resolveExportMapping()
            ),
            $this->getExportFilename() . '.xlsx'
        );
    }

    /**
     * Exportar a CSV
     */
    public function exportCsv()
    {
        return Excel::download(
            new GenericExport(
                $this->getExportData(),
                $this->getExportHeadings(),
                $this->resolveExportMapping()
            ),
            $this->getExportFilename() . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf()
    {
        return Excel::download(
            new GenericExport(
                $this->getExportData(),
                $this->getExportHeadings(),
                $this->resolveExportMapping()
            ),
            $this->getExportFilename() . '.pdf',
            \Maatwebsite\Excel\Excel::DOMPDF
        );
    }

    /**
     * Resuelve dinámicamente el mapeador para la exportación
     */
    private function resolveExportMapping()
    {
        try {
            // Intentamos obtener el mapping (puede ser null, array o Closure)
            $mapping = $this->getExportMapping();
            
            // Si el resultado ya es un ejecutable o configuración de columnas, lo usamos
            if (is_callable($mapping) || is_array($mapping)) {
                return $mapping;
            }
            
            // Si es null, intentamos usar el método mismo como callable por si acaso
            return [$this, 'getExportMapping'];
            
        } catch (\ArgumentCountError $e) {
            // Si falla por falta de argumentos, el método mismo es el mapeador (ej. getExportMapping($item))
            return [$this, 'getExportMapping'];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Métodos que deben ser implementados o pueden ser sobrescritos
     */

    protected function getExportData()
    {
        // Por defecto intenta obtener los datos de una propiedad llamada 'data' o similar
        // Pero lo ideal es que el componente lo implemente
        return method_exists($this, 'getDataForExport') 
            ? $this->getDataForExport() 
            : collect([]);
    }

    protected function getExportHeadings(): array
    {
        return [];
    }

    protected function getExportMapping($item = null)
    {
        // Si se llama con un item, devolvemos su representación en array
        if ($item !== null) {
            return is_array($item) ? $item : (method_exists($item, 'toArray') ? $item->toArray() : (array)$item);
        }
        
        return null;
    }

    protected function getExportFilename(): string
    {
        $name = str_replace(['App\\Livewire\\', '\\'], ['', '-'], get_class($this));
        return Str::snake($name) . '_' . now()->format('Y-m-d_His');
    }
}
