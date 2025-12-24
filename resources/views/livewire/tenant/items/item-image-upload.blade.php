<section>
    <!-- Mensajes de éxito/error -->
    @if (session('image-message'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('image-message') }}
            </div>
        </div>
    @endif

    @if (session('image-error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('image-error') }}
            </div>
        </div>
    @endif

    <div class="space-y-6">
        <!-- Imagen Principal -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Imagen Principal
            </h4>

            <div class="flex items-start space-x-4">
                <!-- Thumbnail actual -->
                <div class="shrink-0">
                    @if($principalImageData)
                        <div class="rounded-lg overflow-hidden border-2 border-indigo-300 dark:border-indigo-600 shadow-sm bg-white dark:bg-gray-700" style="width:100px;height:100px;">
                            <img class="max-w-[80px] max-h-[80px] w-auto h-auto object-contain block"
                                 src="{{ $principalImageData->getImageUrl() }}"
                                 alt="Imagen principal">
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-200 mt-1">
                            Principal
                        </span>
                    @else
                        <div class="rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600" style="width:80px;height:80px;">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 mt-1">
                            Sin imagen
                        </span>
                    @endif
                </div>

                <!-- Formulario de subida -->
                <div class="flex-1">
                    <form wire:submit.prevent="uploadPrincipalImage" class="space-y-3">

                        <div>
                            <label for="principal-image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $principalImageData ? 'Cambiar imagen principal' : 'Subir imagen principal' }}
                            </label>
                            <input
                                type="file"
                                wire:model="principalImage"
                                id="principal-image"
                                accept="image/*"
                                class="block w-full text-sm text-gray-500 dark:text-gray-400
                                       file:mr-4 file:py-2 file:px-4
                                       file:rounded-md file:border-0
                                       file:text-sm file:font-medium
                                       file:bg-indigo-50 file:text-indigo-700
                                       dark:file:bg-indigo-900/20 dark:file:text-indigo-400
                                       hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/30
                                       file:transition-colors file:cursor-pointer
                                       border border-gray-300 dark:border-gray-600 rounded-md
                                       bg-white dark:bg-gray-700">
                            @error('principalImage')
                                <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Preview de nueva imagen -->
                        @if ($principalImage && !$errors->has('principalImage'))
                            <div class="flex items-center space-x-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                                <div class="rounded-md overflow-hidden border-2 border-indigo-300 dark:border-indigo-600 bg-white dark:bg-gray-700" style="width:64px;height:64px;">
                                    <img class="max-w-[64px] max-h-[64px] w-auto h-auto object-contain block"
                                         src="{{ $principalImage->temporaryUrl() }}"
                                         alt="Vista previa">
                                </div>
                                <div class="flex-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-200">
                                        Vista previa
                                    </span>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Esta será la nueva imagen principal
                                    </p>
                                    
                                </div>
                            </div>
                        @endif

                        <!-- Indicador de carga -->
                        <div wire:loading wire:target="principalImage" class="p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">Cargando imagen...</span>
                            </div>
                        </div>

                        <!-- Botones -->
                        @if($principalImage)
                            <div class="flex items-center gap-3">
                                <button type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="uploadPrincipalImage"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                                    <span wire:loading.remove wire:target="uploadPrincipalImage">Guardar Imagen</span>
                                    <span wire:loading wire:target="uploadPrincipalImage">Guardando...</span>
                                </button>

                                <button type="button"
                                        wire:click="$set('principalImage', null)"
                                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        @endif

                        @if($principalImageData)
                            <button type="button"
                                    wire:click="deleteImage({{ $principalImageData->id }})"
                                    wire:confirm="¿Estás seguro de que quieres eliminar la imagen principal?"
                                    class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 transition-colors">
                                Eliminar imagen principal
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Galería de Imágenes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Galería ({{ $galleryImagesData->count() }}/{{ $maxImages }})
                </h4>
            </div>

            <!-- Grid de galeria -->
            @if($galleryImagesData->count() > 0)
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 mb-4">
                    @foreach($galleryImagesData as $image)
                        <div class="relative group">
                            <!-- Imagen con borde visible y tamaño uniforme fijo -->
                            <div class="h-20 w-20 rounded-lg overflow-hidden border-2 border-gray-300 dark:border-gray-500 shadow-sm bg-white dark:bg-gray-700 flex items-center justify-center">
                                <img class="h-20 w-20 object-cover object-center"
                                     src="{{ $image->getImageUrl() }}"
                                     alt="Imagen de galería">
                            </div>

                            <!-- Botón de eliminar siempre visible en esquina superior derecha -->
                                <button type="button"
                                    wire:click="deleteImage({{ $image->id }})"
                                    wire:confirm="¿Eliminar esta imagen?"
                                    title="Eliminar imagen"
                                    class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center shadow transition-all z-10 border-2 border-white dark:border-gray-800">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>

                            <!-- Botón para establecer como principal (overlay) -->
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <button type="button"
                                        wire:click="setPrincipal({{ $image->id }})"
                                        title="Establecer como principal"
                                        class="p-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm">No hay imágenes en la galería</p>
                </div>
            @endif

            <!-- Formulario para agregar a galería -->
            @if($canAddMore)
                <form wire:submit.prevent="uploadGalleryImages" class="space-y-3">

                    <div>
                        <label for="gallery-images" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Agregar imágenes a la galería (múltiples)
                        </label>
                        <input
                            type="file"
                            wire:model="galleryImages"
                            id="gallery-images"
                            accept="image/*"
                            multiple
                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                                   file:mr-4 file:py-2 file:px-4
                                   file:rounded-md file:border-0
                                   file:text-sm file:font-medium
                                   file:bg-green-50 file:text-green-700
                                   dark:file:bg-green-900/20 dark:file:text-green-400
                                   hover:file:bg-green-100 dark:hover:file:bg-green-900/30
                                   file:transition-colors file:cursor-pointer
                                   border border-gray-300 dark:border-gray-600 rounded-md
                                   bg-white dark:bg-gray-700">
                        @error('galleryImages.*')
                            <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                        @enderror

                        <!-- Barra de progreso -->
                        <div wire:loading wire:target="galleryImages" class="mt-2">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full animate-pulse" style="width: 70%"></div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Cargando archivos...</p>
                        </div>
                    </div>

                    <!-- Preview de nuevas imágenes de galería -->
                    @if ($galleryImages && count($galleryImages) > 0)
                        <div class="space-y-3">
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                                        {{ count($galleryImages) }} imagen(es) seleccionada(s)
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                    @foreach($galleryImages as $image)
                                        @if($image)
                                            <div class="relative">
                                                <img class="h-16 w-16 rounded-md object-cover border-2 border-green-300 dark:border-green-600"
                                                     src="{{ $image->temporaryUrl() }}"
                                                     alt="Vista previa">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                                    Se agregarán a la galería
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="uploadGalleryImages"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                                    <span wire:loading.remove wire:target="uploadGalleryImages">Agregar a Galería</span>
                                    <span wire:loading wire:target="uploadGalleryImages">Agregando...</span>
                                </button>

                                <button type="button"
                                        wire:click="$set('galleryImages', [])"
                                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
            @else
                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        Has alcanzado el límite máximo de {{ $maxImages }} imágenes en la galería.
                    </p>
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            <strong>Nota:</strong> Formatos permitidos: JPG, PNG, WEBP. Tamaño máximo: 2MB por imagen.
        </p>
    </div>
</section>
