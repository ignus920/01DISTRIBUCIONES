<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Helper para procesamiento de imágenes
 * Genera thumbnails y optimiza imágenes
 */
class ImageHelper
{
    /**
     * Generar thumbnail de una imagen
     * 
     * @param string $imagePath Ruta de la imagen original
     * @param int $width Ancho del thumbnail
     * @param int $height Alto del thumbnail
     * @return string|null Ruta del thumbnail generado
     */
    public static function generateThumbnail($imagePath, $width = 150, $height = 150)
    {
        try {
            // Verificar que la imagen existe
            if (!Storage::disk('public')->exists($imagePath)) {
                return null;
            }

            // Obtener la ruta completa de la imagen
            $fullPath = Storage::disk('public')->path($imagePath);

            // Crear directorio de thumbnails si no existe
            $pathInfo = pathinfo($imagePath);
            $thumbnailDir = $pathInfo['dirname'] . '/thumbnails';
            
            if (!Storage::disk('public')->exists($thumbnailDir)) {
                Storage::disk('public')->makeDirectory($thumbnailDir);
            }

            // Ruta del thumbnail
            $thumbnailPath = $thumbnailDir . '/' . $pathInfo['basename'];
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

            // Generar thumbnail con Intervention Image
            $image = Image::make($fullPath);
            
            // Redimensionar manteniendo aspecto y recortar al centro
            $image->fit($width, $height, function ($constraint) {
                $constraint->upsize(); // No agrandar imágenes pequeñas
            });

            // Guardar thumbnail
            $image->save($thumbnailFullPath, 85); // Calidad 85%

            return $thumbnailPath;

        } catch (\Exception $e) {
            \Log::error('Error generando thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Eliminar thumbnail de una imagen
     * 
     * @param string $imagePath Ruta de la imagen original
     * @return bool
     */
    public static function deleteThumbnail($imagePath)
    {
        try {
            $pathInfo = pathinfo($imagePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];

            if (Storage::disk('public')->exists($thumbnailPath)) {
                return Storage::disk('public')->delete($thumbnailPath);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error eliminando thumbnail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimizar imagen (reducir tamaño sin perder mucha calidad)
     * 
     * @param string $imagePath Ruta de la imagen
     * @param int $maxWidth Ancho máximo
     * @param int $quality Calidad (0-100)
     * @return bool
     */
    public static function optimizeImage($imagePath, $maxWidth = 1200, $quality = 85)
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                return false;
            }

            $fullPath = Storage::disk('public')->path($imagePath);
            $image = Image::make($fullPath);

            // Redimensionar si es muy grande
            if ($image->width() > $maxWidth) {
                $image->resize($maxWidth, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Guardar con compresión
            $image->save($fullPath, $quality);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error optimizando imagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener dimensiones de una imagen
     * 
     * @param string $imagePath Ruta de la imagen
     * @return array|null ['width' => int, 'height' => int]
     */
    public static function getImageDimensions($imagePath)
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                return null;
            }

            $fullPath = Storage::disk('public')->path($imagePath);
            $image = Image::make($fullPath);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error obteniendo dimensiones: ' . $e->getMessage());
            return null;
        }
    }
}
