<?php
/**
 * Helper para generar PDFs con DomPDF
 */

class PDFHelper {
    
    /**
     * Genera un PDF a partir de contenido HTML
     * @param string $html Contenido HTML
     * @param string $filename Nombre del archivo (sin extensión)
     * @param bool|string $orientation Si es bool, es $download; si es string, es orientación
     * @param string $size Tamaño: 'letter', 'a4', etc.
     * @param bool $download Si true descarga, si false muestra en navegador
     */
    public static function generarPDF(
        string $html, 
        string $filename = 'documento', 
        $orientation = 'portrait',
        string $size = 'letter',
        bool $download = true
    ): void {
        // Compatibilidad: si $orientation es bool, reordenar parámetros
        if (is_bool($orientation)) {
            $download = $orientation;
            $orientation = 'portrait';
        }
        // Registrar autoloader simple para DomPDF
        spl_autoload_register(function($class) {
            // Manejar clases del namespace Dompdf\
            if (strpos($class, 'Dompdf\\') === 0) {
                // Caso especial: Dompdf\Cpdf debe cargar desde lib/Cpdf.php
                if ($class === 'Dompdf\\Cpdf') {
                    $file = __DIR__ . '/../libs/dompdf/dompdf/lib/Cpdf.php';
                    if (file_exists($file)) {
                        require_once $file;
                        // Crear alias para que Dompdf\Cpdf apunte a Cpdf
                        if (!class_exists('Dompdf\\Cpdf', false) && class_exists('Cpdf', false)) {
                            class_alias('Cpdf', 'Dompdf\\Cpdf');
                        }
                        return true;
                    }
                }
                
                // Resto de clases Dompdf\* van a src/
                $classPath = str_replace('Dompdf\\', '', $class);
                $classPath = str_replace('\\', '/', $classPath);
                $file = __DIR__ . '/../libs/dompdf/dompdf/src/' . $classPath . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            
            // Cargar Cpdf sin namespace (por si acaso)
            if ($class === 'Cpdf') {
                $file = __DIR__ . '/../libs/dompdf/dompdf/lib/Cpdf.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            
            return false;
        });
        
        // Cargar Cpdf anticipadamente para evitar problemas de namespace
        $cpdfFile = __DIR__ . '/../libs/dompdf/dompdf/lib/Cpdf.php';
        if (file_exists($cpdfFile) && !class_exists('Cpdf', false)) {
            require_once $cpdfFile;
        }
        
        // Crear alias si es necesario
        if (class_exists('Cpdf', false) && !class_exists('Dompdf\\Cpdf', false)) {
            class_alias('Cpdf', 'Dompdf\\Cpdf');
        }
        
        // Crear instancia de DomPDF con opciones
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false); // Desactivar recursos remotos
        $options->set('isHtml5ParserEnabled', false); // CRÍTICO: Desactivar HTML5 parser
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', false);
        $options->set('chroot', realpath(__DIR__ . '/../public'));
        $options->set('fontDir', realpath(__DIR__ . '/../libs/dompdf/dompdf/lib/fonts'));
        $options->set('fontCache', realpath(__DIR__ . '/../libs/dompdf/dompdf/lib/fonts'));
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('isPhpEnabled', false); // Desactivar evaluación de PHP
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Cargar HTML de forma simple
        $dompdf->loadHtml($html);
        
        // Configurar papel
        $dompdf->setPaper($size, $orientation);
        
        // Renderizar
        $dompdf->render();
        
        // Salida
        $dompdf->stream($filename . '.pdf', [
            'Attachment' => $download ? 1 : 0
        ]);
    }
    
    /**
     * Genera PDF de ticket de pago
     */
    public static function generarTicketPDF(array $ticket, ?array $pago = null): void {
        $html = self::generarHTMLTicket($ticket, $pago);
        self::generarPDF($html, 'ticket_' . $ticket['id'], 'portrait', 'letter', true);
    }
    
    /**
     * Genera el HTML del ticket para PDF
     */
    private static function generarHTMLTicket(array $ticket, ?array $pago = null): string {
        $base = defined('RUTA') ? RUTA : '';
        
        // Determinar ruta del QR
        $qrPath = '';
        if (!empty($ticket['qr_code'])) {
            $qr = trim($ticket['qr_code']);
            $qr = ltrim($qr, '/');
            $qr = preg_replace('#^public/#', '', $qr);
            if ($qr && !str_starts_with($qr, 'qrcodes/')) {
                if (!str_contains($qr, '/')) $qr = 'qrcodes/' . $qr;
            }
            $qrPath = __DIR__ . '/../public/' . $qr;
        }
        
        // Si no existe, intentar construir desde id_pago
        if (!file_exists($qrPath) && !empty($ticket['id_pago'])) {
            $qrPath = __DIR__ . '/../public/qrcodes/ticket_' . (int)$ticket['id_pago'] . '.png';
        }
        
        // Convertir imagen a base64 para incrustarla en el PDF
        $qrBase64 = '';
        if (file_exists($qrPath)) {
            $qrData = file_get_contents($qrPath);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);
        }
        
        $estadoBadgeClass = ($pago && $pago['estado_pago'] === 'pagado') ? 'status-pagado' : 'status-pendiente';
        $estadoTexto = ($pago && $pago['estado_pago'] === 'pagado') ? 'PAGADO' : 'PENDIENTE';
        
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        @page { margin: 15mm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #222;
        }
        .ticket-wrapper {
            max-width: 400px;
            margin: 0 auto;
        }
        .ticket {
            background: #fff;
            border: 2px solid #222;
            border-radius: 10px;
            padding: 20px 25px;
            position: relative;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed #888;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .ticket-brand {
            font-weight: 700;
            font-size: 16pt;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .subtitle {
            font-size: 8pt;
            color: #666;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .ticket-number {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 12pt;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 8px;
            border: 1px solid #ccc;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
            margin-bottom: 8px;
            font-weight: 600;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .kv-row {
            display: table;
            width: 100%;
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 6px;
            padding: 6px 10px;
            font-size: 10pt;
        }
        .kv-label {
            display: table-cell;
            color: #555;
            font-weight: 500;
            width: 40%;
        }
        .kv-value {
            display: table-cell;
            text-align: right;
            font-weight: 600;
            width: 60%;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .status-pagado {
            background: #198754;
            color: #fff;
        }
        .status-pendiente {
            background: #ffc107;
            color: #222;
        }
        .qr-section {
            text-align: center;
            margin: 20px 0 15px;
            padding-top: 15px;
            border-top: 2px dashed #888;
        }
        .qr-label {
            font-size: 8pt;
            letter-spacing: 1.5px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .qr-image {
            border: 1px solid #ddd;
            padding: 8px;
            background: #fff;
            border-radius: 6px;
        }
        .qr-code-text {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8pt;
            color: #666;
            margin-top: 8px;
        }
        .footer {
            text-align: center;
            font-size: 7pt;
            color: #777;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="ticket-wrapper">
        <div class="ticket">
            <div class="ticket-header">
                <div class="ticket-brand">Plataforma Psicología</div>
                <div class="subtitle">Comprobante de Pago</div>
                <div class="ticket-number">TICKET #' . (int)$ticket['id'] . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Identificación</div>
                <div class="kv-row">
                    <span class="kv-label">Código:</span>
                    <span class="kv-value">' . htmlspecialchars($ticket['codigo']) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Número:</span>
                    <span class="kv-value">' . htmlspecialchars($ticket['numero_ticket']) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Fecha Emisión:</span>
                    <span class="kv-value">' . htmlspecialchars($ticket['fecha_emision']) . '</span>
                </div>
            </div>';
        
        if ($pago) {
            $html .= '
            <div class="section">
                <div class="section-title">Información del Pago</div>
                <div class="kv-row">
                    <span class="kv-label">ID Pago:</span>
                    <span class="kv-value">#' . (int)$pago['id'] . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Monto Base:</span>
                    <span class="kv-value">$' . number_format((float)$pago['monto_base'], 2) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Monto Total:</span>
                    <span class="kv-value">$' . number_format((float)$pago['monto_total'], 2) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Estado:</span>
                    <span class="kv-value"><span class="status-badge ' . $estadoBadgeClass . '">' . $estadoTexto . '</span></span>
                </div>
            </div>';
        }
        
        if ($qrBase64) {
            $html .= '
            <div class="qr-section">
                <div class="qr-label">Código de Autenticación</div>
                <img src="' . $qrBase64 . '" width="160" height="160" class="qr-image" alt="QR Code">
                <div class="qr-code-text">PAGO:' . htmlspecialchars($ticket['id_pago'] ?? '') . '</div>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                Este ticket es válido solo dentro de la plataforma.<br>
                Conserve este comprobante para su registro.<br>
                Generado el ' . date('d/m/Y H:i') . '
            </div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
