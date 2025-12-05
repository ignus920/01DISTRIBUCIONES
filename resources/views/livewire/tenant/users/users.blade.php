<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Usuarios') }}
        </h2>
    </x-slot>
    
    <!-- Livewire Component -->
    <livewire:tenant.users.user-rap-form />
</x-app-layout>