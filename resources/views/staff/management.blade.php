<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mantenimiento de #' . $staff->file_number . ' ' . $staff->name_surname) }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <!-- Inicializa el estado de edición con Alpine.js -->
        <div x-data="{ isEditing: false }" class="bg-white p-8 rounded-xl shadow-lg w-2/4">
            <form method="POST" action="{{ route('staff.update', $staff->id) }}">
                @csrf
                @method('POST')
                <!-- Campos bloqueados por defecto -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="ID" value="ID" />
                        <x-text-input id="file_number" type="text" name="file_number" value="{{$staff->file_number}}"
                            placeholder="file_number" x-bind:disabled="!isEditing" />
                    </div>
                    <div>
                        <x-select id="coordinator" name="coordinator" :options="$coordinators"
                            placeholder="Seleccionar Coordinador" :selected="$staff->coordinator_id"
                            x-bind:disabled="!isEditing">
                            Coordinador
                        </x-select>
                    </div>
                    <div>
                        <x-select id="secretary" name="secretary" :options="$secretaries"
                            placeholder="Seleccionar secretaria" :selected="$staff->secretary_id"
                            x-bind:disabled="!isEditing">
                            Secretaria
                        </x-select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="Nombre y Apellido" value="Nombre y Apellido" />
                        <x-text-input id="name_surname" type="text" name="name_surname" placeholder="Nombre completo"
                            value="{{$staff->name_surname}}" class="mt-1 block w-full" x-bind:disabled="!isEditing" />
                    </div>
                    <div>
                        <x-select id="category" name="category" :options="$categories"
                            placeholder="Seleccionar categoría" :selected="$staff->category_id"
                            x-bind:disabled="!isEditing">
                            Categoría
                        </x-select>
                    </div>
                </div>

                <div class="mb-4">
                    <x-input-label for="email" value="Email" />
                    <x-text-input type="email" id="email" name="email" value="{{$staff->email}}"
                        placeholder="correo@ejemplo.com" class="mt-1 block w-full" x-bind:disabled="!isEditing" />
                </div>

                <div class="mb-4">
                    <x-input-label for="Telefono" value="Telefono" />
                    <x-text-input type="tel" id="phone" name="phone" value="{{$staff->phone}}"
                        placeholder="Teléfono móvil" class="mt-1 block w-full" x-bind:disabled="!isEditing" />
                </div>

                <div class="mb-4">
                    <x-input-label for="Domicilio" value="Domicilio" />
                    <x-text-input type="text" id="address" name="address" value="{{$staff->address}}"
                        placeholder="Dirección completa" class="mt-1 block w-full" x-bind:disabled="!isEditing" />
                </div>

                <div class="bg-white p-6 rounded-xl mt-6">
                    <h3 class="text-2xl font-bold mb-4 text-center text-indigo-700">Vacaciones</h3>

                    <div class="flex items-center mb-4">
                        <label for="annual-vacation-days" class="mr-2 text-gray-700">Días anuales:</label>
                        <input id="annual-vacation-days" style="text-align: right; width: 65px"
                            value="{{ $annual_vacation_days->days ?? '' }}" x-bind:disabled="!isEditing" type="number"
                            class="border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:border-indigo-500">
                    </div>

                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Año</th>
                                <th scope="col" class="px-6 py-3">Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vacations as $vacation)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $vacation->year }}</td>
                                    <td class="px-6 py-4">{{ $vacation->days }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">
                                        No hay registros de vacaciones.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


                <div class="flex justify-between">
                    <button type="button" @click="isEditing = !isEditing"
                        class="flex items-center gap-2 px-4 py-2 rounded-md" :class="isEditing ? 'bg-gray-200 text-gray-700' : 'bg-red-500 text-white'">
                        <svg x-show="!isEditing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.313 3 21l1.687-4.5 12.175-12.175z" />
                        </svg>
                        <span x-text="isEditing ? 'Finalizar Edición': 'Modificar'"></span>
                    </button>
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded-md">Aceptar</button>
                    <a href="{{ route('staff.list') }}"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-center">
                        Volver
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Asegúrate de cargar Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>

<style>
    /* Estilos para el título */
    .text-indigo-700 {
        color: #4c51bf;
    }

    /* Estilos para la tabla */
    .table-auto {
        width: 100%;
        border-collapse: collapse;
    }

    .table-auto th,
    .table-auto td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .table-auto tr:hover {
        background-color: #f5f5f5;
    }

    .table-auto thead th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .table-auto tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
</style>