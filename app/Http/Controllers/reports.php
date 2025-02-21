<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class reports extends Controller
{
    public function individual_hours(Request $request)
    {
        $data = json_decode($request->query('data'), true);
        Carbon::setLocale('es');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $dateReport = Carbon::create($data['year'], $data['month'], 1);

        // Lógica para determinar el día
        if ($dateReport->isPast() && !$dateReport->isCurrentMonth()) {
            // Si es un mes anterior al actual
            $dateReport = $dateReport->endOfMonth(); // Último día del mes
            $dateReportText = ' ';
        } else {
            // Si es el mes actual
            $dateReport = Carbon::now(); // Día actual
            $dateReportText = 'hasta ' . $dateReport->format('d/m/y');
        }

        // Configurar encabezados
        $sheet->setCellValue('A1', "Hopital Universitario / Universidad Nacional de Cuyo");
        $sheet->setCellValue('A3', 'Control Horario ' . ucfirst($dateReport->translatedFormat('F'))
            . $dateReportText);
        $sheet->setCellValue('A5', 'Legajo');
        $sheet->setCellValue('B5', 'Apellido y Nombre');
        $sheet->setCellValue('C5', 'Oficina');
        $sheet->setCellValue('D5', 'Dias complet.');
        $sheet->setCellValue('E5', 'Horas cumpl.');
        $sheet->setCellValue('G5', 'Prom. Mes');
        $sheet->setCellValue('F5', 'Horas extras');

        // Datos
        $sheet->setCellValue('A6', $data['staff']['file_number']);
        $sheet->setCellValue('B6', $data['staff']['name_surname']);
        $sheet->setCellValue('C6', '');
        $sheet->setCellValue('D6', $data['days']);
        $sheet->setCellValue('E6', $data['totalHours']);
        $sheet->setCellValue('G6', $data['hoursAverage']);
        $sheet->setCellValue('F6', $data['totalExtraHours']);


        // Ajustar automáticamente el ancho de las columnas
        foreach (range('B', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Descargar el archivo
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Horarios - 0.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
