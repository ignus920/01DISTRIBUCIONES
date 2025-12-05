<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo para la tabla inv_image_gallery
 * Gestiona las imágenes de los items (productos)
 * Soporta imagen principal y galería de imágenes
 */
class ImageGallery extends Model
{
    // Conexión a la base de datos del tenant
    protected $connection = 'tenant';

    // Nombre de la tabla
    protected $table = 'inv_image_gallery';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'itemId',       // ID del item al que pertenece la imagen
        'img_path',     // Ruta de la imagen en storage
        'type',         // Tipo: PRINCIPAL o GALERIA
        'created_at',   // Fecha de creación
        'updated_at',   // Fecha de actualización
        'deleted_at',   // Fecha de eliminación (soft delete)
    ];

    // Habilitar timestamps automáticos
    public $timestamps = true;

    // Campos de fecha
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relación con el modelo Items
     * Una imagen pertenece a un item
     */
    public function item()
    {
        return $this->belongsTo(Items::class, 'itemId', 'id');
    }

    /**
     * Scope para obtener solo imágenes no eliminadas
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope para obtener solo la imagen principal
     */
    public function scopePrincipal($query)
    {
        return $query->where('type', 'PRINCIPAL')->whereNull('deleted_at');
    }

    /**
     * Scope para obtener solo imágenes de galería
     */
    public function scopeGallery($query)
    {
        return $query->where('type', 'GALERIA')->whereNull('deleted_at');
    }

    /**
     * Obtener la URL completa de la imagen
     *
     * @return string URL de la imagen
     */
    public function getImageUrl()
    {
        if ($this->img_path) {
            // Limpiar URLs malformadas (fix para problema http:https://)
            $cleanPath = $this->cleanMalformedUrl($this->img_path);

            // Si ya es una URL completa, devolverla directamente
            if (filter_var($cleanPath, FILTER_VALIDATE_URL)) {
                return $cleanPath;
            }

            // Si es un path local y existe, generar URL
            if (Storage::disk('public')->exists($cleanPath)) {
                $url = Storage::disk('public')->url($cleanPath);
                return $this->cleanMalformedUrl($url);
            }
        }

        // Retornar imagen placeholder si no existe
        return asset('images/placeholder-item.png');
    }

    /**
     * Obtener la URL del thumbnail
     *
     * @return string URL del thumbnail
     */
    public function getThumbnailUrl()
    {
        if ($this->img_path) {
            // Limpiar URLs malformadas (fix para problema http:https://)
            $cleanPath = $this->cleanMalformedUrl($this->img_path);

            // Si ya es una URL completa, devolverla directamente
            if (filter_var($cleanPath, FILTER_VALIDATE_URL)) {
                return $cleanPath;
            }

            // Generar ruta del thumbnail
            $thumbnailPath = $this->getThumbnailPath();

            if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                $url = Storage::disk('public')->url($thumbnailPath);
                return $this->cleanMalformedUrl($url);
            }

            // Si no existe thumbnail, retornar imagen original
            if (Storage::disk('public')->exists($cleanPath)) {
                $url = Storage::disk('public')->url($cleanPath);
                return $this->cleanMalformedUrl($url);
            }
        }

        // Retornar imagen placeholder si no existe
        return asset('images/placeholder-item.png');
    }

    /**
     * Obtener la ruta del thumbnail
     * 
     * @return string Ruta del thumbnail
     */
    public function getThumbnailPath()
    {
        if (!$this->img_path) {
            return null;
        }

        $pathInfo = pathinfo($this->img_path);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
    }

    /**
     * Verificar si es la imagen principal
     * 
     * @return bool
     */
    public function isPrincipal()
    {
        return $this->type === 'PRINCIPAL';
    }

    /**
     * Verificar si está eliminada (soft delete)
     * 
     * @return bool
     */
    public function isDeleted()
    {
        return !is_null($this->deleted_at);
    }

    /**
     * Soft delete de la imagen
     * Marca la imagen como eliminada sin borrar el archivo físico
     */
    public function softDelete()
    {
        $this->update(['deleted_at' => now()]);
    }

    /**
     * Restaurar imagen eliminada
     */
    public function restore()
    {
        $this->update(['deleted_at' => null]);
    }

    /**
     * Eliminar físicamente la imagen y sus thumbnails
     */
    public function hardDelete()
    {
        // Eliminar archivo original
        if ($this->img_path && Storage::disk('public')->exists($this->img_path)) {
            Storage::disk('public')->delete($this->img_path);
        }

        // Eliminar thumbnail
        $thumbnailPath = $this->getThumbnailPath();
        if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
            Storage::disk('public')->delete($thumbnailPath);
        }

        // Eliminar registro de la base de datos
        $this->delete();
    }

    /**
     * Limpiar URLs malformadas que contengan http:https:// o https:http://
     *
     * @param string $url URL a limpiar
     * @return string URL limpia
     */
    private function cleanMalformedUrl($url)
    {
        if (!$url) {
            return $url;
        }

        // Remover duplicaciones de protocolo
        $url = preg_replace('/^https?:https?:\/\//', 'https://', $url);
        $url = preg_replace('/^https?:http:\/\//', 'https://', $url);
        $url = preg_replace('/^http:https:\/\//', 'https://', $url);

        // Asegurar que use https en producción
        if (str_contains($url, 'erp.dosil.com.co')) {
            $url = str_replace('http:', 'https:', $url);
        }

        return $url;
    }
}
