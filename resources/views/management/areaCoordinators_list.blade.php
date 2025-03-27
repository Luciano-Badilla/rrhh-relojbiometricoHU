<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista de Areas y Coordinadores') }}
        </h2>
    </x-slot>

    <x-modal-custom id="add_areaCoordinators_modal" title="Agregar Área"
        subtitle="Esta acción agregará una área a la lista.">
        <form action="{{ route('areaCoordinators.add') }}" method="POST" id="add_areaCoordinators_form">
            @csrf
            <div class="px-3 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full">
                    <div class="flex flex-col">
                        <label for="area" class="block text-sm font-medium text-gray-700">Área:</label>
                        <x-text-input id="area" name="area" type="text" class="h-9" required />
                    </div>
                    <div class="flex flex-col">
                        <label for="coordinator" class="block text-sm font-medium text-gray-700">Coordinador:</label>
                        <select id="coordinator_select" name="coordinator" required
                            class="selectpicker border-gray-300 w-full rounded-xl shadow-sm" data-live-search="true"
                            data-width="100%">
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name_surname }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
            <div class="flex justify-end px-3 mt-3">
                <button type="submit" class="btn btn-success rounded-xl" >Agregar
                </button>
            </div>
        </form>
    </x-modal-custom>

    <x-modal-custom id="edit_areaCoordinators_modal" title="Editar área"
        subtitle="Esta acción editara un área a la lista.">
        <form action="{{ route('areaCoordinators.edit') }}" method="POST" id="edit_areaCoordinators_form">
            @csrf
            <div class="px-3 flex flex-col gap-3 justify-center items-center">
                <div class="flex flex-col gap-3 w-full">
                    <div class="flex flex-col">
                        <label for="area" class="block text-sm font-medium text-gray-700">Área:</label>
                        <x-text-input id="area" name="area" type="text" class="h-9" required />
                        <x-text-input id="area_id" name="area_id" type="hidden" class="h-9" />
                        <x-text-input id="coordinator_id" name="coordinator_id" type="hidden" class="h-9" />

                    </div>
                    <div class="flex flex-col">
                        <label for="coordinator" class="block text-sm font-medium text-gray-700">Coordinador:</label>
                        <select id="coordinator_select" name="coordinator" required
                            class="selectpicker border-gray-300 w-full rounded-xl shadow-sm" data-live-search="true"
                            data-width="100%">
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name_surname }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
            <div class="flex justify-end px-3 mt-3">
                <button type="submit" class="btn btn-success rounded-xl" >Editar
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
            @if (session('error') || $errors->any())
                <div class="alert-danger rounded-t-xl p-0.5 text-center mb-1">
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif
            <div class="flex gap-3 p-3">
                <!-- Campo de búsqueda -->
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <x-text-input id="table-search" type="text" placeholder="Buscar..." class="ps-10" autofocus />

                </div>
                <x-button :button="[
                    'id' => 'add-btn',
                    'type' => 'button',
                    'classes' => 'btn btn-dark rounded-xl custom-tooltip h-10',
                    'icon' => '<i class=\'fa-solid fa-plus\'></i>',
                    'tooltip' => true,
                    'tooltip_text' => 'Agregar área',
                    'modal_id' => 'add_areaCoordinators_modal',
                ]" />
            </div>

            <div class="m-3">
                <!-- Tabla -->
                <x-table id="areaCoordinators-list" :headers="['Area', 'Coordinador']" :fields="['name', 'coordinator']" :data="$areas"
                    :buttons="[
                        [
                            'id' => 'edit_btn',
                            'classes' => 'btn btn-dark rounded-xl custom-tooltip edit_btn',
                            'icon' => '<i class=\'fas fa-pen\'></i>',
                            'tooltip' => true,
                            'tooltip_text' => 'Editar',
                            'modal_id' => 'edit_areaCoordinators_modal',
                            'data-name' => true,
                            'data-coordinator_id' => true,
                        ],
                    ]" />
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById("table-search");
        const table = document.getElementById("areaCoordinators-list");
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
            let modal = $("#edit_areaCoordinators_modal");
            if (!modal.length) return;

            modal.find("#area_id").val($(this).data("id"));
            modal.find("#coordinator_id").val($(this).data("coordinator_id"));
            modal.find("#area").val($(this).data("name"));
            modal.find("#coordinator_select").val($(this).data("coordinator_id")).trigger("change");
        });

    });
</script>
