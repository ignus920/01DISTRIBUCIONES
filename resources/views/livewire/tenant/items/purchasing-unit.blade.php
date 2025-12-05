<div class="mb-4">
    @if ($showLabel)
        <label for="purchase_unit_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
        </label>
    @endif

    <select
        wire:model.live="purchaseUnitId"
        name="{{ $name }}"
        id="purchase_unit_{{ $name }}"
        @if($required) required @endif
        class="{{ $class }}">
        <option value="">{{ $placeholder }}</option>
        @foreach($purchaseUnits as $purchaseUnit)
            <option value="{{ $purchaseUnit->id }}">{{ $purchaseUnit->description }}</option>
        @endforeach
    </select>

    @error($name)
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>
