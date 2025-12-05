<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Items') }}
        </h2>
    </x-slot>

    {{-- Página de items renderiza el componente Livewire de gestión --}}
    @livewire('tenant.inventory.unit-measurements')
</x-app-layout>