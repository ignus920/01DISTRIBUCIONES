<div>
    @if($showLabel)
        <label for="position_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <div class="relative">
        <select
            wire:model.live="positionId"
            name="{{ $name }}"
            id="position_{{ $name }}"
            @if($required) required @endif
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $class }}">
            <option value="">{{ $placeholder }}</option>
            @foreach($positions as $position)
                <option value="{{ $position->id }}">{{ $position->name }}</option>
            @endforeach
        </select>
    </div>

    @error($name)
        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
    @enderror
</div>
