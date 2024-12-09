
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Datos del Reloj MB360</h1>

    @if(session('success'))
        <div class="bg-green-500 text-white p-4 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="table-auto w-full border-collapse border border-gray-200 shadow-lg">
        <thead class="bg-gray-100">
            <tr>
                <th class="border border-gray-300 px-4 py-2 text-left">UID</th>
                <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Estado</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Fecha/Hora</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Tipo</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $dato)
                <tr class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ $dato['uid'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $dato['id'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        @if($dato['state'] == 1)
                            <span class="text-green-500">Activo</span>
                        @else
                            <span class="text-red-500">Inactivo</span>
                        @endif
                    </td>
                    <td class="border border-gray-300 px-4 py-2">{{ $dato['timestamp'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        @if($dato['type'] == 1)
                            Entrada
                        @else
                            Salida
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                        No se encontraron datos.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
