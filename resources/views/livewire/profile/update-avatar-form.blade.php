<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Photo') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Actualiza la foto de perfil de tu cuenta.") }}
        </p>
    </header>

    <!-- Mensaje de éxito -->
    @if (session('avatar-message'))
        <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg">
            {{ session('avatar-message') }}
        </div>
    @endif

    <div class="mt-6 space-y-6">
        <!-- Avatar actual -->
        <div class="flex items-center space-x-6">
            <div class="shrink-0">
                <img class="h-16 w-16 rounded-full object-cover border-2 border-gray-300 dark:border-gray-600"
                     src="{{ auth()->user()->getAvatarUrl() }}"
                     alt="Avatar actual"
                     id="current-avatar">
            </div>

            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    @if(auth()->user()->hasCustomAvatar())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                            Foto personalizada
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            Usando iniciales
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    JPG, PNG máximo 2MB
                </p>
            </div>
        </div>

        <!-- Preview de nueva imagen -->
        @if ($avatar)
            <div class="flex items-center space-x-6">
                <div class="shrink-0">
                    <img class="h-16 w-16 rounded-full object-cover border-2 border-indigo-300 dark:border-indigo-600"
                         src="{{ $avatar->temporaryUrl() }}"
                         alt="Vista previa">
                </div>
                <div class="flex-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-200">
                        Vista previa
                    </span>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Esta será tu nueva foto de perfil
                    </p>
                </div>
            </div>
        @endif

        <!-- Formulario de subida -->
        <form wire:submit="save" class="space-y-4">
            <div>
                <label for="avatar" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Seleccionar nueva foto
                </label>
                <input
                    type="file"
                    wire:model="avatar"
                    id="avatar"
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
                           bg-white dark:bg-gray-700"
                >
                @error('avatar')
                    <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Indicador de carga mejorado -->
            <div wire:loading wire:target="avatar" class="mt-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                <div class="flex items-center space-x-3">
                    <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">Subiendo imagen...</span>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center gap-4">
                @if($avatar)
                    <x-primary-button wire:loading.attr="disabled" wire:target="save">
                        {{ __('Save Photo') }}
                    </x-primary-button>

                    <button type="button" wire:click="$set('avatar', null)" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                        Cancelar
                    </button>
                @endif

                @if(auth()->user()->hasCustomAvatar())
                    <button type="button"
                            wire:click="deleteAvatar"
                            wire:confirm="¿Estás seguro de que quieres eliminar tu foto de perfil?"
                            class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 transition-colors">
                        Eliminar foto actual
                    </button>
                @endif
            </div>
        </form>
    </div>

</section>
