<?php
/**
 * REPARACIÓN DEFINITIVA Y UNIFICACIÓN DE TABLAS.
 * 
 * Este script elimina la duplicidad entre la tabla Blade (online) y Alpine (offline)
 * y corrige la alineación de las columnas de Inventario (LLEVADA).
 */

$path = 'c:\xampp\htdocs\01DISTRIBUCIONES\resources\views\livewire\tenant\deliveries\deliveries.blade.php';
$lines = file($path);

// 1. Eliminar la sección Online manual (Blade) que está causando la colisión
$startOnline = -1;
$endOnline = -1;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "<!-- SECCIÓN ONLINE") !== false) $startOnline = $i;
    if (strpos($lines[$i], "<!-- SECCIÓN OFFLINE") !== false || strpos($lines[$i], "<!-- SECCIÓN UNIFICADA") !== false) {
        $endOnline = $i - 1;
        break;
    }
}

if ($startOnline !== -1 && $endOnline !== -1) {
    array_splice($lines, $startOnline, ($endOnline - $startOnline + 1));
    echo "SUCCESS: Deleted redundant Blade section (lines " . ($startOnline+1) . " to " . ($endOnline+1) . ")\n";
}

// 2. Corregir la tabla Alpine (ahora única)
$startTable = -1;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "<!-- Tabla Devoluciones -->") !== false) {
        $startTable = $i;
        break;
    }
}

if ($startTable !== -1) {
    echo "Fixing Alpine Table Alignment...\n";
    // Asegurar 7 headers
    for ($j = $startTable; $j < $startTable + 100; $j++) {
        // Corregir LLEVADA 0 (no mostrar '-' si es 0)
        if (strpos($lines[$j], 'ret.qty_llevada || \'-\'') !== false) {
            $lines[$j] = str_replace('ret.qty_llevada || \'-\'', '(ret.qty_llevada !== null && ret.qty_llevada !== undefined) ? ret.qty_llevada : \'-\'', $lines[$j]);
        }
        
        // Corregir footers si el colspan es viejo
        if (strpos($lines[$j], 'colspan="6"') !== false) $lines[$j] = str_replace('colspan="6"', 'colspan="7"', $lines[$j]);
        if (strpos($lines[$j], 'colspan="2"') !== false) $lines[$j] = str_replace('colspan="2"', 'colspan="3"', $lines[$j]);
    }
}

// 3. Quitar x-show="!isOnline" para que sea universal
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '<div class="mt-6 space-y-6" x-show="!isOnline') !== false) {
        $lines[$i] = str_replace('x-show="!isOnline && (viewReturns || viewCollections)"', 'x-show="viewReturns || viewCollections"', $lines[$i]);
    }
}

file_put_contents($path, implode('', $lines));
echo "DONE: Table unified and aligned.\n";
