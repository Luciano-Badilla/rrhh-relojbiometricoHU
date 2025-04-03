@php
    $tardies = json_decode($tardies, true);
    $staffs = json_decode($staffs, true);
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Tardanzas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
            /* 🔹 Hace que las columnas tengan un ancho fijo */
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
            word-wrap: break-word;
            /* 🔹 Evita que el texto desborde */
            text-align: center;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 5%;
        }

        /* # */
        th:nth-child(2),
        td:nth-child(2) {
            width: 15%;
        }

        /* Día */
        th:nth-child(3),
        td:nth-child(3) {
            width: 15%;
        }

        /* Fecha */
        th:nth-child(4),
        td:nth-child(4) {
            width: 25%;
        }

        /* Entrada */
        th:nth-child(5),
        td:nth-child(5) {
            width: 20%;
        }

        /* Salida */
        th:nth-child(6),
        td:nth-child(6) {
            width: 25%;
        }
        th:nth-child(7),
        td:nth-child(7) {
            width: 30%;
        }

        /* Horas cumplidas */

        th {
            background-color: #ddd;
        }

        .header {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .sub-header {
            font-size: 14px;
            font-weight: bold;
        }

        .table-css {
            margin-top: -10px;
        }
    </style>
</head>

<body>

    <div class="header">Universidad Nacional de Cuyo - Hospital Universitario</div>
    <div class="header">Reporte de tardanzas</div>
    <p><strong>Area:</strong> {{ $area_selected }}</p>
    <p><strong>Fecha:</strong> {{ $dates }}</p>
    <p><strong>Tolerancia:</strong> {{ $tolerance }} minutos</p>

    @isset($tardies)
        @foreach ($staffs as $staff)
            @php
                $staffTardies = array_filter($tardies, function ($ardies) use ($staff) {
                    return $ardies['file_number'] === $staff['file_number'];
                });
            @endphp

            @if (count($staffTardies) > 0)
                <p class="sub-header">#{{ $staff['file_number'] . ' ' . $staff['name_surname'] }} -
                    {{ count($staffTardies) }}
                    Tardanza{{ count($staffTardies) > 1 ? 's' : '' }}</p>
                <table class="table-css">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Día</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Horas cumplidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffTardies as $tardy)
                            <tr>
                                <td>{{ $tardy['counter'] }}</td>
                                <td>{{ $tardy['day'] }}</td>
                                <td>{{ $tardy['date_formated'] }}</td>
                                <td>{{ $tardy['asssignedSchedule'] ?? '-' }}</td>
                                <td>{{ $tardy['entryTime'] ?? '-' }}</td>
                                <td>{{ $tardy['departureTime'] ?? '-' }}</td>
                                <td>{{ $tardy['hoursCompleted'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endisset
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                if ($PAGE_COUNT > 1) {
                    $font = $fontMetrics->get_font("Arial, sans-serif", "normal");
                    $size = 12;
                    $pageText = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
                    
                    $x = ($pdf->get_width() - $fontMetrics->get_text_width($pageText, $font, $size)) / 2;
                    $y = $pdf->get_height() - 30;
    
                    $pdf->text($x, $y, $pageText, $font, $size);
                }
            ');
        }
    </script>




</body>

</html>
