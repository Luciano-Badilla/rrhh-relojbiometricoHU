<x-app-layout>
    <div class="container text-center mt-5">
        <h1 class="text-danger">403 - Acceso Denegado</h1>
        <p>No tienes permiso para acceder a esta página.</p>

        @php
            $staffId = session('staff_id');
        @endphp

        @if($staffId)
            <a href="{{ route('staff.administration_panel', ['id' => $staffId]) }}" class="btn btn-primary">Volver</a>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Volver</a>
        @endif
    </div>
</x-app-layout>
