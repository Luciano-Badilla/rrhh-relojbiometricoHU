@php
    use Carbon\Carbon;
    use App\Models\shift;
@endphp
<style>
    .nav-link.active {
        background-color: #f3f4f6 !important;
        color: rgb(17 24 39 1) !important;
        font-weight: 500 !important;
    }

    .nav-link {
        border-top-left-radius: 0.5rem !important;
        border-top-right-radius: 0.5rem !important;
        color: rgb(17 24 39 1) !important;
    }
</style>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asistencias de #' . $staff->file_number . ' ' . $staff->name_surname) }}
        </h2>
    </x-slot>
    <x-modal-custom id="add_attendance_modal" title="Agregar marca de asistencia"
        subtitle="Esta acción agregará una marca de asistencia para esta persona.">
        <form action="" method="POST" id="add_attendance_form">
            @csrf
            <div class="px-3 flex flex-col gap-3 justify-center items-center">
                <div class="flex gap-3">
                    <x-text-input id="attendance_id" name="attendance_id" type="text" class="hidden" />
                    <x-text-input id="file_number" name="file_number" type="text" class="hidden"
                        value="{{ $staff->file_number }}" />
                    <div class="flex flex-col">
                        <label for="entryTime" class="block text-sm font-medium text-gray-700">Nueva marca:</label>
                        <x-text-input id="entryTime" name="attendance_time" type="time" step="1" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col px-[7%] mb-3">
                <label for="observations" class="block text-sm font-medium text-gray-700">Observaciones: (Se agregara su nombre despues de la observación)</label>
                <x-text-input id="observations" name="observations" value="Ingreso manual" type="text" class="w-100" required />
            </div>
            <div class="flex justify-end px-3">
                <button type="submit" class="btn btn-success rounded-xl" id="add_attendance_btn">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>
    <x-modal-custom id="add_manual_attendance_modal" title="Agregar marca de asistencia"
        subtitle="Esta acción agregará una marca de asistencia para esta persona, si hay una inasistencia este día sera eliminada">
        <form action="{{ route('attendance.add_manual') }}" method="POST" id="add_attendance_manual_form">
            @csrf
            <div class="px-3 mt-1 flex flex-col gap-3 justify-center items-center">
                <div class="flex gap-3">
                    <div class="flex flex-col">
                        <label for="attendance_date" class="block text-sm font-medium text-gray-700">Fecha:</label>
                        <x-text-input id="attendance_date" name="attendance_date" type="date" class="h-10" required/>
                    </div>
                    <x-text-input id="staff_id" name="staff_id" type="text" class="hidden"
                        value="{{ $staff->id }}" />
                    <div class="flex flex-col">
                        <label for="entryTime" class="block text-sm font-medium text-gray-700">Entrada:</label>
                        <x-text-input id="entryTime" name="entryTime" type="time" step="1" required />
                    </div>
                    <div class="flex flex-col">
                        <label for="departureTime" class="block text-sm font-medium text-gray-700">Salida:</label>
                        <x-text-input id="departureTime" name="departureTime" type="time" step="1" required/>
                    </div>
                </div>
            </div>
            <div class="flex flex-col px-[7%] mb-3 mt-2">
                <label for="observations" class="block text-sm font-medium text-gray-700">Observaciones: (Se agregara su nombre despues de la observación)</label>
                <x-text-input id="observations" name="observations" type="text" value="Ingreso manual" required />
            </div>
            <div class="flex justify-end px-3 gap-2">
                <button type="submit" class="btn btn-success rounded-xl" id="add_attendance_btn">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>
    <x-modal-custom id="add_many_nonattendance_modal" title="Agregar inasistencias"
        subtitle="Esta acción agregará inasistencias para esta persona, si hay una asistencia en alguna de las fechas, la inasistencia no se creara">
        <form action="{{ route('non_attendance.add_manual') }}" method="POST">
            @csrf
            <div class="px-3 mt-1 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full justify-center items-center">
                    <div class="flex flex-row gap-3">
                        <div>
                            <label for="attendance_date" class="block text-sm font-medium text-gray-700">Desde:</label>
                            <x-text-input id="attendance_date1" name="attendance_date_from" type="date" class="h-10" required/>
                        </div>
                        <div>
                            <label for="attendance_date" class="block text-sm font-medium text-gray-700">Hasta:</label>
                            <x-text-input id="attendance_date2" name="attendance_date_to" type="date" class="h-10" required/>
                        </div>
                    </div>
                    <x-text-input id="staff_id" name="staff_id" type="text" class="hidden"
                        value="{{ $staff->id }}" />
                    <div class="flex flex-col mb-3">
                        <label for="observations" class="block text-sm font-medium text-gray-700">Justificaciones:</label>
                        <div class="border-gray-300">
                            <select id="absenceReason_select"
                                name="absenceReason" 
                                class="selectpicker select_modal border-gray-300 rounded-xl shadow-sm" 
                                data-live-search="true" 
                                data-width="100%">
                            @foreach ($absenceReasons as $absenceReason)
                                <option value="{{ $absenceReason->id }}">{{ $absenceReason->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted">Convenio: {{$staff->collective_agreement->name ?? 'No asignado'}}</p>
                        <p id="warning-text" class="text-red-500 text-sm mt-1 hidden"></p>

                        

                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col px-[6%] mb-3 mt-1">
                <label for="observations" class="block text-sm font-medium text-gray-700">Observaciones: (Se agregara su nombre despues de la observación)</label>
                <x-text-input id="observations" name="observations" type="text" value="Ingreso manual" required />
            </div>
            <div class="flex justify-end px-3 gap-2">
                <button type="submit" class="btn btn-success rounded-xl" id="add_attendance_btn">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>
    <x-modal-custom id="add_nonattendance_modal" title="Agregar justificaciones de ausencia"
        subtitle="Esta acción agregará una justificación de ausencia para esta persona.">
        <form action="" method="POST" id="add_nonattendance_form">
            @csrf
            <div class="flex flex-col px-3 mb-3 w-full">
                <x-text-input id="nonattendance_id" name="nonattendance_id" type="text" class="hidden" />
                <label for="absenceReason_select" class="block text-sm font-medium text-gray-700">Justificaciones:</label>
                <select id="absenceReason_select" name="absenceReason" class="selectpicker select_modal_2" data-live-search="true" data-width="100%">
                    @foreach ($absenceReasons as $absenceReason)
                        <option value="{{ $absenceReason->id }}">{{ $absenceReason->name }}</option>
                    @endforeach
                </select>
                <p class="text-muted">Convenio: {{$staff->collective_agreement->name ?? 'No asignado'}}</p>
                <p id="warning-text" class="text-red-500 text-sm mt-1 hidden"></p>

                

            </div>
            <div class="flex flex-col px-[3%] mb-3 mt-1 w-full">
                <label for="observations" class="block text-sm font-medium text-gray-700">Observaciones: (Se agregara su nombre despues de la observación)</label>
                <x-text-input id="observations" name="observations" class="w-full" type="text" value="Ingreso manual" required />
            </div>
            
            <div class="flex justify-end px-3">
                <button type="submit" class="btn btn-success rounded-xl" id="add_attendance_btn">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>
    <x-modal-custom id="view_absenseReason_modal" title="Justificaciones de {{$year}}" subtitle="">
        <div class="p-2">
            @if ($absenceReasonCount->isEmpty())
                <!-- Verifica si no hay tickets -->
                <div class="text-center max-w-md" id="no_assistances" style="margin: 0 auto;">
                    <div class="p-6 rounded-lg mt-3">
                        <div
                            class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-clipboard-user text-2xl"></i>
                        </div>
                        <h2 class="text-1xl font-bold text-gray-900 mb-2">No hay justificaciones</h2>
                        <p class="text-gray-600 mb-6">
                            No se justificaron inasistencias.
                        </p>
                    </div>
                </div>
            @endif
            @if ($absenceReasonCount->isNotEmpty())
                <x-table id="view_absenseReason-list" :headers="['Motivo', 'Cantidad']" :fields="['name','count']" :data="$absenceReasonCount"/>
            @endif
        </div>

    </x-modal-custom>
    <x-modal-custom id="add_vacations_modal" title="Agregar vacaciones"
        subtitle="Esta acción agregará inasistencias justificadas con vacaciones para esta persona.">
        <form action="{{ route('vacations.add') }}" method="POST">
            @csrf
            <div class="px-3 mt-1 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full justify-center items-center">
                    <div class="flex flex-row gap-3">
                        <div>
                            <label for="vacations_date" class="block text-sm font-medium text-gray-700">Desde:</label>
                            <x-text-input id="vacations_date1" name="vacations_date_from" type="date" class="h-10" required value="{{old('vacations_date_from')}}"/>
                        </div>
                        <div>
                            <label for="vacations_date" class="block text-sm font-medium text-gray-700">Hasta:</label>
                            <x-text-input id="vacations_date2" name="vacations_date_to" type="date" class="h-10" required value="{{old('vacations_date_to')}}"/>
                        </div>
                        
                    </div>
                    <div class="flex gap-1 w-full pl-[4rem]">
                        <label class="block text-sm font-medium text-gray-700">Cantidad de días:</label>
                        <p id="vacation_days" class="text-gray-900 font-semibold -mt-0.5">0</p>
                    </div>
                    <div class="flex gap-1 w-full pl-[4rem] -mt-6">
                        <p id="vacation_error" class="text-red-600 font-semibold text-sm mt-1 hidden">El total de dias disponibles es: {{ $totalVacationDays }}.</p>

                    </div>
                    
                    <x-text-input id="staff_id" name="staff_id" type="text" class="hidden"
                        value="{{ $staff->id }}" />
                        <div>
                            <table class="w-full text-sm text-center text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                    <tr>
                                        @foreach ($vacations as $vacation)
                                            <th scope="col" class="px-6 py-3">{{ $vacation->year }}</th>
                                        @endforeach
                                        <th scope="col" class="px-6 py-3">Total</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        @foreach ($vacations as $vacation)
                                            <td class="px-6 py-4">{{ $vacation->days }}</td>
                                        @endforeach
                                        <td class="px-6 py-4">{{ $totalVacationDays }}</td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
            <div class="flex flex-col px-[6%] mb-3 mt-1">
                <label for="observations" class="block text-sm font-medium text-gray-700">Observaciones: (Se agregara su nombre despues de la observación)</label>
                <x-text-input id="observations" name="observations" type="text" value="Ingreso manual" required />
            </div>
            <div class="flex justify-end px-3 gap-2">
                <span id="errorText" class="text-danger hidden"></span>
                <button type="submit" class="btn btn-success rounded-xl" id="add_vacations_btn">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>
    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-3/4">
            <div id="loading-overlay" class="hidden">
                @if (session('success'))
                    <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert-warning rounded-t-xl py-0.5 px-2 text-center mb-1">
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
            <div class="hidden" id="content">
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
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1 hidden" id="success-alert">
                </div>
                <div class="alert-error rounded-t-xl p-0.5 text-center mb-1 hidden" id="error-alert">
                </div>
                <!-- Campo de búsqueda -->
                <div class="p-3 flex">
                    <form action="{{ route('staff.attendance', ['id' => $staff->id]) }}" method="GET" class="w-full">
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
                                <x-button :button="[
                                    'id' => 'search-btn',
                                    'type' => 'submit',
                                    'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                                    'icon' => '<i class=\'fa-solid fa-magnifying-glass\'></i>',
                                    'tooltip_text' => 'Buscar asistencias',
                                    'loading' => true
                                ]" />

                                <x-button :button="[
                                    'id' => 'update-btn',
                                    'type' => 'button',
                                    'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                                    'icon' => '<i class=\'fa-solid fa-rotate\'></i>',
                                    'tooltip_text' => 'Actualizar',
                                    'loading' => true
                                ]" />
                            </div>
                            
                        </div>
                    </form>
                    <div class="ml-auto flex gap-1">
                        <div class="mt-[1.25rem]">
                            <x-button :button="[
                                'id' => 'view_nonattendance_btn',
                                'classes' => 'btn btn-success rounded-xl custom-tooltip h-10',
                                'icon' => '<i class=\'fa-solid fa-clipboard-list\'></i>',
                                'tooltip_text' => 'Ver cantidad de justificaciones usadas',
                                'modal_id' => 'view_absenseReason_modal'
                            ]" />
                        </div>
                        <form id="export-form" action="{{ route('reportExport.attendance') }}" target="_blank"
                            method="POST" class="-mt-8">
                            @csrf
                            <div class="flex flex-col lg:flex-row items-left lg:items-center gap-3">
                                <div class="w-full lg:mt-6">
                                    <input type="hidden" name="file_name"
                                    value="Resumen de asistencias - {{ $staff->name_surname . ' ' . Carbon::now()->translatedFormat('d F Y') }}">
                                <input type="hidden" name="attendances" id="attendances">
                                <input type="hidden" name="non_attendances" id="non_attendances">
                                <input type="hidden" name="staff" id="staff">
                                <input type="hidden" name="days" id="days">
                                <input type="hidden" name="totalHours" id="totalHours">
                                <input type="hidden" name="hoursAverage" id="hoursAverage">
                                <input type="hidden" name="totalExtraHours" id="totalExtraHours">
                                <input type="hidden" name="schedules" id="schedules">
                                <x-button :button="[
                                    'id' => 'export-btn',
                                    'classes' => 'btn btn-success rounded-xl custom-tooltip h-[2.40rem] mt-[1.75rem]',
                                    'icon' => '<i class=\'fa-solid fa-table\'></i>',
                                    'tooltip_text' => 'Exportar resumen a Excel',
                                    'type' => 'submit',
                                ]" />
                                </div>
                            </div>
                            
                        </form>
                    </div>

                </div>

                <div class="flex">
                    <div class="flex flex-row gap-3 justify-left w-full px-3">
                        <x-card title="Días completados:" icon="fa-solid fa-calendar-check" :content="$days" :clickeable="false" class="cursor-default" />
                        <x-card title="Horas totales:" icon="fa-solid fa-calendar-check" :content="$totalHours" :clickeable="false" class="cursor-default" />
                        <x-card title="Promedio de horas:" icon="fa-chart-pie" :content="$hoursAverage" :clickeable="false" class="cursor-default" />
                        <x-card title="Horas adicionales:" icon="fa-solid fa-clock-rotate-left" :content="$totalExtraHours" :clickeable="false" class="cursor-default" />
                    </div>
                </div>
                <div class="m-3">
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
                                @foreach ([1, 2, 3, 4, 5, 6, 7] as $day)
                                    @php
                                        // Buscar el horario correspondiente al día
                                        $schedule = $schedules->firstWhere('day_id', $day);
                                    @endphp
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($schedule)
                                            <div class="font-medium text-gray-900">
                                                {{ Carbon::parse(shift::find($schedule->shift_id)->startTime)->format('H:i') . ' a ' . Carbon::parse(shift::find($schedule->shift_id)->endTime)->format('H:i') }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ Carbon::parse(shift::find($schedule->shift_id)->startTime)->diffInHours(Carbon::parse(shift::find($schedule->shift_id)->endTime)->format('H:i')) }}
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
                <div class="container -mt-2">
                    <!-- Pestañas -->
                    <ul class="nav nav-tabs" id="attendanceTabs" role="tablist">
                        <!-- Pestaña Asistencias -->
                        <li class="nav-item">
                            <a class="nav-link active" id="asistencias-tab" data-toggle="tab" href="#asistencias"
                                role="tab" aria-controls="asistencias" aria-selected="true">Asistencias</a>
                        </li>
                        <!-- Pestaña Inasistencias -->
                        <li class="nav-item">
                            <a class="nav-link" id="inasistencias-tab" data-toggle="tab" href="#inasistencias"
                                role="tab" aria-controls="inasistencias" aria-selected="false">Inasistencias</a>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="attendanceTabsContent">
                        <!-- Contenido de la pestaña Asistencias -->
                        <div class="tab-pane fade show active" id="asistencias" role="tabpanel"
                            aria-labelledby="asistencias-tab">
                            <div class="mt-3">
                                @if ($attendance->isEmpty())
                                    <!-- Verifica si no hay tickets -->
                                    <div class="text-center max-w-md" id="no_assistances" style="margin: 0 auto;">
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
                                    <div class="flex gap-1 justify-between mb-3">
                                        <div>
                                            <x-button :button="[
                                                'id' => 'add_manual_attendance',
                                                'classes' => 'btn btn-dark rounded-xl custom-tooltip add_nonattendance_btn h-10',
                                                'icon' => '<i class=\'fa-solid fa-plus\'></i>',
                                                'tooltip_text' => 'Agregar marca de asistencia manual',
                                                'modal_id' => 'add_manual_attendance_modal',
                                                'role' => 2
                                            ]" />
                                            
                                        </div>
                                        
                                    </div>
                                    <x-table id="attendance-list" clas :headers="[
                                        'Dia',
                                        'Fecha',
                                        'Entrada',
                                        'Salida',
                                        'Horas cumplidas',
                                        'Horas adicionales',
                                        'Observaciones',
                                    ]" :fields="[
                                        'day',
                                        'date_formated',
                                        'entryTime',
                                        'departureTime',
                                        'hoursCompleted',
                                        'extraHours',
                                        'observations',
                                    ]" :data="$attendance"
                                        :buttons="[
                                            [
                                                'id' => 'edit_btn',
                                                'classes' => 'btn btn-dark rounded-xl custom-tooltip edit_btn h-10',
                                                'icon' => '<i class=\'fas fa-fingerprint\'></i>',
                                                'tooltip_text' => 'Agregar marca de asistencia',
                                                'modal_id' => 'add_attendance_modal',
                                                'data-entryTime' => true,
                                                'role' => 2,
                                                'data-departureTime' => true,
                                                'condition' => fn($record) => $record->entryTime ===
                                                    $record->departureTime &&
                                                    $record->date_formated != Carbon::now()->format('d/m/y'),
                                            ],
                                        ]" />
                                @endif
                            </div>
                        </div>

                        <!-- Contenido de la pestaña Inasistencias -->
                        <div class="tab-pane fade" id="inasistencias" role="tabpanel"
                            aria-labelledby="inasistencias-tab">
                            <div class="mt-3 text-center">
                                @if ($nonAttendance->isEmpty())
                                <div class="flex flex-col justify-start mb-3">
                                    <div class="flex w-full gap-1">
                                        <x-button :button="[
                                            'id' => 'add_many_nonattendance_btn',
                                            'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                                            'icon' => '<i class=\'fa-solid fa-plus\'></i>',
                                            'tooltip_text' => 'Justificador de inasistencias',
                                            'modal_id' => 'add_many_nonattendance_modal'
                                        ]" />
                                        <x-button :button="[
                                            'id' => 'add_vacations',
                                            'classes' => 'btn btn-dark rounded-xl custom-tooltip add_vacations_btn h-10',
                                            'icon' => '<i class=\'fa-solid fa-suitcase-rolling\'></i>',
                                            'tooltip_text' => 'Agregar vacaciones',
                                            'modal_id' => 'add_vacations_modal',
                                            'role' => 2
                                        ]" />
                                    </div>
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

                                @if ($nonAttendance->isNotEmpty())
                                <div class="flex justify-start mb-3">
                                    <div class="flex w-full justify-between">
                                        <div>
                                            <x-button :button="[
                                                'id' => 'add_many_nonattendance_btn',
                                                'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                                                'icon' => '<i class=\'fa-solid fa-plus\'></i>',
                                                'tooltip_text' => 'Justificador de inasistencias',
                                                'modal_id' => 'add_many_nonattendance_modal',
                                                'role' => 2
                                            ]" />
                                            
                                            <x-button :button="[
                                                'id' => 'add_vacations',
                                                'classes' => 'btn btn-dark rounded-xl custom-tooltip add_vacations_btn h-10',
                                                'icon' => '<i class=\'fa-solid fa-suitcase-rolling\'></i>',
                                                'tooltip_text' => 'Agregar vacaciones',
                                                'modal_id' => 'add_vacations_modal',
                                                'role' => 2
                                            ]" />
                                        </div>
                                        
                                    </div>
                                    
                                </div>
                                    <x-table id="non_attendance-list" :headers="['Día', 'Fecha', 'Motivo', 'Observaciones']" :fields="['day', 'date', 'absence_reason_name', 'observations']"
                                        :data="$nonAttendance" :buttons="[
                                            [
                                                'id' => 'add_nonattendance_btn',
                                                'classes' => 'btn btn-dark rounded-xl custom-tooltip add_nonattendance_btn h-10',
                                                'icon' => '<i class=\'fas fa-plus\'></i>',
                                                'tooltip_text' => 'Agregar justificaciones de ausencia',
                                                'modal_id' => 'add_nonattendance_modal',
                                                'role' => 2,
                                                'data-id' => true,
                                                'condition' => fn($record) => $record->absenceReason === null || $record->absenceReason === ''
                                            ],
                                        ]" />
                                @endif
                            </div>
                        </div>
                    </div>
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
        
        updateBtn.click(function() {
            const id = "{{ $staff->id }}";
            localStorage.removeItem('page_loaded');
            window.location.reload();

        });

        document.querySelectorAll('.edit_btn').forEach(button => {
            button.addEventListener('click', function() {
                
                // Obtener valores de los atributos data-*
                const entryTime = this.getAttribute('data-entrytime'); // Formato "HH:MM:SS"
                const id = this.getAttribute('data-id'); // ID de la asistencia

                // Rellenar los campos de entrada
                document.getElementById('attendance_id').value = id;

                // Cambiar dinámicamente el atributo 'action' del formulario
                const form = document.getElementById('add_attendance_form');
                url = "{{ route('attendance.add') }}";
                form.action =
                    url + "/" + id; // Ajusta esta ruta según tus necesidades
            });
        });

        document.querySelectorAll('.add_nonattendance_btn').forEach(button => {
            button.addEventListener('click', function() {
                const id_nonattendance = this.getAttribute('data-id');
                document.getElementById('nonattendance_id').value = id_nonattendance;

                // Cambiar dinámicamente el atributo 'action' del formulario
                const form = document.getElementById('add_nonattendance_form');
                url = "{{ route('absereason.add') }}";
                form.action =
                    url + "/" + id_nonattendance; // Ajusta esta ruta según tus necesidades
            });
        });

        $('#add_attendance_btn').click(function() {
            // Eliminar la clave 'page_loaded' del localStorage
            localStorage.removeItem('page_loaded');
        });

        $('#report_individual_hours_btn').click(function() {
            const dataToExport = @json($dataToExport);
            const encodedData = encodeURIComponent(JSON.stringify(dataToExport));
            window.location.href = "{{ route('report.individual_hours') }}?data=" + encodedData;
        });
        const totalVacationDays = {{ $totalVacationDays }};
        const inputFrom = document.getElementById('vacations_date1');
        const inputTo = document.getElementById('vacations_date2');
        const daysOutput = document.getElementById('vacation_days');
        const addBtn = document.getElementById('add_vacations_btn');
        const errorText = document.getElementById('vacation_error');


        function calculateDays() {
            const fromDate = new Date(inputFrom.value);
            const toDate = new Date(inputTo.value);
            const collective_agreement_id = @json($staff->collective_agreement_id);

            const isCollectiveAgreementValid = collective_agreement_id !== null;

            if (!isNaN(fromDate.getTime()) && !isNaN(toDate.getTime())) {
                const timeDiff = toDate - fromDate;
                const dayDiff = Math.floor(timeDiff / (1000 * 60 * 60 * 24)) + 1;

                const validDays = dayDiff > 0 ? dayDiff : 0;
                daysOutput.textContent = validDays;

                if (validDays > totalVacationDays || !isCollectiveAgreementValid) {
                    addBtn.disabled = true;
                    errorText.classList.remove('hidden');
                    errorText.textContent = !isCollectiveAgreementValid
                        ? "Esta persona no tiene seleccionado un convenio."
                        : "Supera los días disponibles.";
                } else {
                    addBtn.disabled = false;
                    errorText.classList.add('hidden');
                }
            } else {
                daysOutput.textContent = 0;
                addBtn.disabled = true;
                errorText.classList.add('hidden');
            }
        }


        inputFrom.addEventListener('change', calculateDays);
        inputTo.addEventListener('change', calculateDays);

        calculateDays(); // Para evaluar valores pre-cargados


        $('#export-btn').click(function(e) {
            e.preventDefault(); // Evita la recarga de la página

            // Asigna los valores de PHP a los campos ocultos
            $('#attendances').val(JSON.stringify(@json($attendance)));
            $('#non_attendances').val(JSON.stringify(@json($nonAttendance)));
            $('#staff').val(JSON.stringify(@json($staff)));
            $('#days').val(JSON.stringify(@json($days)));
            $('#totalHours').val(JSON.stringify(@json($totalHours)));
            $('#hoursAverage').val(JSON.stringify(@json($hoursAverage)));
            $('#totalExtraHours').val(JSON.stringify(@json($totalExtraHours)));
            $('#schedules').val(JSON.stringify(@json($schedules)));

            // Enviar el formulario
            $('#export-form').submit();
        });

        const razonesCountMonth = {{ $razonesCountMonth }};
        const razonesCountYear = {{ $razonesCountYear }};

        const warningText = document.getElementById('warning-text');
        const selects = document.querySelectorAll('#absenceReason_select');

        selects.forEach(select => {
            select.addEventListener('change', function () {
                const selectedText = select.options[select.selectedIndex].text.toLowerCase();
                const isRazones = selectedText.includes('raz') || selectedText.includes('part') || selectedText.includes('particulares');

                if (isRazones) {
                    let messages = [];
                    if (razonesCountMonth >= 2) {
                        messages.push(`ya tiene ${razonesCountMonth} "razones particulares" en el mes seleccionado`);
                    }
                    if (razonesCountYear >= 6) {
                        messages.push(`ya tiene ${razonesCountYear} "razones particulares" en el año`);
                    }

                    if (messages.length > 0) {
                        warningText.innerText = `Advertencia: esta persona ${messages.join(' y ')}.`;
                        warningText.classList.remove('hidden');
                    } else {
                        warningText.classList.add('hidden');
                    }
                } else {
                    warningText.classList.add('hidden');
                }
            });
        });
    });
</script>
