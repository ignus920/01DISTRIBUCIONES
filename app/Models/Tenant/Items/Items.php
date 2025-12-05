<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Items\Brand;
use App\Models\Tenant\Items\InvValues;
use App\Models\Tenant\Items\ImageGallery;
use App\Models\Central\CnfTaxes;
use App\Models\Tenant\Items\CnfPricelist;
use App\Traits\HasCompanyConfiguration;

class Items extends Model
{
    use HasFactory, HasCompanyConfiguration;

    protected $connection = 'tenant';
    protected $table = 'inv_items';

    protected $fillable = [
        'api_data_id',
        'categoryId',
        'name',
        'internal_code',
        'sku',
        'description',
        'type',
        'taxId',
        'commandId',
        'brandId',
        'houseId',
        'inventoriable',
        'purchasing_unit',
        'consumption_unit',
        'generic',
        'status',
    ];

    /**
     * Variable estática para controlar si la configuración ya fue inicializada
     */
    private static $configurationInitialized = false;

    /**
     * Inicializar configuración cuando se carga el modelo (solo una vez por request)
     */
    protected static function booted()
    {
        static::retrieved(function ($item) {
            if (!self::$configurationInitialized) {
                $item->initializeCompanyConfiguration();
                self::$configurationInitialized = true;
            }
        });
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brandId', 'id');
    }

    public function purchasingUnit()
    {
        return $this->belongsTo(UnitMeasurements::class, 'purchasing_unit', 'id');
    }

    public function consumptionUnit()
    {
        return $this->belongsTo(UnitMeasurements::class, 'consumption_unit', 'id');
    }

    public function tax(){
        return $this->belongsTo(CnfTaxes::class, 'taxId', 'id');
    }

    public function invValues()
    {
        return $this->hasMany(InvValues::class, 'itemId', 'id');
    }

    /**
     * Relación con la galería de imágenes
     * Un item puede tener múltiples imágenes
     */
    public function imageGallery()
    {
        return $this->hasMany(ImageGallery::class, 'itemId', 'id');
    }

    /**
     * Obtener solo imágenes activas (no eliminadas)
     */
    public function activeImages()
    {
        return $this->hasMany(ImageGallery::class, 'itemId', 'id')
                    ->whereNull('deleted_at');
    }

    /**
     * Obtener la imagen principal del item
     */
    public function principalImage()
    {
        return $this->hasOne(ImageGallery::class, 'itemId', 'id')
                    ->where('type', 'PRINCIPAL')
                    ->whereNull('deleted_at');
    }

    /**
     * Obtener URL de la imagen principal
     * 
     * @return string URL de la imagen o placeholder
     */
    public function getPrincipalImageUrl()
    {
        $principalImage = $this->principalImage;
        
        if ($principalImage) {
            return $principalImage->getImageUrl();
        }

        return asset('images/placeholder-item.png');
    }

    /**
     * Obtener URL del thumbnail de la imagen principal
     * 
     * @return string URL del thumbnail o placeholder
     */
    public function getPrincipalThumbnailUrl()
    {
        $principalImage = $this->principalImage;
        
        if ($principalImage) {
            return $principalImage->getThumbnailUrl();
        }

        return asset('images/placeholder-item.png');
    }

    /**
     * Obtener todas las imágenes de la galería (sin la principal)
     */
    public function getGalleryImages()
    {
        return $this->imageGallery()
                    ->where('type', 'GALERIA')
                    ->whereNull('deleted_at')
                    ->get();
    }

    /**
     * Contar imágenes activas (sin contar la principal)
     * 
     * @return int Número de imágenes en galería
     */
    public function getGalleryImagesCount()
    {
        return $this->imageGallery()
                    ->where('type', 'GALERIA')
                    ->whereNull('deleted_at')
                    ->count();
    }


    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'api_data_id' => 'integer',
            'categoryId' => 'integer',
            'commandId' => 'integer',
            'brandId' => 'integer',
            'houseId' => 'integer',
            'inventoriable' => 'integer',
            'purchasing_unit' => 'integer',
            'consumption_unit' => 'integer',
            'status' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeInventoriable($query)
    {
        return $query->where('inventoriable', 1);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('categoryId', $categoryId);
    }

    // Accessors
    public function getPriceAttribute()
    {
        // Verificar si usa lista de precios (opción 4)
        $usePriceList = $this->getOptionValue(4) == 1;

        if ($usePriceList) {
            // MODO LISTA DE PRECIOS: Precio Base × multiplicador
            return $this->getPriceWithPriceList();
        } else {
            // MODO PRECIOS FIJOS: Usar precios directos de inv_values
            return $this->getPriceFromInventory();
        }
    }

    /**
     * Obtiene el precio usando lista de precios (Precio Base × multiplicador)
     */
    private function getPriceWithPriceList()
    {
        // Buscar el Precio Base
        $basePriceRecord = $this->invValues()
            ->where('type', 'precio')
            ->where('label', 'Precio Base')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$basePriceRecord) {
            return 0;
        }

        // Obtener la lista de precios activa (primera activa por simplicidad)
        $priceList = CnfPricelist::active()->first();

        if (!$priceList) {
            return $basePriceRecord->values; // Sin multiplicador
        }

        // Aplicar multiplicador
        return $basePriceRecord->values * $priceList->value;
    }

    /**
     * Obtiene el precio directamente de inv_values
     */
    private function getPriceFromInventory()
    {
        // Buscar el precio más reciente (cualquier label de precio)
        $priceRecord = $this->invValues()
            ->where('type', 'precio')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $priceRecord ? $priceRecord->values : 0;
    }

    public function getFormattedPriceAttribute()
    {
        return '$ ' . number_format($this->price);
    }

    /**
     * Obtiene TODOS los precios disponibles del producto
     * Retorna un array con los precios según la configuración
     */
    public function getAllPricesAttribute()
    {
        // Verificar si usa lista de precios (opción 4)
        $usePriceList = $this->getOptionValue(4) == 1;

        if ($usePriceList) {
            // MODO LISTA DE PRECIOS: Precio Base × cada multiplicador
            return $this->getAllPricesWithPriceList();
        } else {
            // MODO PRECIOS FIJOS: Todos los precios de inv_values
            return $this->getAllPricesFromInventory();
        }
    }

    /**
     * Obtiene todos los precios usando listas de precios
     * Retorna: ['P1' => 100000, 'P2' => 90000, ...]
     */
    private function getAllPricesWithPriceList()
    {
        // Buscar el Precio Base
        $basePriceRecord = $this->invValues()
            ->where('type', 'precio')
            ->where('label', 'Precio Base')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$basePriceRecord) {
            return [];
        }

        $basePrice = $basePriceRecord->values;
        $prices = [];

        // Obtener TODAS las listas de precios activas
        $priceLists = CnfPricelist::active()->get();

        foreach ($priceLists as $priceList) {
            $prices[$priceList->title] = $basePrice * $priceList->value;
        }

        return $prices;
    }

    /**
     * Obtiene todos los precios directamente de inv_values
     * Retorna: ['Precio Base' => 100000, 'Precio Regular' => 95000, ...]
     */
    private function getAllPricesFromInventory()
    {
        // Obtener TODOS los precios del producto ordenados por fecha
        $priceRecords = $this->invValues()
            ->where('type', 'precio')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $prices = [];
        
        // Agrupar por label y tomar solo el primero (más reciente) de cada grupo
        foreach ($priceRecords->groupBy('label') as $label => $records) {
            $prices[$label] = $records->first()->values;
        }

        return $prices;
    }

    public function getDisplayNameAttribute()
    {
        return strtoupper($this->attributes['name']);
    }
}
