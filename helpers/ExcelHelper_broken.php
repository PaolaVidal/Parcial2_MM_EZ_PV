<?php    
    /**
     * Exporta datos a un archivo CSV compatible con Excel
     * @param array $data Datos a exportar
     * @param string $filename Nombre del archivo sin extensión
     * @param array $headers Encabezados de las columnas
     */
    public static function exportarCSV(array $data, string $filename, array $headers = []): void {
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Crear output stream
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para UTF-8 (para que Excel reconozca acentos)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir encabezados si se proporcionan
        if (!empty($headers)) {
            fputcsv($output, $headers, ';'); // Usar punto y coma para Excel español
        }
        
        // Escribir datos
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporta múltiples secciones a un CSV
     * @param array $sheets Array asociativo ['NombreHoja' => ['headers' => [...], 'data' => [...]]]
     * @param string $filename Nombre del archivo
     */
    public static function exportarMultiplesSecciones(array $sheets, string $filename): void {
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $firstSheet = true;
        foreach ($sheets as $sheetName => $sheetData) {
            // Separador entre secciones (excepto la primera)
            if (!$firstSheet) {
                fputcsv($output, [], ';');
                fputcsv($output, [], ';');
            }
            $firstSheet = false;
            
            // Título de la sección
            fputcsv($output, [$sheetName], ';');
            fputcsv($output, [], ';');
            
            // Encabezados
            if (!empty($sheetData['headers'])) {
                fputcsv($output, $sheetData['headers'], ';');
            }
            
            // Datos
            if (!empty($sheetData['data'])) {
                foreach ($sheetData['data'] as $row) {
                    fputcsv($output, $row, ';');
                }
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exporta múltiples hojas con formato profesional a Excel XLSX (requiere PhpSpreadsheet)
     * @param array $sheets Array asociativo ['NombreHoja' => ['headers' => [...], 'data' => [...]]]
     * @param string $filename Nombre del archivo sin extensión
     * @param string $title Título del documento
     */
    public static function exportarMultiplesHojas(array $sheets, string $filename, string $title = ''): void {
        // Verificar si PhpSpreadsheet está disponible
        $spreadsheetFile = __DIR__ . '/../libs/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
        if (!file_exists($spreadsheetFile)) {
            // Fallback a CSV si no está PhpSpreadsheet
            self::exportarMultiplesSecciones($sheets, $filename);
            return;
        }
        
        // Autoloader para PhpSpreadsheet
        spl_autoload_register(function ($class) {
            if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
                $classPath = str_replace('PhpOffice\\PhpSpreadsheet\\', '', $class);
                $classPath = str_replace('\\', '/', $classPath);
                $file = __DIR__ . '/../libs/phpspreadsheet/src/PhpSpreadsheet/' . $classPath . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            return false;
        });* Helper para generar archivos Excel (CSV mejorado + XLSX opcional)
 */
class ExcelHelper {
    
    /**
     * Exporta múltiples hojas con formato profesional a Excel
     * @param array $sheets Array asociativo ['NombreHoja' => ['headers' => [...], 'data' => [...]]]
     * @param string $filename Nombre del archivo sin extensión
     * @param string $title Título del documento
     */
    public static function exportarMultiplesHojas(array $sheets, string $filename, string $title = ''): void {
                });
        
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Metadata
        $spreadsheet->getProperties()
            ->setCreator('Plataforma Psicología')
            ->setTitle($title ?: 'Reporte de Estadísticas')
            ->setSubject('Estadísticas')
            ->setDescription('Reporte generado automáticamente');
        
        $sheetIndex = 0;
        foreach ($sheets as $sheetName => $sheetData) {
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }
            
            $sheet->setTitle(self::sanitizeSheetName($sheetName));
            
            $row = 1;
            
            // Título de la hoja
            $sheet->setCellValue('A' . $row, $sheetName);
            $sheet->mergeCells('A' . $row . ':' . self::getColumnLetter(count($sheetData['headers'] ?? [1])) . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2C3E50']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]);
            $sheet->getRowDimension($row)->setRowHeight(30);
            $row++;
            
            // Espacio
            $row++;
            
            // Encabezados
            if (!empty($sheetData['headers'])) {
                $col = 'A';
                foreach ($sheetData['headers'] as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $sheet->getStyle($col . $row)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF']
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '3498DB']
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ]
                        ]
                    ]);
                    $col++;
                }
                $row++;
            }
            
            // Datos
            if (!empty($sheetData['data'])) {
                foreach ($sheetData['data'] as $dataRow) {
                    $col = 'A';
                    foreach ($dataRow as $cellValue) {
                        $sheet->setCellValue($col . $row, $cellValue);
                        
                        // Estilo zebra striping
                        $fillColor = ($row % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
                        $sheet->getStyle($col . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $fillColor]
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => ['rgb' => 'DDDDDD']
                                ]
                            ]
                        ]);
                        
                        $col++;
                    }
                    $row++;
                }
            }
            
            // Auto-ajustar ancho de columnas
            foreach (range('A', self::getColumnLetter(count($sheetData['headers'] ?? [1]))) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            $sheetIndex++;
        }
        
        // Activar primera hoja
        $spreadsheet->setActiveSheetIndex(0);
        
        // Generar archivo
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
        } catch (\Exception $e) {
            // Si falla PhpSpreadsheet, usar CSV como fallback
            self::exportarMultiplesSecciones($sheets, $filename);
        }
    }
    
    /**
     * Sanitiza nombre de hoja para Excel (max 31 chars, sin caracteres especiales)
     */
    private static function sanitizeSheetName(string $name): string {
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], '_', $name);
        return mb_substr($name, 0, 31);
    }
    
    /**
     * Convierte número de columna a letra (1=A, 2=B, etc.)
     */
    private static function getColumnLetter(int $num): string {
        $letters = '';
        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $num = (int)(($num - $mod) / 26);
        }
        return $letters ?: 'A';
    }
}
