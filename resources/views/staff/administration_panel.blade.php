<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel administrativo de #{{ $staff->file_number }} {{ $staff->name_surname }}
        </h2>
    </x-slot>
    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-2/4 p-4">
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
                            Por favor espere mientras se obtienen los datos.
                        </p>
                    </div>
                </div>
            </div>
            <div class="hidden" id="content">
                <div class="flex gap-2">
                    <x-card title="Mantenimiento" content="Consulta y gestiona la información del personal"
                        route="{{ route('staff.management', ['id' => $staff->id]) }}" icon="fas fa-user-pen" />
                    <x-card title="Asistencias e Inasistencias"
                        content="Consulta y gestiona el registro de asistencias e inasistencias del personal."
                        route="{{ route('staff.attendance', ['id' => $staff->id]) }}" icon="fas fa-calendar-days" />
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
<script>
    $(document).ready(function() {
        const overlay = $('#loading-overlay');
        const content = $('#content');
        const storageKey = "page_loaded";

        // Mostrar spinner si es la primera carga
        if (!localStorage.getItem(storageKey)) {
            console.log('False');
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
            console.log('true');

        }
    });
</script>

