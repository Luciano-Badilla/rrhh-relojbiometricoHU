<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista de Jusitificaciones de Inasistencias') }}
        </h2>
    </x-slot>

    <x-modal-custom id="add_absenceReason_modal" title="Agregar inasistencia"
        subtitle="Esta acción agregará una inasistencia a la lista.">
        <form action="{{ route('absenceReason.add') }}" method="POST" id="add_attendance_form">
            @csrf
            <div class="px-3 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full">
                    <div class="flex flex-col">
                        <label for="description" class="block text-sm font-medium text-gray-700">Descripcion:</label>
                        <x-text-input id="description" name="description" type="text" required />
                    </div>
                    <div class="flex flex-row gap-3 justify-between">
                        <div class="flex flex-col w-full">
                            <label for="decree" class="block text-sm font-medium text-gray-700">Decreto:</label>
                            <x-select id="decree" name="decree" :options="$allCollectiveAgreements" class="-mt-2 w-full"
                                placeholder="Seleccionar un decreto">
                            </x-select>
                        </div>
                        <div class="flex flex-col w-full">
                            <label for="article" class="block text-sm font-medium text-gray-700">Articulo:</label>
                            <x-text-input id="article" name="article" type="text" />
                        </div>
                    </div>
                    <div class="flex flex-row gap-3 justify-between">
                        <div class="flex flex-col w-full">
                            <label for="subsection" class="block text-sm font-medium text-gray-700">Inciso:</label>
                            <x-text-input id="subsection" name="subsection" type="text" />
                        </div>
                        <div class="flex flex-col w-full">
                            <label for="item" class="block text-sm font-medium text-gray-700">Punto:</label>
                            <x-text-input id="item" name="item" type="text" />
                        </div>
                    </div>

                    <div class="flex flex-row gap-3 justify-between">
                        <div class="flex flex-col w-full">
                            <label for="year" class="block text-sm font-medium text-gray-700">Año:</label>
                            <x-text-input id="year" name="year" type="number" />
                        </div>
                        <div class="flex flex-col w-full ml-3">
                            <label for="enjoyment" class="block text-sm font-medium text-gray-700">Goce:</label>
                            <x-text-input id="enjoyment" name="enjoyment" type="hidden" class="mt-2 ml-2"
                                value="0" />
                            <x-text-input id="enjoyment" name="enjoyment" type="checkbox" class="mt-2 ml-2"
                                value="1" />
                        </div>
                        <div class="flex flex-col w-full">
                            <label for="continuous" class="block text-sm font-medium text-gray-700">Corrido:</label>
                            <x-text-input id="continuous" name="continuous" type="hidden" class="mt-2 ml-2"
                                value="0" />
                            <x-text-input id="continuous" name="continuous" type="checkbox" class="mt-2 ml-2"
                                value="1" />
                        </div>
                        <div class="flex flex-col w-full">
                            <label for="businessDay" class="block text-sm font-medium text-gray-700">Habil:</label>
                            <x-text-input id="businessDay" name="businessDay" type="hidden" class="mt-2 ml-2"
                                value="0" />
                            <x-text-input id="businessDay" name="businessDay" type="checkbox" class="mt-2 ml-2"
                                value="1" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end px-3 mt-3">
                <button type="submit" class="btn btn-success rounded-xl">Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>

    <x-modal-custom id="edit_absenceReason_modal" title="Editar inasistencia"
        subtitle="Esta acción editara una inasistencia a la lista.">
        <form action="{{ route('absenceReason.edit') }}" method="POST" id="edit_attendance_form">
            @csrf
            <div class="px-3 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full">
                    <x-text-input id='absenceReason' name="id" type="hidden" />
                    <div class="flex flex-col">
                        <label for="description" class="block text-sm font-medium text-gray-700">Descripcion:</label>
                        <x-text-input id="description" name="description" type="text" required />
                    </div>
                    <div class="flex flex-row gap-3 justify-between">
                        <div class="flex flex-col w-full">
                            <label for="decree" class="block text-sm font-medium text-gray-700">Decreto:</label>
                            <x-select id="decree" name="decree" :options="$allCollectiveAgreements" class="-mt-2 w-full"
                                placeholder="Seleccionar un decreto">
                            </x-select>
                        </div>
                        <div class="flex flex-col w-full">
                            <label for="article" class="block text-sm font-medium text-gray-700">Articulo:</label>
                            <x-text-input id="article" name="article" type="text" />
                        </div>
                    </div>
                    <div class="flex flex-row gap-3 justify-between">
                        <div class="flex flex-col w-full">
                            <label for="subsection" class="block text-sm font-medium text-gray-700">Inciso:</label>
                            <x-text-input id="subsection" name="subsection" type="text" />
                        </div>
                        <div class="flex flex-col w-full">
                            <label for="item" class="block text-sm font-medium text-gray-700">Punto:</label>
                            <x-text-input id="item" name="item" type="text" />
                        </div>
                    </div>

                    <div class="flex flex-row gap-3 justify-between">
                        <div class="flex flex-col w-full">
                            <label for="year" class="block text-sm font-medium text-gray-700">Año:</label>
                            <x-text-input id="year" name="year" type="number" />
                        </div>
                        <div class="flex flex-col w-full ml-3">
                            <label for="enjoyment" class="block text-sm font-medium text-gray-700">Goce:</label>
                            <x-text-input id="enjoyment" name="enjoyment" type="hidden" value="0" />
                            <x-text-input id="enjoyment" name="enjoyment" type="checkbox" class="mt-2 ml-2"
                                value="1" />

                        </div>
                        <div class="flex flex-col w-full">
                            <label for="continuous" class="block text-sm font-medium text-gray-700">Corrido:</label>
                            <x-text-input id="continuous" name="continuous" type="hidden" value="0" />
                            <x-text-input id="continuous" name="continuous" type="checkbox" class="mt-2 ml-2"
                                value="1" />

                        </div>
                        <div class="flex flex-col w-full">
                            <label for="businessDay" class="block text-sm font-medium text-gray-700">Habil:</label>
                            <x-text-input id="businessDay" name="businessDay" type="hidden" value="0" />
                            <x-text-input id="businessDay" name="businessDay" type="checkbox" class="mt-2 ml-2"
                                value="1" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end px-3 mt-3">
                <button type="submit" class="btn btn-success rounded-xl">Editar
                </button>
            </div>
        </form>
    </x-modal-custom>

    <div class="flex items-center justify-center py-6">
        <div class="bg-white rounded-xl w-full lg:w-3/4">
            @if (session('success'))
                <div class="alert-success rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert-danger rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('error') }}
                </div>
            @endif
            <div class="flex gap-3 p-3">
                <!-- Campo de búsqueda -->
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <x-text-input id="table-search" type="text" placeholder="Buscar..." class="ps-10"
                        autofocus />

                </div>
                <x-button :button="[
                    'id' => 'add-btn',
                    'type' => 'button',
                    'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                    'icon' => '<i class=\'fa-solid fa-plus\'></i>',
                    'tooltip' => true,
                    'tooltip_text' => 'Agregar inasistencia',
                    'modal_id' => 'add_absenceReason_modal',
                ]" />
            </div>

            <div class="m-3">
                <!-- Tabla -->
                <x-table id="absenceReasons-list" :headers="['Descripcion', 'Decreto', 'Articulo', 'Inciso', 'Punto', 'Año', 'Goce', 'Corrido', 'Habil']" :fields="[
                    'name',
                    'decree',
                    'article',
                    'subsection',
                    'item',
                    'year',
                    'enjoyment',
                    'continuous',
                    'businessDay',
                ]" :data="$absenceReasons"
                    :buttons="[
                        [
                            'id' => 'edit_btn',
                            'classes' => 'btn btn-dark rounded-xl custom-tooltip edit_btn',
                            'icon' => '<i class=\'fas fa-pen\'></i>',
                            'tooltip' => true,
                            'tooltip_text' => 'Editar',
                            'modal_id' => 'edit_absenceReason_modal',
                            'data-name' => true,
                            'data-decree' => true,
                            'data-article' => true,
                            'data-subsection' => true,
                            'data-item' => true,
                            'data-year' => true,
                            'data-enjoyment' => true,
                            'data-continuous' => true,
                            'data-businessDay' => true,
                        ],
                    ]" />
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById("table-search");
        const table = document.getElementById("absenceReasons-list");
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
    $(document).ready(function() {

        $(".edit_btn").on("click", function() {
            const btn = $(this);
            let modal = $("#edit_absenceReason_modal");
            if (!modal.length) return;

            modal.find("#absenceReason").val($(this).data("id"));
            modal.find("#description").val($(this).data("name") || "-");

            const opcion = modal.find("#decree").find("option").filter(function() {
                return $(this).text().trim().toLowerCase() === btn.data("decree").trim()
                    .toLowerCase();
            }).first();

            if (opcion.length) {
                modal.find("#decree").val(opcion.val() || "-"); // seleccionás y activás eventos si hay
            }

            modal.find("#article").val($(this).data("article") || "-");
            modal.find("#subsection").val($(this).data("subsection") || "-");
            modal.find("#item").val($(this).data("item") || "-");
            modal.find("#year").val($(this).data("year") || "-");

            let enjoyment = $(this).data("enjoyment") == "Si" ? true : false;
            let continuous = $(this).data("continuous") == "Si" ? true : false;
            let businessDay = $(this).data("businessday") == "Si" ? true : false;

            modal.find("#enjoyment").prop("checked", enjoyment);
            modal.find("#continuous").prop("checked", continuous);
            modal.find("#businessDay").prop("checked", businessDay);
        });
    });
</script>
