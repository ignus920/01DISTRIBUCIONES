<div class="flex items-center gap-2">
    <!-- Botón Excel -->
    <button wire:click="exportExcel"
        wire:loading.attr="disabled"
        title="Exportar a Excel"
        class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5M19,5H12V7H19V5M19,9H12V11H19V9M19,13H12V15H19V13M19,17H12V19H19V17M5,5V7H10V5H5M5,9V11H10V9H5M5,13V15H10V13H5M5,17V19H10V17H5Z" />
        </svg>
    </button>
    <!-- Botón PDF -->
    <button wire:click="exportPdf"
        wire:loading.attr="disabled"
        title="Exportar a PDF"
        class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
        </svg>
    </button>
    <!-- Botón CSV -->
    <button wire:click="exportCsv"
        wire:loading.attr="disabled"
        title="Exportar a CSV"
        class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M8,12V14H16V12H8M8,16V18H13V16H8Z" />
        </svg>
    </button>
</div>
