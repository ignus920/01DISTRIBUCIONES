<?php

namespace App\Http\Controllers\Quoter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QuoterPrintController extends Controller
{
    /**
     * Servir archivo temporal de impresión de cotización
     */
    public function showTempPrint(Request $request, $file)
    {
        \Log::info('🎯 Controlador de impresión llamado', ['file' => $file]);

        $tempPath = storage_path('app/temp/' . $file);
        \Log::info('📂 Buscando archivo', ['path' => $tempPath]);

        // Verificar que el archivo existe y es seguro
        if (!file_exists($tempPath)) {
            \Log::error('❌ Archivo no encontrado', ['path' => $tempPath]);
            abort(404, 'Archivo de impresión no encontrado');
        }

        if (!$this->isValidTempFile($file)) {
            \Log::error('❌ Archivo inválido', ['file' => $file]);
            abort(404, 'Archivo de impresión inválido');
        }

        \Log::info('✅ Archivo válido encontrado');

        // Leer el contenido del archivo
        $content = file_get_contents($tempPath);
        \Log::info('📖 Contenido leído', ['size' => strlen($content) . ' caracteres']);

        // Eliminar el archivo temporal después de servir (opcional)
        // unlink($tempPath);

        return response($content)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Validar que el archivo temporal es válido y seguro
     */
    private function isValidTempFile($filename)
    {
        // Verificar que el nombre del archivo tiene el formato esperado
        return preg_match('/^quote_\d+_\d+\.html$/', $filename);
    }

    /**
     * Limpiar archivos temporales antiguos (puede ser llamado por un comando cron)
     */
    public function cleanOldTempFiles()
    {
        $tempDir = storage_path('app/temp');

        if (!is_dir($tempDir)) {
            return;
        }

        $files = glob($tempDir . '/quote_*.html');
        $now = time();

        foreach ($files as $file) {
            // Eliminar archivos de más de 1 hora
            if (filemtime($file) < ($now - 3600)) {
                unlink($file);
            }
        }
    }
}