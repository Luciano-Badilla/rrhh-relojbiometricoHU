@php
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asistencias de #' . $staff->file_number . ' ' . $staff->name_surname) }}
        </h2>
    </x-slot>
    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-2/4">
            <div id="loading-overlay" class="hidden">
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
                            Porfavor espere mientras se obtienen los datos.
                        </p>
                    </div>
                </div>
            </div>
            <div class="hidden" id="content">
                @if (session('success'))
                    <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1 hidden" id="success-alert">
                </div>
                <div class="alert-error rounded-t-xl p-0.5 text-center mb-1 hidden" id="error-alert">
                </div>
                <!-- Campo de búsqueda -->
                <div class="p-3">
                    <form action="{{ route('staff.attendance', ['id' => $staff->id]) }}" method="GET">
                        <div class="flex flex-col lg:flex-row items-left lg:items-center gap-3">
                            <!-- Select para Meses -->
                            <div class="w-full">
                                <label for="month" class="block text-sm font-medium text-gray-700">Mes:</label>
                                <select name="month" id="month"
                                    class="border-gray-300 rounded-xl shadow-sm {{ $errors->has('month') ? 'border-red-500' : '' }} block w-full py-2 px-2"
                                    required>
                                    <option value="">Seleccione un mes</option>
                                    <option value="1" {{ old('month', $month) == 1 ? 'selected' : '' }}>Enero
                                    </option>
                                    <option value="2" {{ old('month', $month) == 2 ? 'selected' : '' }}>Febrero
                                    </option>
                                    <option value="3" {{ old('month', $month) == 3 ? 'selected' : '' }}>Marzo
                                    </option>
                                    <option value="4" {{ old('month', $month) == 4 ? 'selected' : '' }}>Abril
                                    </option>
                                    <option value="5" {{ old('month', $month) == 5 ? 'selected' : '' }}>Mayo
                                    </option>
                                    <option value="6" {{ old('month', $month) == 6 ? 'selected' : '' }}>Junio
                                    </option>
                                    <option value="7" {{ old('month', $month) == 7 ? 'selected' : '' }}>Julio
                                    </option>
                                    <option value="8" {{ old('month', $month) == 8 ? 'selected' : '' }}>Agosto
                                    </option>
                                    <option value="9" {{ old('month', $month) == 9 ? 'selected' : '' }}>Septiembre
                                    </option>
                                    <option value="10" {{ old('month', $month) == 10 ? 'selected' : '' }}>Octubre
                                    </option>
                                    <option value="11" {{ old('month', $month) == 11 ? 'selected' : '' }}>Noviembre
                                    </option>
                                    <option value="12" {{ old('month', $month) == 12 ? 'selected' : '' }}>Diciembre
                                    </option>
                                </select>
                                @error('month')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Input para Año -->
                            <div class="w-full">
                                <label for="year" class="block text-sm font-medium text-gray-700">Año:</label>
                                <input type="number" id="year" name="year" value="{{ $year }}"
                                    class="border-gray-300 rounded-xl shadow-sm {{ $errors->has('year') ? 'border-red-500' : '' }} block w-full"
                                    required min="2000" max="{{ date('Y') }}" placeholder="Ingrese el año" />
                                @error('year')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Botón de Envío -->
                            <div class="w-full lg:mt-6">
                                <button type="submit" class="btn btn-dark rounded-xl custom-tooltip"
                                    data-tooltip_text="Buscar asistencias">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                                <button class="btn btn-dark rounded-xl custom-tooltip" id="update-btn" type="button"
                                    data-tooltip_text="Actualizar base de datos"><i class="fa-solid fa-rotate"
                                        id="update-icon"></i>
                                    <div class="spinner-border spinner-border-sm hidden my-1" id="updating-icon"
                                        role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </form>

                </div>

                <div class="flex">
                    <div class="flex flex-row gap-3 justify-left w-full px-3">
                        <div
                            class="estado relative block overflow-hidden bg-white border border-black rounded-xl p-3 hover:text-black w-25 shadow-sm">
                            <div class="flex flex-row items-center justify-between pb-2">
                                <h2 class="text-md font-bold">Días:</h2>
                                <i class="fa-solid fa-calendar-check h-4 w-4 text-gray-500"></i>
                            </div>
                            <div>
                                <div class="text-md font-semibold text-gray-500">
                                    {{ $days }}
                                </div>
                            </div>
                        </div>
                        <div
                            class="estado relative block overflow-hidden bg-white border border-black rounded-xl p-3 hover:text-black w-25 shadow-sm">
                            <div class="flex flex-row items-center justify-between pb-2">
                                <h2 class="text-md font-bold">Horas:</h2>
                                <i class="fa-solid fa-clock h-4 w-4 text-gray-500"></i>
                            </div>
                            <div>
                                <div class="text-md font-semibold text-gray-500">
                                    {{ $totalHours }}
                                </div>
                            </div>
                        </div>
                        <div
                            class="estado relative block overflow-hidden bg-white border border-black rounded-xl p-3 hover:text-black w-25 shadow-sm">
                            <div class="flex flex-row items-center justify-between pb-2">
                                <h2 class="text-md font-bold whitespace-nowrap">Promedio de horas:</h2>
                                <i class="fa-solid fa-chart-pie h-4 w-4 text-gray-500"></i>
                            </div>
                            <div>
                                <div class="text-md font-semibold text-gray-500">
                                    {{ $hoursAverage }}
                                </div>
                            </div>
                        </div>
                        <div
                            class="estado relative block overflow-hidden bg-white border border-black rounded-xl p-3 hover:text-black w-25 shadow-sm">
                            <div class="flex flex-row items-center justify-between pb-2">
                                <h2 class="text-md font-bold">Horas extra:</h2>
                                <i class="fa-solid fa-clock-rotate-left h-4 w-4 text-gray-500"></i>
                            </div>
                            <div>
                                <div class="text-md font-semibold text-gray-500">{{ $totalExtraHours }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl m-3">
                    <table class="min-w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-bold text-gray-900 sm:pl-6">Lunes
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">
                                    Martes
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">
                                    Miércoles</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">
                                    Jueves
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">
                                    Viernes
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">
                                    Sábado
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-gray-900">
                                    Domingo
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr>
                                @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $day)
                                    @php
                                        // Buscar el horario correspondiente al día
                                        $schedule = $schedules->firstWhere('day', $day);
                                    @endphp
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($schedule)
                                            <div class="font-medium text-gray-900">
                                                {{ \Carbon\Carbon::parse($schedule->startTime)->format('H:i') }} a
                                                {{ \Carbon\Carbon::parse($schedule->endTime)->format('H:i') }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ \Carbon\Carbon::parse($schedule->startTime)->diffInHours($schedule->endTime) }}
                                                horas
                                            </div>
                                        @else
                                            <div class="text-gray-500">Sin horario</div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="overflow-hidden rounded-xl m-3">
                    @if ($attendance->isEmpty())
                        <!-- Verifica si no hay tickets -->
                        <div class="text-center max-w-md" id="no_alerts" style="margin: 0 auto;">
                            <div class="p-6 rounded-lg mt-3">
                                <div
                                    class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-clipboard-user text-3xl"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">No hay asistencias</h2>
                                <p class="text-gray-600 mb-6">
                                    No se encontraron asistencias según los filtros aplicados.
                                </p>
                            </div>
                        </div>
                    @endif
                    @if ($attendance->isNotEmpty())
                        <x-table id="attendance-list" :headers="[
                            'Dia',
                            'Fecha',
                            'Entrada',
                            'Salida',
                            'Horas cumplidas',
                            'Horas extra',
                            'Observaciones',
                        ]" :fields="[
                            'day',
                            'date',
                            'entryTime',
                            'departureTime',
                            'hoursCompleted',
                            'extraHours',
                            'observations',
                        ]" :data="$attendance" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    $(document).ready(function() {
        const overlay = $('#loading-overlay');
        const content = $('#content');
        const updateBtn = $('#update-btn');
        const storageKey = "page_loaded";

        // Mostrar spinner si es la primera carga
        if (!localStorage.getItem(storageKey)) {
            overlay.removeClass('hidden');
            localStorage.setItem(storageKey, true);

            // Simular la carga inicial con AJAX
            $.ajax({
                url: "{{ route('clockLogs.update', ['file_number' => $staff->file_number]) }}",
                type: 'GET',
                data: {
                    _token: '{{ csrf_token() }}',
                    file_number: {{ $staff->file_number }}
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('Error al obtener datos');
                }
            });
        } else {
            // Mostrar el contenido en la segunda carga
            content.removeClass('hidden');
        }
    });

    $(document).ready(function() {
        $('#update-btn').click(function() {
            const updateIcon = $('#update-icon');
            const updatingIcon = $('#updating-icon');
            const id = "{{ $staff->id }}";

            updateIcon.hide();
            updatingIcon.show();

            $.ajax({
                url: "{{ route('clockLogs.update', ['file_number' => 'file_number_placeholder']) }}"
                    .replace('file_number_placeholder',
                        {{ $staff->file_number }}
                    ), // Reemplaza ID_placeholder con el id dinámico
                type: 'GET',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF token de Laravel
                    file_number: {{ $staff->file_number }} // Puedes agregar el file_number aquí
                },
                success: function(response) {
                    custom_alert(response.message, 'success');
                    updateIcon.show();
                    updatingIcon.hide();
                    setInterval(() => {
                        window.location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    custom_alert(error, 'error');
                    updateIcon.show();
                    updatingIcon.hide();
                }
            });


        });
    });
</script>
