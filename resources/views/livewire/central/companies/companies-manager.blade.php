<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Gestión de Empresas</h1>
        <button wire:click="create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Crear Nueva Empresa
        </button>
    </div>

    <!-- Búsqueda -->
    <div class="mb-4">
        <input wire:model.live="search" type="text" placeholder="Buscar por nombre, email o identificación..."
               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Mensajes -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razón Social</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identificación</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo Persona</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($items as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->businessName }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->billingEmail }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->identification }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->typePerson }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $item->status ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button wire:click="edit({{ $item->id }})" class="text-blue-600 hover:text-blue-900 mr-2">
                                Editar
                            </button>
                            <button wire:click="delete({{ $item->id }})"
                                    onclick="return confirm('¿Estás seguro de eliminar esta empresa?')"
                                    class="text-red-600 hover:text-red-900">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No se encontraron empresas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $items->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $editingId ? 'Editar' : 'Crear' }} Empresa
                    </h3>

                    <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Información Básica -->
                        <div class="md:col-span-2">
                            <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">Información Básica</h4>
                        </div>

                        <!-- Razón Social -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Razón Social *</label>
                            <input wire:model="businessName" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('businessName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email de Facturación *</label>
                            <input wire:model="billingEmail" type="email"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('billingEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tipo de Persona -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Persona *</label>
                            <select wire:model="typePerson"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar...</option>
                                <option value="Juridica">Jurídica</option>
                                <option value="Natural">Natural</option>
                            </select>
                            @error('typePerson') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select wire:model="status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                            @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Información Personal -->
                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">Información Personal</h4>
                        </div>

                        <!-- Primer Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primer Nombre</label>
                            <input wire:model="firstName" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Segundo Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Segundo Nombre</label>
                            <input wire:model="secondName" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('secondName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Primer Apellido -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primer Apellido</label>
                            <input wire:model="lastName" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Segundo Apellido -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Segundo Apellido</label>
                            <input wire:model="secondLastName" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('secondLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Información Fiscal -->
                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">Información Fiscal</h4>
                        </div>

                        <!-- Identificación -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Identificación</label>
                            <input wire:model="identification" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('identification') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Dígito de Verificación -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dígito Verificación</label>
                            <input wire:model="checkDigit" type="number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('checkDigit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tipo de Identificación -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Identificación</label>
                            <select wire:model="typeIdentificationId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar...</option>
                                @foreach($typeIdentifications as $type)
                                    <option value="{{ $type->id }}">{{ $type->description }}</option>
                                @endforeach
                            </select>
                            @error('typeIdentificationId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Régimen -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Régimen</label>
                            <select wire:model="regimeId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar...</option>
                                @foreach($regimes as $regime)
                                    <option value="{{ $regime->id }}">{{ $regime->description }}</option>
                                @endforeach
                            </select>
                            @error('regimeId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Responsabilidad Fiscal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Responsabilidad Fiscal</label>
                            <select wire:model="fiscalResponsabilityId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar...</option>
                                @foreach($fiscalResponsabilities as $fiscal)
                                    <option value="{{ $fiscal->id }}">{{ $fiscal->description }}</option>
                                @endforeach
                            </select>
                            @error('fiscalResponsabilityId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Código CIIU -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código CIIU</label>
                            <input wire:model="code_ciiu" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('code_ciiu') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Botones -->
                        <div class="md:col-span-2 flex justify-end space-x-2 mt-6">
                            <button type="button" wire:click="$set('showModal', false)"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                {{ $editingId ? 'Actualizar' : 'Crear' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>