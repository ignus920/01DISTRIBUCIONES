<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Tenant\Items\Items;
use App\Models\Tenant\Items\ImageGallery;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Carbon\Carbon;

/**
 * Componente Livewire para gestionar la galería de imágenes de un item
 * Permite subir imagen principal, agregar imágenes a galería y eliminar imágenes
 */
class ItemImageUpload extends Component
{
    use WithFileUploads;

    // ID del item
    public $itemId;

    // Imagen principal temporal
    #[Validate('nullable|image|mimes:jpeg,png,jpg,webp|max:2048')] // 2MB max
    public $principalImage;

    // Imágenes de galería temporales (múltiples)
    public $galleryImages = [];

    // Límite de imágenes en galería
    const MAX_GALLERY_IMAGES = 6;

    /**
     * Reglas de validación
     */
    protected function rules()
    {
        return [
            'principalImage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'galleryImages' => 'nullable|array',
            'galleryImages.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    /**
     * Inicializar componente
     */
    public function mount($itemId)
    {
        $this->ensureTenantConnection();
        $this->itemId = $itemId;
    }

    /**
     * Asegurar que la conexión del tenant esté configurada
     */
    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }

    /**
     * Obtener el item con sus imágenes
     */
    public function getItemProperty()
    {
        return Items::with(['principalImage', 'activeImages'])->find($this->itemId);
    }

    /**
     * Obtener la imagen principal
     */
    public function getPrincipalImageProperty()
    {
        $this->ensureTenantConnection();
        return ImageGallery::where('itemId', $this->itemId)
                          ->where('type', 'PRINCIPAL')
                          ->whereNull('deleted_at')
                          ->first();
    }

    /**
     * Obtener imágenes de galería
     */
    public function getGalleryImagesProperty()
    {
        $this->ensureTenantConnection();
        return ImageGallery::where('itemId', $this->itemId)
                          ->where('type', 'GALERIA')
                          ->whereNull('deleted_at')
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Verificar si se puede agregar más imágenes a la galería
     */
    public function canAddMoreImages()
    {
        return $this->getGalleryImagesProperty()->count() < self::MAX_GALLERY_IMAGES;
    }

    /**
     * Subir imagen principal
     */
    public function uploadPrincipalImage()
    {
        $this->ensureTenantConnection();
        $this->validateOnly('principalImage');

        if (!$this->principalImage) {
            session()->flash('image-error', 'Por favor selecciona una imagen principal.');
            return;
        }

        try {
            // Eliminar la imagen principal anterior
            $this->removePreviousPrincipalImage();

            // Guardar imagen en storage
            $tenantId = session('tenant_id', 'default');
            $path = $this->principalImage->store("items/{$tenantId}", 'public');

            // Optimizar imagen
            // ImageHelper::optimizeImage($path, 1200, 85);

            // Generar thumbnail
            // ImageHelper::generateThumbnail($path, 150, 150);

            // Crear registro en base de datos
            ImageGallery::create([
                'itemId' => $this->itemId,
                'img_path' => $path,
                'type' => 'PRINCIPAL',
                'created_at' => Carbon::now(),
            ]);

            // Limpiar y mostrar mensaje
            $this->reset('principalImage');
            session()->flash('image-message', 'Imagen principal actualizada exitosamente.');

        } catch (\Exception $e) {
            \Log::error('Error subiendo imagen principal: ' . $e->getMessage());
            session()->flash('image-error', 'Error al subir la imagen principal: ' . $e->getMessage());
        }
    }

    /**
     * Subir imágenes a la galería
     */
    public function uploadGalleryImages()
    {
        $this->ensureTenantConnection();

        // Validar arrays de archivos
        $this->validate([
            'galleryImages' => 'nullable|array',
            'galleryImages.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if (!$this->galleryImages || count($this->galleryImages) === 0) {
            session()->flash('image-error', 'Por favor selecciona al menos una imagen para la galería.');
            return;
        }

        try {
            // Verificar límite de imágenes
            $currentCount = $this->getGalleryImagesProperty()->count();
            $newCount = count($this->galleryImages);

            if ($currentCount + $newCount > self::MAX_GALLERY_IMAGES) {
                $remaining = self::MAX_GALLERY_IMAGES - $currentCount;
                session()->flash('image-error', "Solo puedes agregar {$remaining} imagen(es) más. Límite máximo: " . self::MAX_GALLERY_IMAGES . ' imágenes.');
                return;
            }

            $tenantId = session('tenant_id', 'default');
            $uploadedCount = 0;

            foreach ($this->galleryImages as $image) {
                if ($image) {
                    // Guardar imagen en storage
                    $path = $image->store("items/{$tenantId}", 'public');

                    // Optimizar imagen
                    // ImageHelper::optimizeImage($path, 1200, 85);

                    // Generar thumbnail
                    // ImageHelper::generateThumbnail($path, 150, 150);

                    // Crear registro en base de datos
                    ImageGallery::create([
                        'itemId' => $this->itemId,
                        'img_path' => $path,
                        'type' => 'GALERIA',
                        'created_at' => Carbon::now(),
                    ]);

                    $uploadedCount++;
                }
            }

            // Limpiar y mostrar mensaje
            $this->reset('galleryImages');

            // Refrescar las propiedades computadas
            unset($this->galleryImagesData);

            session()->flash('image-message', "{$uploadedCount} imagen(es) agregada(s) a la galería exitosamente.");

        } catch (\Exception $e) {
            \Log::error('Error subiendo imágenes de galería: ' . $e->getMessage());
            session()->flash('image-error', 'Error al subir las imágenes de galería: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar imagen principal anterior
     */
    private function removePreviousPrincipalImage()
    {
        $previousPrincipal = $this->getPrincipalImageProperty();

        if ($previousPrincipal) {
            // Eliminar archivos físicos
            if ($previousPrincipal->img_path && Storage::disk('public')->exists($previousPrincipal->img_path)) {
                Storage::disk('public')->delete($previousPrincipal->img_path);
            }

            // Eliminar thumbnail
            // ImageHelper::deleteThumbnail($previousPrincipal->img_path);

            // Eliminar registro
            $previousPrincipal->delete();
        }
    }

    /**
     * Eliminar imagen (soft delete)
     */
    public function deleteImage($imageId)
    {
        $this->ensureTenantConnection();
        try {
            $image = ImageGallery::find($imageId);

            if (!$image || $image->itemId != $this->itemId) {
                session()->flash('image-error', 'Imagen no encontrada.');
                return;
            }

            // Soft delete
            $image->softDelete();

            session()->flash('image-message', 'Imagen eliminada exitosamente.');

        } catch (\Exception $e) {
            \Log::error('Error eliminando imagen: ' . $e->getMessage());
            session()->flash('image-error', 'Error al eliminar la imagen.');
        }
    }

    /**
     * Establecer una imagen de galería como principal
     */
    public function setPrincipal($imageId)
    {
        $this->ensureTenantConnection();
        try {
            $image = ImageGallery::find($imageId);

            if (!$image || $image->itemId != $this->itemId) {
                session()->flash('image-error', 'Imagen no encontrada.');
                return;
            }

            // Cambiar la imagen principal actual a galería
            $currentPrincipal = $this->getPrincipalImageProperty();
            if ($currentPrincipal) {
                $currentPrincipal->update(['type' => 'GALERIA']);
            }

            // Cambiar la imagen seleccionada a principal
            $image->update(['type' => 'PRINCIPAL']);

            session()->flash('image-message', 'Imagen establecida como principal exitosamente.');

        } catch (\Exception $e) {
            \Log::error('Error estableciendo imagen principal: ' . $e->getMessage());
            session()->flash('image-error', 'Error al establecer imagen principal.');
        }
    }

    /**
     * Renderizar componente
     */
    public function render()
    {
        $this->ensureTenantConnection();
        return view('livewire.tenant.items.item-image-upload', [
            'principalImageData' => $this->getPrincipalImageProperty(),
            'galleryImagesData' => $this->getGalleryImagesProperty(),
            'canAddMore' => $this->canAddMoreImages(),
            'maxImages' => self::MAX_GALLERY_IMAGES,
        ]);
    }
}
