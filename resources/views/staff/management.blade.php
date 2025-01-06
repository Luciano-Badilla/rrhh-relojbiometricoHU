<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mantenimiento de #' . $staff->file_number . ' ' . $staff->name_surname) }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <!-- Inicializa el estado de edición con Alpine.js -->
        <div x-data="{ isEditing: false }" class="bg-white p-8 rounded-xl shadow-lg w-2/4">
            <!-- Usa el estado para deshabilitar todo -->
            <form :class="{ 'opacity-50 pointer-events-none'}">
                <!-- Campos bloqueados por defecto -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="legajo" value="Legajo" />
                        <x-text-input id="legajo" type="text" name="legajo" value="{{$staff->file_number}}"
                            placeholder="Legajo" />
                    </div>
                    <div>
                        <x-select id="coordinator" name="coordinator" :options="$coordinators"
                            placeholder="Seleccionar Coordinador" :selected="$staff->coordinator_id">
                            Coordinador
                        </x-select>
                    </div>
                    <div>
                        <x-select id="secretary" name="secretary" :options="$secretaries"
                            placeholder="Seleccionar secretaria" :selected="$staff->secretary_id">
                            Secretaria
                        </x-select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" type="text" name="nombre" placeholder="Nombre completo"
                            value="{{$staff->name_surname}}" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-select id="category" name="category" :options="$categories"
                            placeholder="Seleccionar categoría" :selected="$staff->category_id">
                            Categoría
                        </x-select>
                    </div>
                    <div>
                        <x-select id="scale" name="scale" :options="$scales" placeholder="Seleccionar escalafón"
                            :selected="$staff->scale_id">
                            Escalafón
                        </x-select>
                    </div>
                </div>

                <div class="mb-4">
                    <x-input-label for="email" value="Email" />
                    <x-text-input type="email" id="email" name="email" value="{{$staff->email}}"
                        placeholder="correo@ejemplo.com" class="mt-1 block w-full" />
                </div>

                <div class="mb-4">
                    <x-input-label for="telefono" value="Teléfono" />
                    <x-text-input type="tel" id="telefono" name="telefono" value="{{$staff->phone}}"
                        placeholder="Teléfono móvil" class="mt-1 block w-full" />
                </div>

                <div class="mb-4">
                    <x-input-label for="domicilio" value="Domicilio" />
                    <x-text-input type="text" id="domicilio" name="domicilio" value="{{$staff->address}}"
                        placeholder="Dirección completa" class="mt-1 block w-full" />
                </div>

                <div class="flex justify-between">
                    <button type="button" @click="isEditing = !isEditing"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">
                        <span x-text="isEditing ? 'Finalizar Edición' : 'Modificar'"></span>
                    </button>
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded-md">Aceptar</button>
                    <button type="reset" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Asegúrate de cargar Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
