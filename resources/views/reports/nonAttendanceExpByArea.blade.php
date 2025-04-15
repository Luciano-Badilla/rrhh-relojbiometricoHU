<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Exp. de inasistencias mensual') }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-[87%]">
            @if (session('success'))
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('success') }}
                </div>
            @endif
            <div class="flex justify-between p-3">
                <!-- Campo de búsqueda -->
                <form action="{{ route('reportSearch.nonAttendanceExp') }}" class="w-full" id="search_form">
                    <div class="flex flex-row gap-2 w-full">
                        <div class="flex flex-row gap-2 w-1/3">
                            <div class="w-full">
                                <label for="month" class="block text-sm font-medium text-gray-700">Mes:</label>
                                <select name="month" id="month"
                                    class="border-gray-300 rounded-xl h-[38px] shadow-sm {{ $errors->has('month') ? 'border-red-500' : '' }} block w-full py-2 px-2"
                                    required>
                                    <option value="">Seleccione un mes</option>
                                    <option value="1" {{ old('month') == 1 ? 'selected' : '' }}>Enero
                                    </option>
                                    <option value="2" {{ old('month') == 2 ? 'selected' : '' }}>Febrero
                                    </option>
                                    <option value="3" {{ old('month') == 3 ? 'selected' : '' }}>Marzo
                                    </option>
                                    <option value="4" {{ old('month') == 4 ? 'selected' : '' }}>Abril
                                    </option>
                                    <option value="5" {{ old('month') == 5 ? 'selected' : '' }}>Mayo
                                    </option>
                                    <option value="6" {{ old('month') == 6 ? 'selected' : '' }}>Junio
                                    </option>
                                    <option value="7" {{ old('month') == 7 ? 'selected' : '' }}>Julio
                                    </option>
                                    <option value="8" {{ old('month') == 8 ? 'selected' : '' }}>Agosto
                                    </option>
                                    <option value="9" {{ old('month') == 9 ? 'selected' : '' }}>Septiembre
                                    </option>
                                    <option value="10" {{ old('month') == 10 ? 'selected' : '' }}>Octubre
                                    </option>
                                    <option value="11" {{ old('month') == 11 ? 'selected' : '' }}>Noviembre
                                    </option>
                                    <option value="12" {{ old('month') == 12 ? 'selected' : '' }}>Diciembre
                                    </option>
                                </select>
                                @error('month')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Input para Año -->
                            <div class="w-full">
                                <label for="year" class="block text-sm font-medium text-gray-700">Año:</label>
                                <input type="number" id="year" name="year"
                                    class="border-gray-300 rounded-xl h-[38px] shadow-sm {{ $errors->has('year') ? 'border-red-500' : '' }} block w-full"
                                    required min="2000" max="{{ date('Y') }}" placeholder="Ingrese el año"
                                    value="{{ old('year') }}" />
                                @error('year')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
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
                        <!--
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
                        </div>-->

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
                @if (!empty($nonAttendances) && $nonAttendances->count())
                    <div class="flex gap-1">
                        <form id="exportExcel-form" action="{{ route('reportExport.nonAttendanceExp') }}"
                            target="_blank" method="POST" class="-mt-8">
                            @csrf
                            <input type="hidden" name="file_name"
                                value="Expediente mensual de inasistencias  - {{ $area_selected . ' ' . $dates }}">
                            <input type="hidden" name="nonAttendances" id="nonAttendances">
                            <input type="hidden" name="staffs" id="staffs">
                            <input type="hidden" name="areas" id="areas">
                            <input type="hidden" name="area_selected" value="{{ $area_selected }}">
                            <input type="hidden" name="dates" value="{{ $dates }}">

                            <x-button :button="[
                                'id' => 'exportExcel-btn',
                                'classes' => 'btn btn-success rounded-xl custom-tooltip h-[2.40rem] mt-[1.75rem]',
                                'icon' => '<i class=\'fa-solid fa-table\'></i>',
                                'tooltip_text' => 'Exportar a Excel',
                                'type' => 'submit',
                            ]" />
                        </form>

                    </div>

                    <div class="mt-4">
                        @foreach ($nonAttendances as $group)
                            <div class="border rounded-xl mt-2 shadow-sm">
                                <div class="p-3 bg-gray-100 rounded-t-xl">
                                    <h2 class="block font-medium text-xl text-gray-700">
                                        {{ $group['reason'] }}
                                    </h2>

                                    @php
                                        $info = $group['reason_data'] ?? null;
                                    @endphp

                                    @if ($info)
                                        <p class="text-sm text-gray-600 mt-1">
                                            @if ($info['article'])
                                                <strong>Artículo:</strong> {{ $info['article'] }}
                                            @endif
                                            @if ($info['subsection'])
                                                &nbsp; <strong>Inciso:</strong> {{ $info['subsection'] }}
                                            @endif
                                            @if ($info['decree'])
                                                &nbsp; <strong>Decreto:</strong> {{ $info['decree'] }}
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <div class="p-3">
                                    <x-table class="rounded-none"
                                        id="nonAttendances-table-{{ Str::slug($group['reason']) }}" :headers="['#Legajo', 'Nombre', 'Días', 'Fecha(s)']"
                                        :fields="['file_number', 'name', 'days_count', 'date_range']" :data="collect($group['staffs'])->flatMap(function ($staff) {
                                            return collect($staff['rango_fechas'])->map(function ($rango) use ($staff) {
                                                return (object) [
                                                    'file_number' => '#' . $staff['file_number'],
                                                    'name' => $staff['name'],
                                                    'date_range' => $rango['date_range'],
                                                    'days_count' => $rango['days_count'],
                                                ];
                                            });
                                        })" />

                                </div>
                            </div>
                        @endforeach
                    </div>
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

        $('#exportExcel-btn').click(function(e) {
            e.preventDefault();

            const form = $(this).closest('form');

            form.find('#nonAttendances').val(JSON.stringify(@json($nonAttendances)));
            form.find('#staffs').val(JSON.stringify(@json($staffs)));
            form.find('#areas').val(JSON.stringify(@json($areas)));

            form.submit();
        });

        $('.administration_panel_btn').click(function() {
            localStorage.removeItem('page_loaded');
        });
    });
</script>
