<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manetenimiento de #' . $staff->file_number . ' ' . $staff->name_surname) }}
        </h2>
    </x-slot>
    <div class="flex items-center justify-center py-6">
        <div class="bg-white p-8 rounded-xl shadow-lg w-2/4">
            <form>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="legajo" value="Legajo" />
                        <x-text-input id="legajo" type="text" name="legajo" value="{{$staff->file_number}}"
                            placeholder="Legajo" required />
                    </div>
                    <div>
                        <x-input-label for="cargo" value="Cargo" />
                        <x-text-input id="cargo" type="text" name="cargo" placeholder="Cargo"
                            class="mt-1 block w-full" />
                    </div>
                    <div>
                    <x-select id="secretary" name="secretary" :options="$secretaries"
                            placeholder="Seleccionar secretaria">
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
                            placeholder="Seleccionar categoría">
                            Categoría
                        </x-select>
                    </div>
                    <div>
                        <x-select id="scale" name="scale" :options="$scales"
                            placeholder="Seleccionar escalafon">
                            Escalafon
                        </x-select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">

                    <div>
                        <label for="opciones" class="block text-sm font-medium text-gray-700">Opciones</label>
                        <div class="mt-1 flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" class="form-checkbox" id="nodoc">
                                <span class="ml-2">NoDoc</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" class="form-checkbox" id="cct">
                                <span class="ml-2">CCT</span>
                            </label>
                        </div>
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
                    <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Buscar</button>
                    <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Agregar</button>
                    <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Borrar</button>
                    <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Modificar</button>
                    <button type="button" class="bg-black text-white px-4 py-2 rounded-md">Aceptar</button>
                    <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Cancelar</button>
                    <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md">Cerrar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>