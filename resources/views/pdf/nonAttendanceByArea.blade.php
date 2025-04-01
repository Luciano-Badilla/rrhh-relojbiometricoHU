@php
    $nonAttendances = json_decode($nonAttendances, true);
    $staffs = json_decode($staffs, true);
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencia</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #ddd;
        }

        td:nth-child(3) {
            min-width: 200px;
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
    <div class="header">Reporte de ausentismo</div>
    <p><strong>Area:</strong> {{ $area_selected }}</p>
    <p><strong>Fecha:</strong> {{ $dates }}</p>

    @isset($nonAttendances)
        @foreach ($staffs as $staff)
            @php
                $staffAbsences = array_filter($nonAttendances, function ($absence) use ($staff) {
                    return $absence['file_number'] === $staff['file_number'];
                });
            @endphp

            @if (count($staffAbsences) > 0)
                <p class="sub-header">#{{ $staff['file_number'] . ' ' . $staff['name_surname'] }} -
                    {{ count($staffAbsences) }}
                    Inasistencia{{ count($staffAbsences) > 1 ? 's' : '' }}</p>
                <table class="table-css">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Fecha</th>
                            <th>Motivo/Justificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffAbsences as $absence)
                            <tr>
                                <td>{{ $absence['day'] }}</td>
                                <td>{{ $absence['date'] }}</td>
                                <td>{{ $absence['absenceReason'] ?? '-' }}</td>
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
