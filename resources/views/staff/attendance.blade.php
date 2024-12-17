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
                                class="border-gray-300 rounded-xl shadow-md {{ $errors->has('month') ? 'border-red-500' : '' }} block w-full py-2 px-2"
                                required>
                                <option value="">Seleccione un mes</option>
                                <option value="1" {{ old('month', $month) == 1 ? 'selected' : '' }}>Enero</option>
                                <option value="2" {{ old('month', $month) == 2 ? 'selected' : '' }}>Febrero
                                </option>
                                <option value="3" {{ old('month', $month) == 3 ? 'selected' : '' }}>Marzo</option>
                                <option value="4" {{ old('month', $month) == 4 ? 'selected' : '' }}>Abril</option>
                                <option value="5" {{ old('month', $month) == 5 ? 'selected' : '' }}>Mayo</option>
                                <option value="6" {{ old('month', $month) == 6 ? 'selected' : '' }}>Junio</option>
                                <option value="7" {{ old('month', $month) == 7 ? 'selected' : '' }}>Julio</option>
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
                                class="border-gray-300 rounded-xl shadow-md {{ $errors->has('year') ? 'border-red-500' : '' }} block w-full"
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
            <div class="flex flex-row">
                <div class="px-3 flex flex-col items-left">
                    <span class="text-sm font-medium text-gray-700">Días: {{ $days }}</span>
                    <span class="text-sm font-medium text-gray-700">Horas:
                        {{ $totalHours }}</span>
                </div>
                <div class="px-3 flex flex-col items-left">
                    <span class="text-sm font-medium text-white">.</span>
                    <span class="text-sm font-medium text-gray-700">Promedio de horas:
                        {{ $hoursAverage }}</span>
                </div>

            </div>


            @if ($attendance->isEmpty())
                <!-- Verifica si no hay tickets -->
                <div class="text-center max-w-md" id="no_alerts" style="margin: 0 auto;">
                    <div class="p-6 rounded-lg mt-3">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
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
                <x-table id="attendance-list" :headers="['Dia', 'Fecha', 'Entrada', 'Salida', 'Horas cumplidas', 'Observaciones']" :fields="['day', 'date', 'entryTime', 'departureTime', 'hoursCompleted', 'observations']" :data="$attendance" />
            @endif
        </div>
    </div>
</x-app-layout>
<script>
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
                    {{ $staff->file_number }}), // Reemplaza ID_placeholder con el id dinámico
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
