@php
    use Carbon\Carbon;
    use App\Models\shift;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mantenimiento de #' . $staff->file_number . ' ' . $staff->name_surname) }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <!-- Inicializa el estado de edición con Alpine.js -->
        <div x-data="{ isEditing: false }" class="bg-white p-8 rounded-xl shadow-lg w-2/4">
            <form x-data="{ isEditing: false }" x-ref="form" method="POST"
                action="{{ route('staff.update', $staff->id) }}">
                @csrf
                @method('POST')
                <!-- Campos bloqueados por defecto -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label for="Nombre y Apellido" value="Nombre y Apellido" />
                        <x-text-input id="name_surname" type="text" name="name_surname" placeholder="Nombre completo"
                            value="{{$staff->name_surname}}" class="mt-1 block w-full" x-bind:disabled="!isEditing" />
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

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <x-input-label for="ID" value="Legajo" />
                        <x-text-input id="file_number" type="text" name="file_number" value="{{$staff->file_number}}"
                            placeholder="file_number" x-bind:disabled="!isEditing" />
                    </div>
                    <div>
                        <x-select id="category" name="category" :options="$categories"
                            placeholder="Seleccionar categoría" :selected="$staff->category_id"
                            x-bind:disabled="!isEditing">
                            Categoría
                        </x-select>
                    </div>
                    <div x-data="{ 
    entryDate: '{{$staff->date_of_entry}}', 
    formattedEntryDate: '', 
    yearsOfService: 0, 
    formatEntryDate() {
        if (this.entryDate) {
            let [year, month, day] = this.entryDate.split('-'); // Asumiendo que viene en formato yyyy-mm-dd
            this.formattedEntryDate = `${day}/${month}/${year}`;
        }
    },
    calculateYears() { 
        if (this.entryDate) { 
            let [year, month, day] = this.entryDate.split('-');
            let entry = new Date(year, month - 1, day); // Meses en JS van de 0 a 11
            let today = new Date();
            
            let years = today.getFullYear() - entry.getFullYear();
            
            let hasAnniversaryPassed = (today.getMonth() > entry.getMonth()) || 
                                       (today.getMonth() === entry.getMonth() && today.getDate() >= entry.getDate());

            this.yearsOfService = hasAnniversaryPassed ? years : years - 1;
        } 
    } 
}" x-init="formatEntryDate(); calculateYears()">

                        <!-- Input para fecha con formato dd/mm/yyyy -->
                        <x-input-label for="Ingreso" value="Ingreso" />
                        <x-text-input id="date_of_entry" type="text" name="date_of_entry" x-model="formattedEntryDate"
                            placeholder="dd/mm/yyyy" class="mt-1 block w-full" x-bind:disabled="!isEditing" />

                    </div>

                    <div x-data="{ 
                                    entryDate: '{{$staff->date_of_entry}}', 
                                    yearsOfService: 0, 
                                    calculateYears() { 
                                        if (this.entryDate) { 
                                            let entry = new Date(this.entryDate);
                                            let today = new Date();
                                            
                                            let years = today.getFullYear() - entry.getFullYear();
                                            
                                            // Verifica si el aniversario de ingreso ya ocurrió este año
                                            let hasAnniversaryPassed = (today.getMonth() > entry.getMonth()) || 
                                                                    (today.getMonth() === entry.getMonth() && today.getDate() >= entry.getDate());

                                            // Si aún no ha pasado el aniversario en este año, restamos 1
                                            this.yearsOfService = hasAnniversaryPassed ? years : years - 1;
                                        } 
                                    } 
                                }" x-init="calculateYears()">

                        <!-- Antigüedad calculada -->
                        <x-input-label for="Antiguedad" value="Antigüedad" />
                        <x-text-input id="years_of_service" type="text" name="years_of_service"
                            x-bind:value="yearsOfService + ' años'" class="mt-1 block w-full"
                            x-bind:disabled="!isEditing" />
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="area-select" class="block font-medium text-sm text-gray-700 mb-1">
                            Áreas
                        </label>
                        <select id="area-select" name="areas[]" multiple
                            class="p-2 h-10 mt-1 block w-full border-gray-300 rounded-md select2-custom"
                            x-bind:disabled="!isEditing">
                            @foreach ($areas as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $assigned_areas) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="worker-status-select" class="block font-medium text-sm text-gray-700 mb-1">
                            Estado
                        </label>
                        <select id="worker-status-select" name="worker_status"
                            class="p-2 h-10 mt-1 block w-full border-gray-300 rounded-md select2-custom"
                            x-bind:disabled="!isEditing">
                            <option value="contratado" {{ $staff->worker_status == 'contratado' ? 'selected' : '' }}>
                                Contratado</option>
                            <option value="planta" {{ $staff->worker_status == 'planta' ? 'selected' : '' }}>Planta
                            </option>
                        </select>
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

                <div class="flex items-center mb-4 mt-2">
                    <label for="annual-vacation-days" class="mr-2 text-gray-700">Días anuales:</label>
                    <input id="annual-vacation-days" style="text-align: right; width: 65px"
                        value="{{ $annual_vacation_days->days ?? '' }}" x-bind:disabled="!isEditing" type="number"
                        class="border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:border-indigo-500">
                </div>



                <div class="container mt-8">
                    <!-- Pestañas -->
                    <ul class="nav nav-tabs" id="attendanceTabs" role="tablist">
                        <!-- Pestaña Vaciones -->
                        <li class="nav-item">
                            <a class="nav-link active" id="vacaciones-tab" data-toggle="tab" href="#vacaciones"
                                role="tab" aria-controls="vacaciones" aria-selected="true">Vacaciones</a>
                        </li>
                        <!-- Pestaña Horarios -->
                        <li class="nav-item">
                            <a class="nav-link" id="horarios-tab" data-toggle="tab" href="#horarios" role="tab"
                                aria-controls="horarios" aria-selected="false">Horarios</a>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="attendanceTabsContent">
                        <!-- Contenido de la pestaña Vacaciones -->

                        <div class="tab-pane fade show active" id="vacaciones" role="tabpanel"
                            aria-labelledby="vacaciones-tab">
                            <div class="">

                                <table class="w-full text-sm text-left text-gray-500    ">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            @foreach ($vacations as $vacation)
                                                <th scope="col" class="px-6 py-3">{{ $vacation->year }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            @foreach ($vacations as $vacation)
                                                <td class="px-6 py-4">{{ $vacation->days }}</td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Contenido de la pestaña Horarios -->
                        <div class="tab-pane fade" id="horarios" role="tabpanel" aria-labelledby="horarios-tab">
                            <div class="">
                                <table class="min-w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th
                                                class="py-3.5 pl-4 pr-3 text-left text-sm font-bold text-gray-900 sm:pl-6">
                                                Lunes</th>
                                            <th class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">Martes
                                            </th>
                                            <th class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">Miércoles
                                            </th>
                                            <th class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">Jueves
                                            </th>
                                            <th class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">Viernes
                                            </th>
                                            <th class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">Sábado
                                            </th>
                                            <th class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">Domingo
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr>
                                            @foreach ([1, 2, 3, 4, 5, 6, 7] as $day)
                                                                                        @php
                                                                                            $schedule = $schedules->firstWhere('day_id', $day);
                                                                                            $shift = $schedule ? shift::find($schedule->shift_id) : null;
                                                                                            $startTime = $shift ? \Carbon\Carbon::parse($shift->startTime)->format('H:i') : '';
                                                                                            $endTime = $shift ? \Carbon\Carbon::parse($shift->endTime)->format('H:i') : '';
                                                                                        @endphp
                                                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                                                            <div
                                                                                                x-data="{
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        isEditing: false, 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        startTime: '{{ $startTime }}', 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        endTime: '{{ $endTime }}',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        saveSchedule() {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            axios.post('{{route('schedule.store')}}', {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                staff_id: {{ $staff['id'] }},
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                day_id: {{ $day }},
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                start_time: this.startTime,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                end_time: this.endTime
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            })
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            .then(response => {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                console.log('Horario guardado:', response.data);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            })
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            .catch(error => {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                console.error('Error al guardar el horario:', error);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    }">
                                                                                                <!-- Modo vista -->
                                                                                                <div x-show="!isEditing">
                                                                                                    <div class="font-medium text-gray-900">
                                                                                                        <template x-if="startTime && endTime">
                                                                                                            <span><span x-text="startTime"></span> a <span
                                                                                                                    x-text="endTime"></span></span>
                                                                                                        </template>
                                                                                                        <template x-if="!startTime || !endTime">
                                                                                                            <span class="text-gray-500">Sin horario</span>
                                                                                                        </template>
                                                                                                    </div>
                                                                                                </div>

                                                                                                <!-- Modo edición -->
                                                                                                @if(Auth::user()->role_id != 1)
                                                                                                    <div x-show="isEditing" class="flex flex-col gap-1">
                                                                                                        <input type="time" x-model="startTime"
                                                                                                            class="h-6 px-2 py-1 text-sm border rounded-md"
                                                                                                            style="width: 90px">
                                                                                                        <input type="time" x-model="endTime"
                                                                                                            class="h-6 px-2 py-1 text-sm border rounded-md"
                                                                                                            style="width: 90px">
                                                                                                    </div>
                                                                                                    <!-- Botón Guardar -->
                                                                                                    <button type="button"
                                                                                                        @click="isEditing = !isEditing; if (!isEditing) saveSchedule()"
                                                                                                        class="mt-2 text-blue-500 hover:text-blue-700 transition-all flex items-center gap-2">
                                                                                                        <svg x-show="!isEditing" xmlns="http://www.w3.org/2000/svg"
                                                                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                                                                            class="w-5 h-5">
                                                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                                                stroke-width="2"
                                                                                                                d="M12 20h9M16.5 3.5a2.121 2.121 0 113 3L7 19H4v-3L16.5 3.5z" />
                                                                                                        </svg>
                                                                                                        <svg x-show="isEditing" xmlns="http://www.w3.org/2000/svg"
                                                                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                                                                            class="w-5 h-5">
                                                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                                                                        </svg>
                                                                                                    </button>
                                                                                                @endif
                                                                                            </div>
                                                                                        </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-2">
                    @if(Auth::check() && Auth::user()->role_id == 2)
                        <button type="button" @click="isEditing = !isEditing; if (!isEditing) $refs.form.submit()"
                            class="flex items-center gap-2 px-4 py-2 rounded-md" :class="isEditing ? 'bg-gray-200 text-gray-700' : 'bg-red-500 text-white'">

                            <svg x-show="!isEditing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.313 3 21l1.687-4.5 12.175-12.175z" />
                            </svg>

                            <span x-text="isEditing ? 'Aceptar' : 'Modificar'"></span>
                        </button>
                    @endif

                    @php
                        $staff = \DB::table('staff')->where('file_number', Auth::user()->file_number)->first();
                    @endphp

                    @if($staff)
                        <a href="{{ route('staff.administration_panel', ['id' => $staff->id]) }}"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-center">
                            Volver
                        </a>
                    @endif
                </div>

            </form>
        </div>
    </div>


    <!-- Asegúrate de cargar Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

</x-app-layout>

<!-- Agregar el CDN de Select2 si no lo tienes -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        function initializeSelect2() {
            $('#area-select').select2({
                placeholder: "Selecciona áreas",
                allowClear: true,
                width: '100%',  // Hace que el select ocupe el ancho completo
                minimumResultsForSearch: Infinity  // Oculta el buscador para que se vea como los otros selects
            }).next('.select2-container').addClass('select2-custom');  // Aplica estilos personalizados
        }

        initializeSelect2(); // Inicializa al cargar la página

        // Si se edita el formulario, activamos o desactivamos el select correctamente
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                if (Alpine.store('isEditing')) {
                    $('#area-select').prop('disabled', false);
                } else {
                    $('#area-select').prop('disabled', true);
                }
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        $('#worker-status-select').select2({
            placeholder: "",
            allowClear: true,
            width: '100%',  // Hace que el select ocupe el ancho completo
            minimumResultsForSearch: Infinity  // Oculta el buscador para que se vea como los otros selects
        }).next('.select2-container').addClass('select2-custom');;
    });
</script>


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