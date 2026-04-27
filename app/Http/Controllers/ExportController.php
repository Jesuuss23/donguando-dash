<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function exportContacts()
    {
        $contacts = Contact::with('tags')->orderBy('name', 'asc')->get();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Estilos para el encabezado
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7F1D1D'] // Rojo oscuro Don Guando
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        // Encabezados
        $headers = ['ID', 'Número de WhatsApp', 'Nombre', 'Etiquetas (Tags)', 'Estado IA', 'Mensajes IA (24h)', 'Chat Anclado', 'Última actualización'];
        
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Datos
        $row = 2;
        foreach ($contacts as $contact) {
            // Obtener tags como string
            $tagsString = $contact->tags->pluck('name')->implode(', ');
            
            // Estado IA
            $estadoIA = $contact->is_intervened == 0 ? 'AUTO (IA Activa)' : 'MANUAL (IA Off)';
            
            // Chat anclado
            $anclado = $contact->is_pinned ? '✅ Sí' : '❌ No';
            
            $sheet->setCellValue('A' . $row, $contact->id);
            $sheet->setCellValue('B' . $row, $contact->whatsapp_id);
            $sheet->setCellValue('C' . $row, $contact->name);
            $sheet->setCellValue('D' . $row, $tagsString);
            $sheet->setCellValue('E' . $row, $estadoIA);
            $sheet->setCellValue('F' . $row, $contact->ia_messages_count ?? 0);
            $sheet->setCellValue('G' . $row, $anclado);
            $sheet->setCellValue('H' . $row, $contact->updated_at ?? $contact->created_at);
            
            // Alternar colores de filas
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
            
            $row++;
        }
        
        // Ajustar anchos de columnas
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Cabeceras para descarga
        $fileName = 'contactos_donguando_' . date('Y-m-d_H-i-s') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    // Exportar solo contactos con filtro (opcional)
    public function exportFiltered(Request $request)
    {
        $query = Contact::with('tags');
        
        // Filtrar por estado IA
        if ($request->has('ia_status') && $request->ia_status !== '') {
            $query->where('is_intervened', $request->ia_status == 'auto' ? 0 : 1);
        }
        
        // Filtrar por tag
        if ($request->has('tag_id') && $request->tag_id) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tag_id', $request->tag_id);
            });
        }
        
        $contacts = $query->orderBy('name', 'asc')->get();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Mismo código de exportación que arriba...
        // (copiar el mismo código de exportación)
        
        $fileName = 'contactos_filtrados_donguando_' . date('Y-m-d_H-i-s') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}