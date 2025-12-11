<div>
    @if($showLabel)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <select
        wire:model.live="selectedValue"
        @if($required) required @endif
        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500"
        wire:loading.attr="disabled">
        <option value="">{{ $placeholder }}</option>
        @foreach($routes as $route)
            <option value="{{ $route->id }}">
                {{ $route->name }}
                @if($route->salesman) - {{ $route->salesman->name }} @endif
                @if($route->sale_day) - {{ ucfirst($route->sale_day) }} @endif
            </option>
        @endforeach
    </select>
</div>
