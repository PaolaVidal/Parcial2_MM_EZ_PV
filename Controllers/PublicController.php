<?php
/** Portal público de Paciente (sin credenciales de Usuario) */
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../models/Especialidad.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../models/HorarioPsicologo.php';
require_once __DIR__ . '/BaseController.php';

class PublicController extends BaseController
{

    private string $viewsPath;
    public function __construct()
    {
        $this->viewsPath = __DIR__ . '/../Views/public/';
    }

    // Render específico para vistas públicas dentro de subcarpeta, mantiene compatibilidad con BaseController
    protected function render($vista, $data = []): void
    {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista pública faltante: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        // Asegurar que RUTA esté disponible
        if (!defined('RUTA')) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
            define('RUTA', $scheme . '://' . $host . $basePath);
        }
        extract($data);
        require $file;
    }

    // Página principal portal
    public function portal(): void
    {
        $this->render('portal');
    }

    // Lista de psicólogos activos con filtros de búsqueda y especialidad
    public function disponibilidad(): void
    {
        $nombre = trim($_GET['q'] ?? '');
        $idEsp = isset($_GET['esp']) && ctype_digit($_GET['esp']) ? (int) $_GET['esp'] : null;
        $psicoModel = new Psicologo();
        $psicologos = $psicoModel->listarActivosFiltrado($nombre, $idEsp);
        $horarioModel = new HorarioPsicologo();
        foreach ($psicologos as &$p) {
            $p['horarios'] = $horarioModel->listarPorPsicologo($p['id']);
        }
        unset($p);
        $especialidades = (new Especialidad())->listarActivas();
        $this->render('disponibilidad', [
            'psicologos' => $psicologos,
            'q' => $nombre,
            'esp' => $idEsp,
            'especialidades' => $especialidades
        ]);
    }

    // Login solo por Código de Acceso (DUI eliminado del formulario)
    public function acceso(): void
    {
        $pac = new Paciente();
        $msg = '';
        $codigo = $_POST['codigo'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($codigo)) {
                $msg = 'Ingresa tu código de acceso';
            } else {
                $row = $pac->getByCodigo($codigo);
                if ($row) {
                    // Sesión previa de otro rol: opcionalmente preservar, pero aquí la sobreescribimos si no es admin/psicologo
                    // Variables específicas (compatibilidad con código existente)
                    $_SESSION['paciente_id'] = $row['id'];
                    $_SESSION['paciente_nombre'] = $row['nombre'];
                    if (isset($row['dui'])) {
                        $_SESSION['paciente_dui'] = $row['dui'];
                    }
                    // Sesión unificada estilo usuario (para navbar y comprobaciones de rol)
                    $_SESSION['usuario'] = [
                        'id' => $row['id'],           // usamos id de paciente ya que no existe Usuario asociado
                        'id_paciente' => $row['id'],  // redundante pero explícito
                        'nombre' => $row['nombre'],
                        'rol' => 'paciente'
                    ];
                    $this->safeRedirect(RUTA . 'public/panel');
                    return;
                } else {
                    $msg = 'Código de acceso incorrecto';
                }
            }
        }
        // Compatibilidad: la vista ya no usa $dui, pero lo dejamos vacío por si se referencia
        $dui = '';
        $this->render('acceso', compact('msg', 'dui', 'codigo'));
    }

    // Panel tras validar código
    public function panel(): void
    {
        $paciente = $this->requirePortal();
        $idPac = (int) $paciente['id'];

        // Exportaciones: pdf_historial | excel_historial | pdf_grafica
        if (isset($_GET['export'])) {
            $this->exportarPanel($_GET['export'], $idPac);
            return;
        }

        $citaM = new Cita();
        // Historial de citas realizadas (estado_cita='realizada')
        $historialRealizadas = $this->historialRealizadasPaciente($idPac);
        // Citas por mes (últimos 12 meses si se desea, aquí todas y luego limit en front)
        $citasPorMes = $this->citasRealizadasPacientePorMes($idPac, 12);

        $this->render('panel', [
            'paciente' => $paciente,
            'historialRealizadas' => $historialRealizadas,
            'citasPorMesPaciente' => $citasPorMes
        ]);
    }

    private function historialRealizadasPaciente(int $idPaciente): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT c.id, c.fecha_hora, c.motivo_consulta, c.estado_cita, 
                                     u.nombre AS psicologo, e.nombre AS especialidad
                              FROM Cita c
                              LEFT JOIN Psicologo p ON p.id = c.id_psicologo
                              LEFT JOIN Usuario u ON u.id = p.id_usuario
                              LEFT JOIN Especialidad e ON e.id = p.id_especialidad
                              WHERE c.id_paciente=? AND c.estado_cita='realizada'
                              ORDER BY c.fecha_hora DESC");
        $st->execute([$idPaciente]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function citasRealizadasPacientePorMes(int $idPaciente, int $limitMeses = 12): array
    {
        $db = (new Cita())->pdo();
        // Tomar últimos $limitMeses meses incluyendo mes actual
        $st = $db->prepare("SELECT DATE_FORMAT(fecha_hora,'%Y-%m') mes, COUNT(*) total
                             FROM Cita
                             WHERE id_paciente=? AND estado_cita='realizada'
                             GROUP BY mes
                             ORDER BY mes DESC
                             LIMIT ?");
        $st->bindValue(1, $idPaciente, PDO::PARAM_INT);
        $st->bindValue(2, $limitMeses, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($rows); // para mostrar cronológico ascendente en gráfica
    }

    private function exportarPanel(string $formato, int $idPaciente): void
    {
        $formato = strtolower(trim($formato));
        // Limpiar cualquier salida previa (evita headers already sent)
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $historial = $this->historialRealizadasPaciente($idPaciente);
        $citasMes = $this->citasRealizadasPacientePorMes($idPaciente, 24); // más amplitud para gráfico si se desea
        // Datos resumen
        $total = count($historial);
        $primerFecha = $total ? substr($historial[$total - 1]['fecha_hora'], 0, 10) : '';
        $ultimaFecha = $total ? substr($historial[0]['fecha_hora'], 0, 10) : '';
        $meses = count($citasMes);

        if ($formato === 'excel_historial') {
            require_once __DIR__ . '/../helpers/ExcelHelper.php';
            $sheetHist = [];
            foreach ($historial as $h) {
                $sheetHist[] = [
                    substr($h['fecha_hora'], 0, 16),
                    $h['psicologo'] ?: 'N/D',
                    $h['especialidad'] ?: 'N/D',
                    $h['motivo_consulta'] ?: '',
                    $h['estado_cita']
                ];
            }
            $sheetMes = [];
            foreach ($citasMes as $m) {
                $sheetMes[] = [$m['mes'], $m['total']];
            }
            $sheets = [
                'RESUMEN' => [
                    'headers' => ['Total Citas Realizadas', 'Primera Fecha', 'Última Fecha', 'Meses Cubiertos'],
                    'data' => [[$total, $primerFecha, $ultimaFecha, $meses]]
                ],
                'HISTORIAL REALIZADAS' => [
                    'headers' => ['Fecha/Hora', 'Psicólogo', 'Especialidad', 'Motivo', 'Estado'],
                    'data' => $sheetHist
                ],
                'CITAS POR MES' => [
                    'headers' => ['Mes', 'Total'],
                    'data' => $sheetMes
                ]
            ];
            $filename = 'Historial_Citas_' . date('Ymd_His');
            ExcelHelper::exportarMultiplesHojas($sheets, $filename, 'Historial Citas Paciente');
            exit;
        }

        if ($formato === 'pdf_historial') {
            require_once __DIR__ . '/../helpers/PDFHelper.php';
            $rows = '';
            foreach ($historial as $h) {
                $fecha = htmlspecialchars(substr($h['fecha_hora'], 0, 16));
                $ps = htmlspecialchars($h['psicologo'] ?: 'N/D');
                $esp = htmlspecialchars($h['especialidad'] ?: 'N/D');
                $mot = htmlspecialchars($h['motivo_consulta'] ?: '');
                $est = htmlspecialchars($h['estado_cita']);
                $rows .= "<tr><td>$fecha</td><td>$ps</td><td>$esp</td><td>$mot</td><td>$est</td></tr>";
            }
            if ($rows === '') {
                $rows = '<tr><td colspan="5" style="text-align:center">Sin citas realizadas</td></tr>';
            }
            $generado = date('d/m/Y H:i');
            $html = "<!doctype html><html><head><meta charset='utf-8'><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px}h1{margin:0 0 12px}table{width:100%;border-collapse:collapse;font-size:10px}th,td{border:1px solid #ccc;padding:6px}th{background:#f2f2f2}small{color:#666}</style></head><body><h1>Historial de Citas Realizadas</h1><small>Generado: $generado</small><table><thead><tr><th>Fecha/Hora</th><th>Psicólogo</th><th>Especialidad</th><th>Motivo</th><th>Estado</th></tr></thead><tbody>$rows</tbody></table></body></html>";
            // Limpiar más buffers si se generó algo extra
            if (ob_get_length()) {
                @ob_end_clean();
            }
            PDFHelper::generarPDF($html, 'Historial_Citas_' . date('Ymd'), 'portrait', 'letter', true);
            exit;
        }

        if ($formato === 'pdf_grafica') {
            require_once __DIR__ . '/../helpers/PDFHelper.php';
            require_once __DIR__ . '/../helpers/ChartHelper.php';
            $labels = array_column($citasMes, 'mes');
            $datos = array_map(fn($r) => (int) $r['total'], $citasMes);
            $chart = ChartHelper::generarBarChart($datos, $labels, 'Citas Realizadas por Mes', 700, 300);
            $generado = date('d/m/Y H:i');
            $html = "<!doctype html><html><head><meta charset='utf-8'><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px;text-align:center}h1{margin:0 0 15px}</style></head><body><h1>Gráfica Citas por Mes</h1><div><img src='$chart' style='width:100%;max-width:700px'></div><div style='margin-top:10px;font-size:10px'>Generado: $generado - Total: $total citas</div></body></html>";
            if (ob_get_length()) {
                @ob_end_clean();
            }
            PDFHelper::generarPDF($html, 'Grafica_Citas_' . date('Ymd'), 'landscape', 'letter', true);
            exit;
        }

        // Formato no reconocido -> salida simple
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Exportación no soportada';
        exit;
    }

    public function citas(): void
    {
        $paciente = $this->requirePortal();
        $citas = (new Cita())->listarPaciente($paciente['id']);
        $this->render('citas', ['citas' => $citas]);
    }

    public function pagos(): void
    {
        $paciente = $this->requirePortal();
        $pagos = (new Pago())->listarPaciente($paciente['id']);
        $this->render('pagos', ['pagos' => $pagos]);
    }

    public function solicitud(): void
    {
        $paciente = $this->requirePortal();
        $msg = '';
        $ok = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campo = trim($_POST['campo'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            if ($campo === '' || $valor === '') {
                $msg = 'Completa los campos.';
            } else {
                (new SolicitudCambio())->crear($paciente['id'], $campo, $valor);
                $msg = 'Solicitud enviada exitosamente. Será revisada por un administrador.';
                $ok = true;
            }
        }
        // Obtener historial de solicitudes del paciente
        $historial = (new SolicitudCambio())->listarPorPaciente($paciente['id']);
        $this->render('solicitud', ['msg' => $msg, 'ok' => $ok, 'historial' => $historial]);
    }

    public function salir(): void
    {
        unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
        if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['rol'] ?? '') === 'paciente') {
            unset($_SESSION['usuario']);
        }
        $this->safeRedirect(RUTA . 'public/portal');
    }

    private function requirePortal(): array
    {
        if (!isset($_SESSION['paciente_id'])) {
            $this->safeRedirect(RUTA . 'public/acceso'); // termina ejecución
        }
        $pac = (new Paciente())->getById((int) $_SESSION['paciente_id']);
        if (!$pac) {
            unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
            $this->safeRedirect(RUTA . 'public/acceso'); // termina ejecución
        }
        // Si existe campo estado y no está activo, forzar salida
        if (isset($pac['estado']) && $pac['estado'] !== 'activo') {
            unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
            if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['rol'] ?? '') === 'paciente') {
                unset($_SESSION['usuario']);
            }
            $this->safeRedirect(RUTA . 'public/acceso?msg=Cuenta%20inactiva');
        }
        // Fallback: si el login se hizo antes de introducir la sesión unificada o se perdió $_SESSION['usuario']
        if (!isset($_SESSION['usuario']) || (($_SESSION['usuario']['rol'] ?? '') !== 'paciente')) {
            $_SESSION['usuario'] = [
                'id' => (int) $pac['id'],
                'id_paciente' => (int) $pac['id'],
                'nombre' => $pac['nombre'] ?? 'Paciente',
                'rol' => 'paciente'
            ];
        }
        return $pac; // siempre retorna array si no redirige
    }

    // safeRedirect ahora heredado de BaseController
}