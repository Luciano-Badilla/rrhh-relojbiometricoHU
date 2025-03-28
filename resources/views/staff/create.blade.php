<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Nuevo Staff') }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white p-8 rounded-xl shadow-lg w-2/4">
            <form method="POST" action="{{ route('staff.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="name_surname" value="Nombre y Apellido" />
                        <x-text-input id="name_surname" type="text" name="name_surname" placeholder="Nombre completo"
                            class="mt-1 block w-full" required />
                    </div>

                    <div>
                        <x-select id="coordinator" name="coordinator" :options="$coordinators"
                            placeholder="Seleccionar Coordinador" required>
                            Coordinador
                        </x-select>
                    </div>

                    <div>
                        <x-select id="secretary_id" name="secretary_id" :options="$secretaries"
                            placeholder="Seleccionar Secretaria" required>
                            Secretaria
                        </x-select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="file_number" value="Legajo" />
                        <x-text-input id="file_number" class="w-full" type="text" name="file_number"
                            placeholder="Número de legajo" required />
                    </div>
                    <div>
                        <x-input-label for="category_id" value="Categoria" />
                        <x-select id="category" name="category_id" :options="$categories" class="rounded-xl"
                            required></x-select>
                    </div>

                    <div x-data="{
    entryDate: '',
    formattedEntryDate: '',
    updateDate() {
        if (this.formattedEntryDate) {
            let parts = this.formattedEntryDate.split('/');
            if (parts.length === 3) {
                this.entryDate = `${parts[2]}-${parts[1]}-${parts[0]}`; // Convertir a yyyy-mm-dd
            }
        }
    }
}">
                        <x-input-label for="Ingreso" value="Ingreso" />

                        <!-- Input que muestra la fecha en formato dd/mm/yyyy -->
                        <x-text-input id="date_of_entry" type="text" name="formatted_date_of_entry"
                            x-model="formattedEntryDate" placeholder="dd/mm/yyyy" class="mt-1 block w-full"
                            @blur="updateDate()" x-bind:disabled="!isEditing" />

                        <!-- Input oculto que enviará la fecha en formato yyyy-mm-dd -->
                        <input type="hidden" name="date_of_entry" x-model="entryDate" />
                    </div>

                </div>
                <div>
                    <x-input-label for="worker-status-select" value="Estado" />
                    <select id="worker-status-select" name="worker_status"
                        class="p-2 h-10 mt-1 block w-full border-gray-300 rounded-md select2" required>
                        <option value="contratado">Contratado</option>
                        <option value="planta">Planta</option>
                    </select>
                </div>
                <div>
                    <label for="area-select" class="block font-medium text-sm text-gray-700 mb-1">
                        Áreas
                    </label>
                    <select id="area-select" name="areas[]" multiple
                        class="p-2 h-10 mt-1 block w-full border-gray-300 rounded-md select2-custom"
                        x-bind:disabled="!isEditing">
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" {{ $area->name}}>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <x-input-label for="email" value="Email" />
                    <x-text-input type="email" id="email" name="email" value="" placeholder="correo@ejemplo.com"
                        class="mt-1 block w-full" />
                </div>

                <div class="mb-4">
                    <x-input-label for="Telefono" value="Telefono" />
                    <x-text-input type="tel" id="phone" name="phone" value="" placeholder="Teléfono móvil"
                        class="mt-1 block w-full" />
                </div>

                <div class="mb-4">
                    <x-input-label for="Domicilio" value="Domicilio" />
                    <x-text-input type="text" id="address" name="address" value="" placeholder="Dirección completa"
                        class="mt-1 block w-full" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button type="submit">Guardar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<!-- Asegúrate de cargar Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<!-- Agregar el CDN de Select2 si no lo tienes -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>


<SCRipt>
    document.addEventListener("DOMContentLoaded", function () {
        $('#worker-status-select').select2({
            placeholder: "",
            allowClear: true,
            width: '100%',  // Hace que el select ocupe el ancho completo
            minimumResultsForSearch: Infinity  // Oculta el buscador para que se vea como los otros selects
        }).next('.select2-container').addClass('select2-custom');;
    });

    $(document).ready(function () {
        function initializeSelect2() {
            $('#area-select').select2({
                placeholder: "Selecciona áreas",
                allowClear: true,
                width: '100%',  // Hace que el select ocupe el ancho completo
                minimumResultsForSearch: Infinity  // Oculta el buscador para que se vea como los otros selects
            }).next('.select2-container').addClass('select2-custom');  // Aplica estilos personalizados
        }
        initializeSelect2();
    });
</SCRipt>

<style>
    .select2-container--default .select2-selection--multiple {
        background-color: #fff !important;
        /* Blanco como los otros selects */
        border: 1px solid #d1d5db !important;
        /* Borde gris como los otros selects */
        border-radius: 6px !important;
        /* Bordes redondeados */
        height: 38px !important;
        /* Altura similar a los otros selects */
        display: flex;
        align-items: center;
        padding: 4px;
        --tw-shadow: 0 4px 4px 0 rgb(0 0 0 / 0.08);
        --tw-shadow-colored: 0 3px 2px 0 var(--tw-shadow-color);
        box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
    }

    .select2-container--default .select2-selection {
        background-color: #fff !important;
        /* Blanco como los otros selects */
        border: 1px solid #d1d5db !important;
        /* Borde gris como los otros selects */
        border-radius: 6px !important;
        /* Bordes redondeados */
        height: 38px !important;
        /* Altura similar a los otros selects */
        display: flex;
        align-items: center;
        padding: 4px;
        --tw-shadow: 0 4px 4px 0 rgb(0 0 0 / 0.08);
        --tw-shadow-colored: 0 3px 2px 0 var(--tw-shadow-color);
        box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        overflow-x: auto;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e5e7eb !important;
        /* Color gris claro como los otros selects */
        border: none !important;
        border-radius: 4px !important;
        padding: 2px 6px;
        font-size: 14px;
    }
</style>