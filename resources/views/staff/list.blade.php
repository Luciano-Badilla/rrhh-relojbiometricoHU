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

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-2/4">
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
                            class="selectpicker border-gray-300 rounded-xl shadow-sm" data-live-search="true">
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
                            'tooltip_text' => 'Todas las áreas',
                        ]" />
                    </div>
                </div>
                <div>
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

                    <!-- Botón de backup (se mantiene en la misma posición) -->
                    <x-button :button="[
                        'id' => 'backup-btn',
                        'type' => 'button',
                        'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                        'icon' => '<i class=\'fa-solid fa-rotate\'></i>',
                        'tooltip' => true,
                        'tooltip_text' => 'Backup de marcadas',
                        'loading' => true,
                    ]" />
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
            searchSelect.value = "";
            filterTable();
        });

        searchInput.addEventListener("input", filterTable);
        searchSelect.addEventListener("change", filterTable);

        $('.administration_panel_btn').click(function() {
            localStorage.removeItem('page_loaded');
        });

        $('#backup-btn').click(function() {
            $.ajax({
                url: "{{ route('clockLogs.backup') }}",
                type: 'GET',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    custom_alert(response.message, 'success');
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    custom_alert(error, 'error');
                }
            });
        });

        $('#add-staff-btn').click(function() {
            window.location.href = "{{ route('staff.create') }}";
        });
        $('.selectpicker').selectpicker('refresh'); // Solo si usas Bootstrap select
    });
</script>
