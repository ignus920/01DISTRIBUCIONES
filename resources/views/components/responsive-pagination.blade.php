@props(['paginator'])

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        <!-- Información de página -->
        <div class="text-sm text-gray-700 dark:text-gray-300">
            <span class="hidden sm:inline">
                Mostrando <span class="font-medium">{{ $paginator->firstItem() }}</span>
                a <span class="font-medium">{{ $paginator->lastItem() }}</span>
                de <span class="font-medium">{{ $paginator->total() }}</span> resultados
            </span>
            <span class="sm:hidden">
                Página <span class="font-medium">{{ $paginator->currentPage() }}</span>
                de <span class="font-medium">{{ $paginator->lastPage() }}</span>
            </span>
        </div>

        <!-- Botones de paginación -->
        <div class="flex items-center gap-1 sm:gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-2 sm:px-3 py-2 text-sm font-medium text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-not-allowed">
                    <span class="hidden sm:inline">← Anterior</span>
                    <span class="sm:hidden">←</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="px-2 sm:px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <span class="hidden sm:inline">← Anterior</span>
                    <span class="sm:hidden">←</span>
                </a>
            @endif

            {{-- Pagination Elements --}}
            <div class="hidden md:flex items-center gap-1">
                @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                            class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- Mobile/Tablet: Show current page only --}}
            <div class="md:hidden flex items-center gap-1">
                <span class="px-3 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg">
                    {{ $paginator->currentPage() }}
                </span>
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="px-2 sm:px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <span class="hidden sm:inline">Siguiente →</span>
                    <span class="sm:hidden">→</span>
                </a>
            @else
                <span class="px-2 sm:px-3 py-2 text-sm font-medium text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-not-allowed">
                    <span class="hidden sm:inline">Siguiente →</span>
                    <span class="sm:hidden">→</span>
                </span>
            @endif
        </div>
    </nav>
@endif
