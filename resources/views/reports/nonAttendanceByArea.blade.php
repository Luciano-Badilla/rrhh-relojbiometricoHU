<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reporte de ausentismo por área') }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-3/4">
            @if (session('success'))
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('success') }}
                </div>
            @endif
            <div class="flex justify-between p-3">
                <!-- Campo de búsqueda -->
                <form action="{{ route('reportSearch.nonAttendance') }}" class="w-full" id="search_form">
                    <div class="flex flex-row gap-2 w-full">
                        <div class="flex flex-col">
                            <label for="date" class="block text-sm font-medium text-gray-700">Fecha/s:</label>
                            <div id="date_div" class="">
                                <div class="flex flex-col">
                                    <x-text-input id="date" name="date" type="date" class="h-[2.40rem]"
                                        value="{{ old('date') }}" required />
                                </div>
                            </div>
                            <div id="date_range_div" class="hidden">
                                <div class="flex flex-col">
                                    <div class="flex gap-2">
                                        <x-text-input id="date_from" name="date_from" type="date" class="h-[2.40rem]"
                                            value="{{ old('date_from') }}" />
                                        <x-text-input id="date_to" name="date_to" type="date" class="h-[2.40rem]"
                                            value="{{ old('date_to') }}" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <input id="date_range_checkbox" name="date_range_checkbox" type="checkbox"
                                    class="mt-2 ml-1 border-gray-300 rounded-xl shadow-sm" value="1"
                                    @if (old('date_range_checkbox', request('date_range_checkbox', 0)) == '1') checked @endif />

                                <label for="date_range_checkbox"
                                    class="block text-sm font-medium text-gray-700 mt-1.5 ml-2">Rango</label>
                            </div>
                        </div>
                        <div class="w-1/4">
                            <label for="areas" class="block text-sm font-medium text-gray-700">Área:</label>
                            <select id="area_select" name="area_id" title="Selecciona un área" required
                                class="selectpicker border-gray-300 rounded-xl shadow-sm" data-live-search="true"
                                data-width="100%">
                                <option value="" selected>
                                    Todas las áreas
                                </option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}"
                                        {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-1/4">
                            <label for="absenceReason"
                                class="block text-sm font-medium text-gray-700">Motivo/Justificación:</label>
                            <select id="absenceReason_select" name="absenceReason_id" title="Seleccione un motivo"
                                class="selectpicker border-gray-300 rounded-xl shadow-sm" data-live-search="true"
                                data-width="100%">
                                <option value="">
                                    Seleccione un motivo
                                </option>
                                <option value="00">
                                    Sin justificaciones
                                </option>
                                @foreach ($absenceReasons as $absenceReason)
                                    <option value="{{ $absenceReason->id }}"
                                        {{ old('absenceReason_id') == $absenceReason->id ? 'selected' : '' }}>
                                        {{ $absenceReason->name }} @if (!empty($absenceReason->decree))
                                            - ({{ $absenceReason->decree }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-1/4">
                            <label for="secretary" class="block text-sm font-medium text-gray-700">Secretaria:</label>
                            <select id="secretary_select" name="secretary_id" title="Seleccione una secretaria"
                                class="selectpicker border-gray-300 rounded-xl shadow-sm" data-width="100%">
                                <option value="">
                                    Seleccione una secretaria
                                </option>
                                @foreach ($secretaries as $secretarie)
                                    <option value="{{ $secretarie->id }}"
                                        {{ old('secretary_id') == $secretarie->id ? 'selected' : '' }}>
                                        {{ $secretarie->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-1/4">
                            <label for="worker_status" class="block text-sm font-medium text-gray-700">Estado:</label>
                            <select id="worker_status_Select" name="worker_status" title="Seleccione un estado"
                                class="selectpicker border-gray-300 rounded-xl shadow-sm" data-width="100%">
                                <option value="">
                                    Seleccione un estado
                                </option>
                                @foreach ($worker_status as $worker_statu)
                                    <option value="{{ $worker_statu }}"
                                        {{ old('worker_status') == $worker_statu ? 'selected' : '' }}>
                                        {{ $worker_statu }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <x-button :button="[
                            'id' => 'search-btn',
                            'type' => 'submit',
                            'classes' => 'btn btn-dark rounded-xl custom-tooltip h-[2.40rem] mt-[1.75rem]',
                            'icon' => '<i class=\'fa-solid fa-magnifying-glass\'></i>',
                            'tooltip_text' => 'Buscar',
                        ]" />
                    </div>
                </form>
            </div>
            <div id="loading-overlay" class="hidden">
                @if (session('success'))
                    <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert-warning rounded-t-xl p-0.5 text-center mb-1">
                        {{ session('warning') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert-danger rounded-t-xl p-0.5 text-center mb-1">
                        {{ session('error') }}
                    </div>
                @endif
                <!-- Verifica si no hay tickets -->
                <div class="text-center max-w-md" id="no_alerts" style="margin: 0 auto;">
                    <div class="p-6 rounded-lg mt-3">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <div class="spinner-border text-primary spinner-border-xl" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Obteniendo datos...</h2>
                        <p class="text-gray-600 mb-6">
                            Por favor espere mientras se obtienen los datos.
                        </p>
                    </div>
                </div>
            </div>
            <div id="content" class="px-3">
                @if (!empty($nonAttendances) && !$nonAttendances->isEmpty())
                    <form id="export-form" action="{{ route('reportExport.nonAttendanceByArea') }}" target="_blank"
                        method="POST" class="-mt-8">
                        @csrf
                        <input type="hidden" name="file_name"
                            value="Reporte de inasistencias - {{ $area_selected . ' ' . $dates }}">
                        <input type="hidden" name="nonAttendances" id="nonAttendances">
                        <input type="hidden" name="staffs" id="staffs">
                        <input type="hidden" name="area_selected" value="{{ $area_selected }}">
                        <input type="hidden" name="dates" value="{{ $dates }}">
                        <x-button :button="[
                            'id' => 'export-btn',
                            'classes' => 'btn btn-danger rounded-xl custom-tooltip h-[2.40rem] mt-[1.75rem]',
                            'icon' => '<i class=\'fa-solid fa-file-pdf\'></i>',
                            'tooltip_text' => 'Exportar a PDF',
                            'type' => 'submit',
                        ]" />
                    </form>
                    @foreach ($staffsGrouped as $areaId => $staffGroup)
                        <div class="border rounded-xl mt-2">
                            @php
                                $area = $areas->firstWhere('id', $areaId);
                            @endphp

                            <div class="p-3 bg-gray-100 rounded-t-xl">
                                <h2 class="block font-medium text-xl text-gray-700">
                                    {{ $area?->name ?? 'Área desconocida' }}
                                </h2>
                            </div>
                            <div class="p-3">
                                @foreach ($staffGroup as $staff)
                                    @if ($nonAttendances->where('file_number', $staff->file_number)->count() > 0)
                                        <div class="p-3 border border-gray-300 rounded-xl mt-2 shadow-sm">
                                            <div class="flex gap-3 justify-between">
                                                <div class="flex gap-2">
                                                    <x-button-link :button="[
                                                        'id' => 'administration_panel_btn',
                                                        'route' => 'staff.administration_panel',
                                                        'data' => $staff->id,
                                                        'classes' =>
                                                            'btn btn-dark rounded-xl custom-tooltip administration_panel_btn -mt-2 mb-2',
                                                        'icon' => '<i class=\'fas fa-bars\'></i>',
                                                        'tooltip' => true,
                                                        'tooltip_text' =>
                                                            'Panel administrativo de ' . $staff->name_surname,
                                                    ]" />
                                                    <h2 class="font-semibold text-md text-gray-800 leading-tight mb-2">
                                                        {{ '#' . $staff->file_number . ' ' . $staff->name_surname }}
                                                    </h2>
                                                </div>
                                                <p
                                                    class="font-semibold text-md text-gray-800 leading-tight px-2 rounded-full mb-2.5">
                                                    {{ $nonAttendances->where('file_number', $staff->file_number)->count() }}
                                                    {{ $nonAttendances->where('file_number', $staff->file_number)->count() == 1 ? 'tardanza' : 'tardanzas' }}
                                                </p>
                                            </div>
                                            <x-table class="rounded-none" id="nonAttendances-list" :headers="['#', 'Día', 'Fecha', 'Horario', 'Entrada']"
                                                :fields="[
                                                    'counter',
                                                    'day',
                                                    'date_formated',
                                                    'asssignedSchedule',
                                                    'entryTime',
                                                ]" :data="$nonAttendances->where('file_number', $staff->file_number)" />
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center max-w-md" id="no_assistances" style="margin: 0 auto;">
                        <div class="p-6 rounded-lg mt-3">
                            <div
                                class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-clipboard-user text-3xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">No hay inasistencias</h2>
                            <p class="text-gray-600 mb-6">
                                No se encontraron inasistencias según los filtros aplicados.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    $(document).ready(function() {
        const date_div = $('#date_div');
        const date_range_div = $('#date_range_div');
        const date_range_checkbox = $('#date_range_checkbox');
        const date_range_checkbox_control = $('#date_range_checkbox_control');
        const date = $('#date');
        const date_from = $('#date_from');
        const date_to = $('#date_to');
        const area_select = $('#area_select');
        const absenceReason_select = $('#absenceReason_select');
        const content = $('#content');
        const loading_overlay = $('#loading-overlay');
        const search_form = $('#search_form');

        // Mantener el área seleccionada después de la recarga
        if ("{{ old('area_id') }}") {
            area_select.val("{{ old('area_id') }}");
        }

        // Mantener el área seleccionada después de la recarga
        if ("{{ old('absenceReason_id') }}") {
            area_select.val("{{ old('absenceReason_id') }}");
        }

        // Verificar si el checkbox estaba marcado antes de la recarga y actualizar la UI
        if (date_range_checkbox.prop('checked')) {
            date_range_div.removeClass('hidden');
            date_from.attr('required', true);
            date_to.attr('required', true);
            date.removeAttr('required');
            date_div.addClass('hidden');
        } else {
            date_div.removeClass('hidden');
            date_range_div.addClass('hidden');
            date_from.removeAttr('required');
            date_to.removeAttr('required');
            date.attr('required', true);
        }

        // Evento al hacer clic en el checkbox
        date_range_checkbox.change(function() {
            date_to.val(null);
            if ($(this).prop('checked')) {
                date_range_div.removeClass('hidden');
                date_from.attr('required', true);
                date_to.attr('required', true);
                date.removeAttr('required');
                date_div.addClass('hidden');
                date_from.val(date.val());
            } else {
                date_div.removeClass('hidden');
                date_range_div.addClass('hidden');
                date_from.removeAttr('required');
                date_to.removeAttr('required');
                date.attr('required', true);
                date.val(date_from.val());

            }
        });

        date_range_checkbox_control.prop('checked', false);

        // Manejo de carga mientras se envía el formulario
        search_form.on("submit", function(event) {
            content.hide();
            loading_overlay.show();
        });

        $('#export-btn').click(function(e) {
            e.preventDefault(); // Evita la recarga de la página

            // Asigna los valores de PHP a los campos ocultos
            $('#nonAttendances').val(JSON.stringify(@json($nonAttendances)));
            $('#staffs').val(JSON.stringify(@json($staffs)));

            // Enviar el formulario
            $('#export-form').submit();
        });

        $('.administration_panel_btn').click(function() {
            localStorage.removeItem('page_loaded');
        });
    });
</script>
