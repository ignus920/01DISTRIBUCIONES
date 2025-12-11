<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cargue') }}
        </h2>
    </x-slot>
    {{-- Página de items renderiza el componente Livewire de gestión --}}
    @livewire('tenant.uploads.uploads')
</x-app-layout>