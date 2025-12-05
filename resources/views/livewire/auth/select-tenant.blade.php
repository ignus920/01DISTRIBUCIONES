<div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Seleccione una Empresa</h2>
            <p class="mt-2 text-sm text-gray-600">
                Escoja la empresa con la que desea trabajar
            </p>
        </div>

        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @if ($tenants->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($tenants as $tenant)
                    <button
                        wire:click="selectTenant('{{ $tenant->id }}')"
                        class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 p-6 text-left border-2 border-transparent hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            @if ($tenant->pivot->last_accessed_at)
                                <span class="text-xs text-gray-500">
                                    Último acceso: {{ $tenant->pivot->last_accessed_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ $tenant->name }}
                        </h3>

                        @if ($tenant->email)
                            <p class="text-sm text-gray-600 mb-1">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ $tenant->email }}
                            </p>
                        @endif

                        @if ($tenant->phone)
                            <p class="text-sm text-gray-600 mb-3">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $tenant->phone }}
                            </p>
                        @endif

                        @if ($tenant->pivot->role)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ ucfirst($tenant->pivot->role) }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        @else
            <div class="text-center bg-white rounded-lg shadow-md p-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No tiene acceso a ninguna empresa</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Contacte al administrador del sistema para solicitar acceso.
                </p>
            </div>
        @endif

        <div class="mt-8 text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:text-gray-800 underline">
                    Cerrar Sesión 
                </button>
            </form>
        </div>
    </div>
</div>
