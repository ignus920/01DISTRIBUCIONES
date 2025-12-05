<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Caja') }}
        </h2>
    </x-slot>
    {{-- Página de items renderiza el componente Livewire de gestión --}}
    @livewire('tenant.petty-cash.petty-cash')
</x-app-layout>