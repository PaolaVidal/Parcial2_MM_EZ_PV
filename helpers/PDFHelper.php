<?php
/**
 * Helper para generar PDFs con DomPDF
 */

class PDFHelper
{

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
        spl_autoload_register(function ($class) {
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

        // CRÍTICO: dompdf requiere la clase Masterminds\HTML5 que no tenemos
        // SOLUCIÓN: Crear una clase stub con todos los métodos que dompdf necesita
        if (!class_exists('Masterminds\\HTML5')) {
            eval ('
                namespace Masterminds {
                    class HTML5 {
                        private $dom;
                        
                        public function __construct($options = array()) {
                            $this->dom = new \\DOMDocument();
                        }
                        
                        public function loadHTML($html) { 
                            $dom = new \\DOMDocument();
                            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                            $this->dom = $dom;
                            return $dom;
                        }
                        
                        public function save($dom) {
                            return $dom->saveHTML();
                        }
                        
                        public function saveHTML($dom = null) {
                            if ($dom === null) {
                                $dom = $this->dom;
                            }
                            return $dom->saveHTML();
                        }
                    }
                }
            ');
        }

        // Limpiar HTML antes de procesar
        // Eliminar BOM
        $html = str_replace("\xEF\xBB\xBF", '', $html);

        // Ensure no stray output remains which would make headers_sent() true
        while (ob_get_level()) {
            @ob_end_clean();
        }

        // Diagnóstico: si ya se enviaron headers, loguear y devolver mensaje en texto para evitar que dompdf muera
        if (headers_sent($file, $line)) {
            // Log only: do not echo to the client (prevents breaking the layout)
            error_log('PDFHelper::generarPDF - headers already sent by ' . ($file ?? 'unknown') . ':' . ($line ?? '0'));
            // Try to clean any buffers to leave a clean state, then return without streaming
            while (ob_get_level()) {
                @ob_end_clean();
            }
            return;
        }

        // Ahora cargar el HTML
        $dompdf->loadHtml($html);

        // Configurar papel
        $dompdf->setPaper($size, $orientation);

        // Renderizar
        $dompdf->render();

        // Limpiar buffer de salida antes de enviar PDF
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Salida
        $dompdf->stream($filename . '.pdf', [
            'Attachment' => $download ? 1 : 0
        ]);
    }

    /**
     * Genera PDF de ticket de pago
     */
    public static function generarTicketPDF(array $ticket, ?array $pago = null, ?array $paciente = null): void
    {
        $html = self::generarHTMLTicket($ticket, $pago, $paciente);
        self::generarPDF($html, 'ticket_' . $ticket['id'], 'portrait', 'letter', true);
    }

    /**
     * Genera HTML para un ticket de Cita (no pago) y retorna el HTML
     */
    private static function generarHTMLCita(array $cita, ?array $paciente = null, ?array $psicologo = null): string
    {
        $base = defined('RUTA') ? RUTA : '';

        // Obtener QR de la cita
        $qrPath = '';
        if (!empty($cita['qr_code'])) {
            $qr = ltrim(trim($cita['qr_code']), '/');
            $qr = preg_replace('#^public/#', '', $qr);
            if ($qr && !str_starts_with($qr, 'qrcodes/')) {
                if (!str_contains($qr, '/'))
                    $qr = 'qrcodes/' . $qr;
            }
            $qrPath = __DIR__ . '/../public/' . $qr;
        }
        if (!file_exists($qrPath) && !empty($cita['id'])) {
            $qrPath = __DIR__ . '/../public/qrcodes/cita_id_' . (int) $cita['id'] . '.png';
        }

        $qrBase64 = '';
        if (file_exists($qrPath)) {
            $qrData = file_get_contents($qrPath);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);
        }

        $pacNombre = $paciente['nombre'] ?? ($cita['paciente_nombre'] ?? 'Paciente');
        $psicNombre = $psicologo['nombre'] ?? ($cita['psicologo_nombre'] ?? 'Psicólogo');
        $fecha = htmlspecialchars($cita['fecha_hora'] ?? '');

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;margin:18px} .ticket{max-width:480px;margin:0 auto;border:1px solid #222;padding:18px;border-radius:8px} .center{text-align:center} .qr{margin:12px auto} .meta{margin-top:10px}</style></head><body>';
        $html .= '<div class="ticket">';
        $html .= '<h2 class="center">Comprobante de Cita</h2>';
        $html .= '<div class="center"><strong>' . htmlspecialchars($psicNombre) . '</strong></div>';
        $html .= '<div class="center small text-muted">' . htmlspecialchars($fecha) . '</div>';
        $html .= '<div class="meta"><strong>Paciente:</strong> ' . htmlspecialchars($pacNombre) . '<br><strong>Cita ID:</strong> ' . (int) $cita['id'] . '</div>';
        if ($qrBase64) {
            $html .= '<div class="qr center"><img src="' . $qrBase64 . '" width="200" height="200" alt="QR"></div>';
        }
        $html .= '<div class="center small text-muted">Presenta este comprobante al momento de la atención.</div>';
        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Genera y envía al navegador un PDF para una Cita (descarga)
     */
    public static function generarCitaPDF(array $cita, ?array $paciente = null, ?array $psicologo = null): void
    {
        $html = self::generarHTMLCita($cita, $paciente, $psicologo);
        // nombre archivo cita_ID
        $name = 'cita_' . ((int) $cita['id']);
        self::generarPDF($html, $name, 'portrait', 'letter', true);
    }

    /**
     * Genera el HTML del ticket para PDF
     */
    private static function generarHTMLTicket(array $ticket, ?array $pago = null, ?array $paciente = null): string
    {
        $base = defined('RUTA') ? RUTA : '';

        // Determinar ruta del QR
        $qrPath = '';
        if (!empty($ticket['qr_code'])) {
            $qr = trim($ticket['qr_code']);
            $qr = ltrim($qr, '/');
            $qr = preg_replace('#^public/#', '', $qr);
            if ($qr && !str_starts_with($qr, 'qrcodes/')) {
                if (!str_contains($qr, '/'))
                    $qr = 'qrcodes/' . $qr;
            }
            $qrPath = __DIR__ . '/../public/' . $qr;
        }

        // Si no existe, intentar construir desde id_pago
        if (!file_exists($qrPath) && !empty($ticket['id_pago'])) {
            $qrPath = __DIR__ . '/../public/qrcodes/ticket_' . (int) $ticket['id_pago'] . '.png';
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
                <div class="ticket-number">TICKET #' . (int) $ticket['id'] . '</div>
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
                    <span class="kv-value">#' . (int) $pago['id'] . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Monto Base:</span>
                    <span class="kv-value">$' . number_format((float) $pago['monto_base'], 2) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Monto Total:</span>
                    <span class="kv-value">$' . number_format((float) $pago['monto_total'], 2) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Estado:</span>
                    <span class="kv-value"><span class="status-badge ' . $estadoBadgeClass . '">' . $estadoTexto . '</span></span>
                </div>
            </div>';
        }
        // Mostrar código de acceso del paciente si está disponible
        $codigoAcceso = $paciente['codigo_acceso'] ?? $paciente['codigo'] ?? ($ticket['codigo_acceso'] ?? '');
        if ($codigoAcceso) {
            $html .= '
            <div class="section">
                <div class="section-title">Acceso Paciente</div>
                <div class="kv-row">
                    <span class="kv-label">Paciente:</span>
                    <span class="kv-value">' . htmlspecialchars($paciente['nombre'] ?? ($ticket['paciente_nombre'] ?? '')) . '</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Código de Acceso:</span>
                    <span class="kv-value" style="font-family:DejaVu Sans Mono">' . htmlspecialchars($codigoAcceso) . '</span>
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

    /**
     * Guarda el ticket como archivo PDF (y opcionalmente PNG si Imagick está disponible).
     * Devuelve rutas generadas ['pdf' => path, 'png' => path|null]
     */
    public static function guardarTicketPDFFile(array $ticket, ?array $pago = null, ?array $paciente = null): array
    {
        $html = self::generarHTMLTicket($ticket, $pago, $paciente);
        // Generar PDF en memoria
        // Reutilizar las opciones y render del método generarPDF pero en vez de stream, obtener output
        // Inicializar Dompdf como en generarPDF
        spl_autoload_register(function ($class) {
            if (strpos($class, 'Dompdf\\') === 0) {
                $classPath = str_replace('Dompdf\\', '', $class);
                $classPath = str_replace('\\', '/', $classPath);
                $file = __DIR__ . '/../libs/dompdf/dompdf/src/' . $classPath . '.php';
                if (file_exists($file))
                    require_once $file;
            }
            if ($class === 'Cpdf') {
                $file = __DIR__ . '/../libs/dompdf/dompdf/lib/Cpdf.php';
                if (file_exists($file))
                    require_once $file;
            }
        });
        if (!class_exists('Masterminds\\HTML5')) {
            // ensure stub exists (same as generarPDF)
            eval ('namespace Masterminds { class HTML5 { public function __construct($o=array()){} public function loadHTML($h){ $d=new \\DOMDocument(); @$d->loadHTML($h, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); return $d;} public function save($dom){return $dom->saveHTML();} public function saveHTML($dom=null){ if($dom===null) return ""; return $dom->saveHTML();}} }');
        }
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', realpath(__DIR__ . '/../public'));
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $pdfString = $dompdf->output();

        $dir = __DIR__ . '/../public/tickets/';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        $pdfPath = $dir . 'ticket_' . (int) $ticket['id'] . '.pdf';
        file_put_contents($pdfPath, $pdfString);

        $pngPath = null;
        // Try to convert to PNG if Imagick available
        if (class_exists('Imagick')) {
            try {
                $im = new \Imagick();
                $im->setResolution(150, 150);
                $im->readImageBlob($pdfString);
                $im->setImageFormat('png');
                $pngPath = $dir . 'ticket_' . (int) $ticket['id'] . '.png';
                $im->writeImage($pngPath);
                $im->clear();
                $im->destroy();
            } catch (Throwable $e) {
                error_log('Imagick conversion failed: ' . $e->getMessage());
                $pngPath = null;
            }
        }

        // Return web-relative paths (without leading public/)
        $res = ['pdf' => 'tickets/ticket_' . (int) $ticket['id'] . '.pdf', 'png' => $pngPath ? 'tickets/ticket_' . (int) $ticket['id'] . '.png' : null];
        return $res;
    }
}
