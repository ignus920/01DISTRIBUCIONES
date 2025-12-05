<x-app-layout>
    <x-slot name="header">
        <h2 class="inline-flex items-center text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
            {{ __('Cotizaciones') }}
        </h2>
    </x-slot>

    <!-- Livewire Component -->
    <livewire:tenant.quoter.quoter viewType="mobile" />
</x-app-layout>
