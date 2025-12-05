<div class="mb-4">
    @if ($showLabel)
        <label for="consumption_unit_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
        </label>
    @endif
    <select
        wire:model.live="consumptionUnitId"
        name="{{ $name }}"
        id="consumption_unit_{{ $name }}"
        @if($required) required @endif
        class="{{ $class }}">
        <option value="">{{ $placeholder }}</option>
        @foreach($consumptionUnits as $consumptionUnit)
            <option value="{{ $consumptionUnit->id }}">{{ $consumptionUnit->description }}</option>
        @endforeach
    </select>   
    @error($name)
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>
