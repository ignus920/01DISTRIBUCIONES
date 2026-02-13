<div>
    @if($showLabel)
        <label for="regime_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <select
        wire:model.live="regimeId"
        name="{{ $name }}"
        id="regime_{{ $name }}"
        @if($required) required @endif
        class="{{ $class }} bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600"
        wire:loading.attr="disabled">
        <option value="">Seleccione una opción</option>
        @foreach($regimes as $regime)
            <option value="{{ $regime->id }}">
                {{ $regime->name }}
            </option>
        @endforeach
    </select>

    @error($name)
        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
    @enderror
</div>