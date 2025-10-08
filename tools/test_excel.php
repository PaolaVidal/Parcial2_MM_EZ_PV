<?php
/**
 * Script de prueba para PhpSpreadsheet
 * Ejecutar desde navegador: http://localhost/.../tools/test_excel.php
 */

// Autoloader para PhpSpreadsheet
$basePath = __DIR__ . '/../libs/phpspreadsheet/src/PhpSpreadsheet/';

spl_autoload_register(function ($class) use ($basePath) {
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        $classPath = str_replace(['PhpOffice\\PhpSpreadsheet\\', '\\'], ['', '/'], $class);
        $file = $basePath . $classPath . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

try {
    echo "<h2>Probando PhpSpreadsheet...</h2>";
    
    // Crear spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Agregar datos de prueba
    $sheet->setCellValue('A1', 'Nombre');
    $sheet->setCellValue('B1', 'Valor');
    $sheet->setCellValue('A2', 'Prueba 1');
    $sheet->setCellValue('B2', '100');
    
    // Aplicar estilo
    $sheet->getStyle('A1:B1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '3498DB']
        ]
    ]);
    
    echo "<p style='color: green; font-weight: bold;'>✓ PhpSpreadsheet funciona correctamente!</p>";
    echo "<p>Extensiones PHP detectadas:</p>";
    echo "<ul>";
    echo "<li>zip: " . (extension_loaded('zip') ? '✓ Sí' : '✗ No') . "</li>";
    echo "<li>xml: " . (extension_loaded('xml') ? '✓ Sí' : '✗ No') . "</li>";
    echo "<li>gd: " . (extension_loaded('gd') ? '✓ Sí' : '✗ No') . "</li>";
    echo "<li>SimpleXML: " . (extension_loaded('SimpleXML') ? '✓ Sí' : '✗ No') . "</li>";
    echo "</ul>";
    
    echo "<p><a href='?download=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Descargar Excel de Prueba</a></p>";
    
    // Si se solicita descarga
    if (isset($_GET['download'])) {
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="prueba_excel.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
} catch (\Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
