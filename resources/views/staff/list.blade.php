<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista del Personal') }}
        </h2>
    </x-slot>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-2/4">
            @if (session('success'))
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('success') }}
                </div>
            @endif
            <div class="p-3">
                <!-- Campo de búsqueda -->
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <x-text-input id="table-search" type="text" placeholder="Buscar..." class="ps-10" />
                </div>
            </div>

            <!-- Tabla -->
            <x-table id="staff-list" :headers="['Legajo', 'Nombre']" :fields="['file_number', 'name_surname']" :data="$staff" :links="[
                [
                    'route' => 'staff.management',
                    'classes' => 'btn btn-dark rounded-xl custom-tooltip',
                    'icon' => '<i class=\'fas fa-user-pen\'></i>',
                    'tooltip' => true,
                    'tooltip_text' => 'Mantenimiento del personal',
                ],
                [
                    'route' => 'staff.attendance',
                    'classes' => 'btn btn-dark rounded-xl custom-tooltip',
                    'icon' => '<i class=\'fas fa-calendar-days\'></i>',
                    'tooltip' => true,
                    'tooltip_text' => 'Resumen de asistencias',
                ],
            ]" />
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById("table-search");
        const table = document.getElementById("staff-list");
        const rows = table.querySelectorAll("tbody tr");

        searchInput.addEventListener("input", () => {
            const filter = searchInput.value.toLowerCase();

            rows.forEach(row => {
                const cells = row.querySelectorAll("td");
                const rowText = Array.from(cells)
                    .map(cell => cell.textContent.toLowerCase())
                    .join(" ");

                row.style.display = rowText.includes(filter) ? "" : "none";
            });
        });
    });
</script>
