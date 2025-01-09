<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class reports extends Controller
{
    public function individual_hours(Request $request)
    {
        $data = json_decode($request->query('data'), true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar encabezados
        $sheet->setCellValue('A1', "Nombre");
        $sheet->setCellValue('B1', 'Horas');
        $sheet->setCellValue('C1', 'Fecha de Registro');

        // Agregar datos
        $sheet->setCellValue('A2', 'Juan Pérez');
        $sheet->setCellValue('B2', 'juan.perez@example.com');
        $sheet->setCellValue('C2', '2025-01-01');

        $sheet->setCellValue('A3', 'Ana López');
        $sheet->setCellValue('B3', 'ana.lopez@example.com');
        $sheet->setCellValue('C3', '2025-01-02');

        // Descargar el archivo
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="usuarios.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
