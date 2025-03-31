<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reporte de Inasistencias') }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-2/4">
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
                                <x-text-input id="date_range_checkbox_control" name="date_range_checkbox" type="hidden"
                                    value="0" />
                                <x-text-input id="date_range_checkbox" name="date_range_checkbox" type="checkbox"
                                    class="mt-2 ml-1" value="1"
                                    {{ old('date_range_checkbox') ? 'checked' : '' }} />
                                <label for="date_range_checkbox"
                                    class="block text-sm font-medium text-gray-700 mt-1.5 ml-2">Rango</label>
                            </div>
                        </div>
                        <div class="w-1/4">
                            <label for="areas" class="block text-sm font-medium text-gray-700">Area:</label>
                            <select id="area_select" name="area_id" required title="Selecciona un área"
                                class="selectpicker border-gray-300 rounded-xl shadow-sm" data-live-search="true"
                                data-width="100%">
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}"
                                        {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }}
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
                            'loading' => true,
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
            <div id="content" class="p-3">
                @isset($nonAttendances)
                    @foreach ($staffs as $staff)
                        @if ($nonAttendances->where('file_number', $staff->file_number)->count() > 0)
                            <p>{{ $staff->name_surname }}</p>
                            <x-table id="non_attendance-list" :headers="['Día', 'Fecha', 'Motivo']" :fields="['day', 'date', 'absenceReason']" :data="$nonAttendances->where('file_number', $staff->file_number)" />
                        @endif
                    @endforeach
                @endisset()
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    $(document).ready(function() {
        const date_div = $('#date_div');
        const date_range_div = $('#date_range_div');
        const date_range_checkbox = $('#date_range_checkbox');
        const date = $('#date');
        const date_from = $('#date_from');
        const date_to = $('#date_to');
        const area_select = $('#area_select');
        const content = $('#content');
        const loading_overlay = $('#loading-overlay');
        const search_form = $('#search_form');

        // Si ya hay un área seleccionada después de recargar la página, mantenerla
        if ("{{ old('area_id') }}") {
            area_select.val("{{ old('area_id') }}");
        }

        // Si el checkbox estaba marcado antes de la recarga, mantener la visibilidad de los elementos
        if (date_range_checkbox.is(':checked')) {
            date_range_div.removeClass('hidden');
            date_from.attr('required', true);
            date_to.attr('required', true);
            date.removeAttr('required');
            date_div.addClass('hidden');
        }

        date_range_checkbox.click(function() {
            if ($(this).prop('checked')) {
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
        });

        search_form.on("submit", function(event) {
            content.hide();
            loading_overlay.show();
        });
    });
</script>
