@php
    use Carbon\Carbon;
    Carbon::setLocale('es'); // Establece el idioma en español
    $monthName = Carbon::now()->subMonth()->translatedFormat('F');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista del Personal') }}
        </h2>
    </x-slot>
    <x-modal-custom id="add_staff_modal" title="Nuevo personal" subtitle="">
        <form action="{{ route('staff.store') }}" method="POST" id="add_attendance_form">
            @csrf
            <div class="px-3 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full">
                    <div>
                        <x-input-label for="file_number" value="Legajo" />
                        <x-text-input id="file_number" class="w-full" type="text" name="file_number"
                            placeholder="Número de legajo" required />
                    </div>
                    <div>
                        <x-input-label for="name_surname" value="Nombre y Apellido" />
                        <x-text-input id="name_surname" type="text" name="name_surname" placeholder="Nombre completo"
                            class="mt-1 block w-full" required />
                    </div>
                </div>
            </div>
            <div class="flex justify-end px-3 mt-3">
                <button type="submit" class="btn btn-success rounded-xl">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>
    <x-modal-custom id="add_eventuality_modal" title="Agregar eventualidad"
        subtitle="Se justificaran todas las inasistencias del personal en la fecha elegida">
        <form action="{{ route('eventuality.add') }}" method="POST" id="add_eventuality_modal">
            @csrf
            <div class="px-4 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full items-center">
                    <div class="w-1/3">
                        <x-input-label for="date" value="Fecha:" />
                        <x-text-input id="date" class="w-full" type="date" name="date"
                            placeholder="Motivo de la eventualidad" required />
                    </div>
                    <div class="w-full">
                        <x-input-label for="absenceReason" value="Justificación:" />
                        <x-text-input id="absenceReason" type="text" name="absenceReason" class="mt-1 block w-full"
                            required />
                    </div>
                    <div class="w-full">
                        <label for="observations" class="block text-sm font-medium text-gray-700">Observaciones: (Se
                            agregara su nombre despues de la observación)</label>
                        <x-text-input id="observations" name="observations" type="text" value="Ingreso manual"
                            class="w-full" required />
                    </div>
                </div>
            </div>
            <div class="flex justify-end px-3 mt-3">
                <x-button :button="[
                    'id' => 'add-btn',
                    'type' => 'submit',
                    'classes' => 'btn btn-success rounded-xl h-10',
                    'icon' => '',
                    'text' => 'Agregar',
                ]" />
            </div>
        </form>
    </x-modal-custom>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-[55%]">
            @if (session('success'))
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('success') }}
                </div>
            @endif
            <div class="flex justify-between items-center p-3">
                <!-- Contenedor del buscador y botón de agregar staff -->
                <div class="flex items-center space-x-3">
                    <!-- Campo de búsqueda -->
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <x-text-input id="table-search" type="text" placeholder="Buscar..." class="ps-10 h-[2.40rem]"
                            autofocus />
                    </div>
                    <div class="flex items-center space-x-2 w-1/2">
                        <select id="area-select" name="area_id" title="Selecciona un área"
                            class="border-gray-300 rounded-xl shadow-sm p-1.5">
                            <option value="">Todas las áreas</option> <!-- Opción por defecto -->
                            @foreach ($areas as $area)
                                <option value="{{ $area->name }}">
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                        <!-- Botón para limpiar el select -->
                        <x-button :button="[
                            'id' => 'clear-area-select',
                            'type' => 'button',
                            'classes' => 'btn btn-dark rounded-xl custom-tooltip h-[2.40rem]',
                            'icon' => '<i class=\'fa-solid fa-filter-circle-xmark\'></i>',
                            'tooltip' => true,
                            'tooltip_text' => 'Borrar filtros',
                        ]" />
                    </div>
                </div>
                <div class="flex gap-1">
                    <!-- Botón de agregar staff -->
                    <x-button :button="[
                        'id' => 'add-btn',
                        'type' => 'button',
                        'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                        'icon' => '<i class=\'fa-solid fa-plus\'></i>',
                        'tooltip' => true,
                        'tooltip_text' => 'Agregar staff',
                        'modal_id' => 'add_staff_modal',
                    ]" />
                    <x-button :button="[
                        'id' => 'add-eventuality-btn',
                        'type' => 'button',
                        'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                        'icon' => '<i class=\'fa-solid fa-calendar-xmark\'></i>',
                        'tooltip' => true,
                        'tooltip_text' => 'Agregar eventualidad',
                        'modal_id' => 'add_eventuality_modal',
                    ]" />

                    <!-- Botón de backup (se mantiene en la misma posición) -->
                    <form action="{{ route('clockLogs.backup') }}">
                        <x-button :button="[
                            'id' => 'backup-btn',
                            'type' => 'submit',
                            'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                            'icon' => '<i class=\'fa-solid fa-rotate\'></i>',
                            'tooltip' => true,
                            'tooltip_text' => 'Respaldar marcas del reloj en la BD local',
                            'loading' => true,
                        ]" />
                    </form>
                </div>

            </div>


            <div class="m-3">
                <!-- Tabla -->
                <x-table id="staff-list" :headers="['Legajo', 'Nombre', 'Áreas']" :fields="['file_number', 'name_surname', 'areas_name']" :data="$staff" :row-classes="fn($row) => $row->inactive_since ? 'bg-red-100' : ''"
                    :links="[
                        [
                            'id' => 'administration_panel_btn',
                            'route' => 'staff.administration_panel',
                            'classes' => 'btn btn-dark rounded-xl custom-tooltip administration_panel_btn',
                            'icon' => '<i class=\'fas fa-bars\'></i>',
                            'tooltip' => true,
                            'tooltip_text' => 'Panel administrativo',
                        ],
                    ]"
                    :buttons="[
                        [
                            'id' => 'export-btn',
                            'route' => 'staff.administration_panel',
                            'classes' => 'btn btn-success rounded-xl custom-tooltip h-[2.40rem] attendance-export-btn',
                            'icon' => '<i class=\'fa-solid fa-table\'></i>',
                            'tooltip' => true,
                            'tooltip_text' => 'Exportar resumen de '.$monthName.
                            ' a Excel ',
                            'data-file_number' => true,
                        ],
                    ]" />

            </div>
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById("table-search");
        const searchSelect = document.getElementById("area-select");
        const table = document.getElementById("staff-list");
        const rows = table.querySelectorAll("tbody tr");

        function filterTable() {
            const textFilter = searchInput.value.toLowerCase();
            const areaFilter = searchSelect.value.toLowerCase();

            rows.forEach(row => {
                const cells = row.querySelectorAll("td");
                const rowText = Array.from(cells)
                    .map(cell => cell.textContent.toLowerCase())
                    .join(" ");

                const matchesText = rowText.includes(textFilter);
                const matchesArea = !areaFilter || rowText.includes(areaFilter);

                row.style.display = (matchesText && matchesArea) ? "" : "none";
            });
        }

        // Evento para limpiar el select
        document.getElementById("clear-area-select").addEventListener("click", () => {
            $('#table-search').val(null).trigger('input');
            searchSelect.value = "";
            filterTable();
        });

        searchInput.addEventListener("input", filterTable);
        searchSelect.addEventListener("change", filterTable);

        $('.administration_panel_btn').click(function() {
            localStorage.removeItem('page_loaded');
        });

        $('#add-staff-btn').click(function() {
            window.location.href = "{{ route('staff.create') }}";
        });
        $('.selectpicker').selectpicker('refresh'); // Solo si usas Bootstrap select

        $(document).on('click', '.attendance-export-btn', function() {
            var button = $(this);
            var originalContent = button.html(); // Guardamos el contenido original
            button.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span> Cargando...'
            );

            var id = button.data('id');
            var file_number = button.data('file_number');

            $.ajax({
                url: "{{ route('clockLogs.update', ['file_number' => '__ID__']) }}".replace(
                    '__ID__', file_number),
                type: 'GET',
                data: {
                    _token: '{{ csrf_token() }}',
                    file_number: file_number
                },
                success: function() {
                    console.log('Ajax de update de datos exitoso');
                    $.ajax({
                        url: '{{ route('reportExport.attendanceSearch', ['id' => '__ID__']) }}'
                            .replace('__ID__', id),
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            console.log('Ajax de obtencion de datos exitoso');

                            const fechaActual = new Date();

                            // Restar un mes
                            const fechaMesAnterior = new Date(fechaActual);
                            fechaMesAnterior.setMonth(fechaActual.getMonth() -
                                1);

                            // Formatear en español
                            const opciones = {
                                month: 'long',
                                year: 'numeric'
                            };
                            let mesAnteriorFormateado = fechaMesAnterior
                                .toLocaleDateString('es-ES', opciones);

                            // Capitalizar la primera letra (opcional)
                            mesAnteriorFormateado = mesAnteriorFormateado
                                .charAt(0).toUpperCase() + mesAnteriorFormateado
                                .slice(1);

                            var form = $('<form>', {
                                method: 'POST',
                                action: '{{ route('reportExport.attendance') }}',
                                target: '_blank'
                            });

                            form.append($('<input>', {
                                type: 'hidden',
                                name: '_token',
                                value: $('meta[name="csrf-token"]')
                                    .attr('content')
                            }));

                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'staff',
                                value: JSON.stringify(response
                                    .staff)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'days',
                                value: JSON.stringify(response.days)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'totalHours',
                                value: JSON.stringify(response
                                    .totalHours)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'hoursAverage',
                                value: JSON.stringify(response
                                    .hoursAverage)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'totalExtraHours',
                                value: JSON.stringify(response
                                    .totalExtraHours)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'schedules',
                                value: JSON.stringify(response
                                    .schedules)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'attendances',
                                value: JSON.stringify(response
                                    .attendances)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'non_attendances',
                                value: JSON.stringify(response
                                    .non_attendances)
                            }));
                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'file_name',
                                value: 'Resumen de asistencias - ' +
                                    response.staff.name_surname +
                                    ' - ' + mesAnteriorFormateado
                            }));

                            form.append($('<input>', {
                                type: 'hidden',
                                name: 'fecha',
                                value: JSON.stringify(response
                                    .fecha)
                            }));

                            $('body').append(form);
                            form.submit();
                            form.remove();

                            // Restaurar el botón
                            button.prop('disabled', false).html(
                                originalContent);
                        },
                        error: function(error) {
                            console.error(
                                'Error al obtener el reporte de asistencia:',
                                error);
                            button.prop('disabled', false).html(
                                originalContent);
                        }
                    });
                },
                error: function() {
                    alert('Error al obtener datos');
                    button.prop('disabled', false).html(originalContent);
                }
            });
        });






    });
</script>
