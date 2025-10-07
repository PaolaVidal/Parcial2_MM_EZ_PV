<?php
/**
 * Helper para generar archivos Excel (CSV con formato mejorado)
 */
class ExcelHelper {
    
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
     * Exporta múltiples hojas (secciones) a un CSV
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
}
