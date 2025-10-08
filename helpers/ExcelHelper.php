<?php
/**
 * Helper para generar archivos Excel (CSV + XLSX)
 */
class ExcelHelper {
    
    /**
     * Exporta datos a un archivo CSV compatible con Excel
     * @param array $data Datos a exportar
     * @param string $filename Nombre del archivo sin extensión
     * @param array $headers Encabezados de las columnas
     */
    public static function exportarCSV(array $data, string $filename, array $headers = []): void {
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($headers)) {
            fputcsv($output, $headers, ';');
        }
        
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
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $firstSheet = true;
        foreach ($sheets as $sheetName => $sheetData) {
            if (!$firstSheet) {
                fputcsv($output, [], ';');
                fputcsv($output, [], ';');
            }
            $firstSheet = false;
            
            fputcsv($output, [$sheetName], ';');
            fputcsv($output, [], ';');
            
            if (!empty($sheetData['headers'])) {
                fputcsv($output, $sheetData['headers'], ';');
            }
            
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
     * Exporta a Excel XLSX real usando XML directo (sin dependencias de Composer)
     */
    public static function exportarMultiplesHojas(array $sheets, string $filename, string $title = ''): void {
        // Generar Excel XLSX manualmente usando formato XML
        $tmpDir = sys_get_temp_dir() . '/excel_' . uniqid();
        mkdir($tmpDir);
        mkdir($tmpDir . '/_rels');
        mkdir($tmpDir . '/docProps');
        mkdir($tmpDir . '/xl');
        mkdir($tmpDir . '/xl/_rels');
        mkdir($tmpDir . '/xl/worksheets');
        
        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        
        $sheetIndex = 1;
        foreach ($sheets as $sheetName => $sheetData) {
            $contentTypes .= '
    <Override PartName="/xl/worksheets/sheet' . $sheetIndex . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheetIndex++;
        }
        $contentTypes .= '
</Types>';
        file_put_contents($tmpDir . '/[Content_Types].xml', $contentTypes);
        
        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';
        file_put_contents($tmpDir . '/_rels/.rels', $rels);
        
        // docProps/core.xml
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:creator>Plataforma Psicología</dc:creator>
    <dc:title>' . htmlspecialchars($title ?: 'Estadísticas') . '</dc:title>
    <dcterms:created xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>
</cp:coreProperties>';
        file_put_contents($tmpDir . '/docProps/core.xml', $core);
        
        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>';
        $sheetIndex = 1;
        foreach ($sheets as $sheetName => $sheetData) {
            $safeName = self::sanitizeSheetName($sheetName);
            $workbook .= '
        <sheet name="' . htmlspecialchars($safeName) . '" sheetId="' . $sheetIndex . '" r:id="rId' . $sheetIndex . '"/>';
            $sheetIndex++;
        }
        $workbook .= '
    </sheets>
</workbook>';
        file_put_contents($tmpDir . '/xl/workbook.xml', $workbook);
        
        // xl/_rels/workbook.xml.rels
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $sheetIndex = 1;
        foreach ($sheets as $sheetName => $sheetData) {
            $workbookRels .= '
    <Relationship Id="rId' . $sheetIndex . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetIndex . '.xml"/>';
            $sheetIndex++;
        }
        $workbookRels .= '
    <Relationship Id="rId' . $sheetIndex . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        file_put_contents($tmpDir . '/xl/_rels/workbook.xml.rels', $workbookRels);
        
        // xl/styles.xml (con colores)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="3">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="5">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF2C3E50"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF3498DB"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFF8F9FA"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/></border>
        <border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/></border>
    </borders>
    <cellXfs count="5">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
        <xf numFmtId="0" fontId="2" fillId="2" borderId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>
        <xf numFmtId="0" fontId="1" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="4" borderId="1" applyFill="1" applyBorder="1"/>
    </cellXfs>
</styleSheet>';
        file_put_contents($tmpDir . '/xl/styles.xml', $styles);
        
        // Generar cada hoja
        $sheetIndex = 1;
        foreach ($sheets as $sheetName => $sheetData) {
            $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';
            
            $rowNum = 1;
            
            // Título
            $colCount = count($sheetData['headers'] ?? [1]);
            $lastCol = self::getColumnLetter($colCount);
            $worksheet .= '
        <row r="' . $rowNum . '">
            <c r="A' . $rowNum . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars($sheetName) . '</t></is></c>
        </row>';
            $rowNum += 2; // Saltar fila
            
            // Headers
            if (!empty($sheetData['headers'])) {
                $worksheet .= '
        <row r="' . $rowNum . '">';
                $colIdx = 0;
                foreach ($sheetData['headers'] as $header) {
                    $colLetter = self::getColumnLetter($colIdx + 1);
                    $worksheet .= '
            <c r="' . $colLetter . $rowNum . '" s="2" t="inlineStr"><is><t>' . htmlspecialchars($header) . '</t></is></c>';
                    $colIdx++;
                }
                $worksheet .= '
        </row>';
                $rowNum++;
            }
            
            // Data
            if (!empty($sheetData['data'])) {
                foreach ($sheetData['data'] as $dataRow) {
                    $style = ($rowNum % 2 == 0) ? '4' : '3'; // Zebra striping
                    $worksheet .= '
        <row r="' . $rowNum . '">';
                    $colIdx = 0;
                    foreach ($dataRow as $cellValue) {
                        $colLetter = self::getColumnLetter($colIdx + 1);
                        $cleanValue = htmlspecialchars(str_replace(['$', ','], '', $cellValue));
                        if (is_numeric($cleanValue)) {
                            $worksheet .= '
            <c r="' . $colLetter . $rowNum . '" s="' . $style . '"><v>' . $cleanValue . '</v></c>';
                        } else {
                            $worksheet .= '
            <c r="' . $colLetter . $rowNum . '" s="' . $style . '" t="inlineStr"><is><t>' . htmlspecialchars($cellValue) . '</t></is></c>';
                        }
                        $colIdx++;
                    }
                    $worksheet .= '
        </row>';
                    $rowNum++;
                }
            }
            
            $worksheet .= '
    </sheetData>
</worksheet>';
            file_put_contents($tmpDir . '/xl/worksheets/sheet' . $sheetIndex . '.xml', $worksheet);
            $sheetIndex++;
        }
        
        // Crear ZIP
        $zipFile = $tmpDir . '.xlsx';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('No se pudo crear el archivo Excel');
        }
        
        // Agregar todos los archivos al ZIP
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($tmpDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
        
        // Enviar archivo
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        
        // Limpiar temporales
        unlink($zipFile);
        array_map('unlink', glob($tmpDir . '/**/*.xml'));
        array_map('unlink', glob($tmpDir . '/*/*.xml'));
        array_map('unlink', glob($tmpDir . '/*.xml'));
        rmdir($tmpDir . '/xl/worksheets');
        rmdir($tmpDir . '/xl/_rels');
        rmdir($tmpDir . '/xl');
        rmdir($tmpDir . '/docProps');
        rmdir($tmpDir . '/_rels');
        rmdir($tmpDir);
        
        exit;
    }
    
    private static function sanitizeSheetName(string $name): string {
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], '_', $name);
        return mb_substr($name, 0, 31);
    }
    
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
