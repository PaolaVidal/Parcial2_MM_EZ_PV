<?php
require_once __DIR__ . '/../Models/Cita.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Pago.php';
require_once __DIR__ . '/../Models/TicketPago.php';
require_once __DIR__ . '/../Models/Psicologo.php';
require_once __DIR__ . '/../Models/Evaluacion.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/../helpers/PDFHelper.php';
require_once __DIR__ . '/../helpers/ExcelHelper.php';
require_once __DIR__ . '/BaseController.php';

class PsicologoController extends BaseController
{

    /** Obtiene el id real del psicólogo (tabla Psicologo.id) a partir del usuario logueado.
     *  Cachea sólo si encuentra un id válido (>0). */
    private function currentPsicologoId(): int
    {
        $this->requirePsicologo();
        if (isset($_SESSION['psicologo_id']) && (int) $_SESSION['psicologo_id'] > 0) {
            return (int) $_SESSION['psicologo_id'];
        }
        $idUsuario = (int) $_SESSION['usuario']['id'];
        $modelo = new Psicologo();
        $row = $modelo->obtenerPorUsuario($idUsuario);
        if ($row) {
            $_SESSION['psicologo_id'] = (int) $row['id'];
            return (int) $row['id'];
        }
        // Si no existe registro en Psicologo, devolver 0 (esto causará error en slots/crear) y dejar pista
        $_SESSION['diag_psicologo_id'] = 'No hay fila en Psicologo para id_usuario=' . $idUsuario;
        return 0;
    }

    protected function requirePsicologo(): void
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'psicologo') {
            $this->safeRedirect(RUTA . 'auth/login');
        }
    }

    protected function render($vista, $data = []): void
    {
        // Sin header/footer porque el index raíz ya monta layout y navbar
        $file = __DIR__ . '/../Views/' . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista no encontrada: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }

    public function dashboard(): void
    {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        $pagoM = new Pago();

        // Datos básicos
        $hoy = date('Y-m-d');
        $pendHoy = $this->countEstadoDia($idPsico, 'pendiente', $hoy);
        $realHoy = $this->countEstadoDia($idPsico, 'realizada', $hoy);
        $cancelHoy = $this->countEstadoDia($idPsico, 'cancelada', $hoy);
        $ingresos = $this->ingresosPsicologo($idPsico);

        // Estadísticas del mes
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');
        $citasMes = $this->countCitasRango($idPsico, $inicioMes, $finMes);
        $ingresosMes = $this->ingresosPsicologoRango($idPsico, $inicioMes, $finMes);

        // Próximos slots y citas pendientes
        $slots = $citaM->proximosSlots($idPsico, 5);
        $proximasCitas = $this->proximasCitasPendientes($idPsico, 5);

        // Estadísticas por estado (últimos 30 días)
        $hace30 = date('Y-m-d', strtotime('-30 days'));
        $estadisticas = $this->estadisticasUltimos30Dias($idPsico);

        // Total de pacientes únicos atendidos
        $totalPacientes = $this->contarPacientesUnicos($idPsico);

        $this->render('psicologo/dashboard', [
            'pendHoy' => $pendHoy,
            'realHoy' => $realHoy,
            'cancelHoy' => $cancelHoy,
            'ingresos' => $ingresos,
            'citasMes' => $citasMes,
            'ingresosMes' => $ingresosMes,
            'slots' => $slots,
            'proximasCitas' => $proximasCitas,
            'estadisticas' => $estadisticas,
            'totalPacientes' => $totalPacientes
        ]);
    }

    public function estadisticas(): void
    {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        // Capturamos posible rango antes de export para que se transmita en query
        $inicioParam = $_GET['inicio'] ?? '';
        $finParam = $_GET['fin'] ?? '';
        // Export dispatch: soportar export=pdf_graficas | pdf_datos | excel
        if (isset($_GET['export'])) {
            $this->exportarEstadisticas($_GET['export'], $idPsico);
            return;
        }

        // Parámetros de rango opcionales ?inicio=YYYY-mm-dd&fin=YYYY-mm-dd
        $inicio = $_GET['inicio'] ?? '';
        $fin = $_GET['fin'] ?? '';
        $rangoValido = false;
        $citasRango = [];
        $pacientesUnicosRango = 0;
        $serieRango = [];
        if ($inicio && $fin) {
            // Validación básica formato y orden
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) && $inicio <= $fin) {
                $rangoValido = true;
                $db = (new Cita())->pdo();
                // Citas del psicólogo en el rango
                $st = $db->prepare("SELECT c.id, c.fecha_hora, c.estado_cita, p.nombre paciente
                                     FROM Cita c
                                     LEFT JOIN Paciente p ON p.id = c.id_paciente
                                     WHERE c.id_psicologo=? AND DATE(c.fecha_hora) BETWEEN ? AND ?
                                     ORDER BY c.fecha_hora");
                $st->execute([$idPsico, $inicio, $fin]);
                $citasRango = $st->fetchAll(PDO::FETCH_ASSOC);
                // Pacientes únicos en el rango
                $st2 = $db->prepare("SELECT COUNT(DISTINCT c.id_paciente) FROM Cita c WHERE c.id_psicologo=? AND DATE(c.fecha_hora) BETWEEN ? AND ?");
                $st2->execute([$idPsico, $inicio, $fin]);
                $pacientesUnicosRango = (int) $st2->fetchColumn();
                // Serie diaria para gráfico (citas por día)
                $st3 = $db->prepare("SELECT DATE(c.fecha_hora) dia, COUNT(*) total FROM Cita c WHERE c.id_psicologo=? AND DATE(c.fecha_hora) BETWEEN ? AND ? GROUP BY dia ORDER BY dia");
                $st3->execute([$idPsico, $inicio, $fin]);
                $serieRango = $st3->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Datos para gráficos
        $citasPorMes = $this->citasPorMes($idPsico, 12); // Últimos 12 meses
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);

        // Estadísticas generales
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);

        $this->render('psicologo/estadisticas', [
            'citasPorMes' => $citasPorMes,
            'citasPorEstado' => $citasPorEstado,
            'ingresosPorMes' => $ingresosPorMes,
            'pacientesFrecuentes' => $pacientesFrecuentes,
            'horariosPopulares' => $horariosPopulares,
            'tasaCancelacion' => $tasaCancelacion,
            'promedioIngresoDiario' => $promedioIngresoDiario,
            'totalCitas' => $totalCitas,
            'totalPacientes' => $totalPacientes,
            'ingresoTotal' => $ingresoTotal,
            // Rango
            'rangoInicio' => $inicio,
            'rangoFin' => $fin,
            'rangoValido' => $rangoValido,
            'pacientesUnicosRango' => $pacientesUnicosRango,
            'serieRango' => $serieRango
        ]);
    }

    /**
     * Permite al psicólogo buscar y consultar el historial clínico, citas y evaluaciones
     * de cualquier paciente (sin importar si el psicólogo lo atendió o no).
     * URL ejemplo: ?url=psicologo/consultarPaciente&id=123 o ?url=psicologo/consultarPaciente&q=nombre
     */
    public function consultarPaciente(): void
    {
        $this->requirePsicologo();
        $q = trim($_GET['q'] ?? '');
        $id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;

        $paciente = null;
        $resultados = [];
        $citas = [];

        $pacModel = new Paciente();
        $citaModel = new Cita();
        $evalModel = new Evaluacion();

        // Si se busca por id directo
        if ($id > 0) {
            $paciente = $pacModel->getById($id);
        }

        // Si viene q (nombre o dui) buscar coincidencias
        if ($q !== '') {
            $db = $pacModel->pdo();
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $st = $db->prepare("SELECT id, nombre, dui, email FROM Paciente WHERE (nombre LIKE ? OR IFNULL(dui,'') LIKE ?) ORDER BY nombre LIMIT 100");
            $st->execute([$like, $like]);
            $resultados = $st->fetchAll(PDO::FETCH_ASSOC);
            // Si solo hay una coincidencia y no se proporcionó id, seleccionarla
            if (!$paciente && count($resultados) === 1) {
                $paciente = $pacModel->getById((int) $resultados[0]['id']);
            }
        }

        if ($paciente) {
            // Obtener historial y citas; listarPacienteTodos devuelve psicologo_nombre y especialidad si están
            $historialClinico = $paciente['historial_clinico'] ?? $paciente['historial'] ?? '';

            // Filtros opcionales recibidos por GET
            $estadoFiltro = trim($_GET['estado'] ?? 'all'); // all|pendiente|realizada|cancelada
            $inicioFiltro = trim($_GET['inicio'] ?? '');
            $finFiltro = trim($_GET['fin'] ?? '');

            $citasTodas = $citaModel->listarPacienteTodos((int) $paciente['id']);
            $citas = [];

            // Validar rango
            $rangoValido = false;
            if ($inicioFiltro && $finFiltro && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicioFiltro) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $finFiltro) && $inicioFiltro <= $finFiltro) {
                $rangoValido = true;
            }

            foreach ($citasTodas as $c) {
                $estadoC = $c['estado_cita'] ?? $c['estado'] ?? '';
                // Filtrar por estado si se especificó
                if ($estadoFiltro !== 'all' && $estadoFiltro !== '' && $estadoC !== $estadoFiltro)
                    continue;
                // Filtrar por rango si es válido
                if ($rangoValido) {
                    $dia = substr($c['fecha_hora'], 0, 10);
                    if ($dia < $inicioFiltro || $dia > $finFiltro)
                        continue;
                }
                // Adjuntar evaluaciones
                $c['evaluaciones'] = $evalModel->obtenerPorCita((int) $c['id']);
                $citas[] = $c;
            }
        } else {
            $historialClinico = '';
        }

        $this->render('psicologo/consultas_paciente', [
            'q' => $q,
            'resultados' => $resultados,
            'paciente' => $paciente,
            'historialClinico' => $historialClinico,
            'citas' => $citas,
            'estadoFiltro' => $estadoFiltro ?? 'all',
            'inicioFiltro' => $inicioFiltro ?? '',
            'finFiltro' => $finFiltro ?? ''
        ]);
    }

    /**
     * Exportar estadísticas para psicólogo: pdf_graficas, pdf_datos, excel
     */
    private function exportarEstadisticas(string $formato, int $idPsico): void
    {
        $formato = (string) $formato;
        // Parámetros de rango opcionales (cuando se exporta por rango)
        $inicio = $_GET['inicio'] ?? '';
        $fin = $_GET['fin'] ?? '';
        $rangoOk = false;
        if ($inicio && $fin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) && $inicio <= $fin) {
            $rangoOk = true;
        }
        // Excel branch - reuse existing exporter
        if ($formato === 'excel') {
            $this->exportarEstadisticasExcel();
            return;
        }
        if ($formato === 'excel_rango') {
            if (!$rangoOk) {
                header('Content-Type: text/plain; charset=utf-8');
                echo "Rango inválido";
                return;
            }
            require_once __DIR__ . '/../helpers/ExcelHelper.php';
            $db = (new Cita())->pdo();
            // Pacientes únicos y serie diaria
            $stSerie = $db->prepare("SELECT DATE(fecha_hora) dia, COUNT(*) total FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ? GROUP BY dia ORDER BY dia");
            $stSerie->execute([$idPsico, $inicio, $fin]);
            $serie = $stSerie->fetchAll(PDO::FETCH_ASSOC);
            $stPac = $db->prepare("SELECT COUNT(DISTINCT id_paciente) FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ?");
            $stPac->execute([$idPsico, $inicio, $fin]);
            $pacUnicos = (int) $stPac->fetchColumn();
            $stDet = $db->prepare("SELECT c.id, c.fecha_hora, c.estado_cita, COALESCE(p.nombre,'') nombre FROM Cita c LEFT JOIN Paciente p ON p.id=c.id_paciente WHERE c.id_psicologo=? AND DATE(c.fecha_hora) BETWEEN ? AND ? ORDER BY c.fecha_hora");
            $stDet->execute([$idPsico, $inicio, $fin]);
            $detalle = $stDet->fetchAll(PDO::FETCH_ASSOC);
            $sheets = [];
            $sheets['RESUMEN RANGO'] = [
                'headers' => ['Fecha Inicio', 'Fecha Fin', 'Pacientes Únicos', 'Total Citas'],
                'data' => [[$inicio, $fin, $pacUnicos, array_sum(array_map(fn($r) => (int) $r['total'], $serie))]]
            ];
            $sheets['CITAS POR DÍA'] = [
                'headers' => ['Día', 'Total Citas'],
                'data' => array_map(fn($r) => [$r['dia'], $r['total']], $serie)
            ];
            $sheets['DETALLE CITAS'] = [
                'headers' => ['ID', 'Fecha/Hora', 'Paciente', 'Estado'],
                'data' => array_map(function ($c) {
                    return [$c['id'], $c['fecha_hora'], $c['nombre'], $c['estado_cita']];
                }, $detalle)
            ];
            $filename = 'Rango_Psicologo_' . $inicio . '_a_' . $fin . '_' . date('Ymd_His');
            ExcelHelper::exportarMultiplesHojas($sheets, $filename, 'Rango de Fechas');
            return;
        }

        // For PDFs we need ChartHelper and PDFHelper
        require_once __DIR__ . '/../helpers/PDFHelper.php';
        require_once __DIR__ . '/../helpers/ChartHelper.php';

        if ($formato === 'pdf_rango') {
            if (!$rangoOk) {
                $html = '<html><body><h3>Rango inválido</h3><p>Debe proporcionar parámetros inicio y fin válidos.</p></body></html>';
                PDFHelper::generarPDF($html, 'Rango_Invalido', 'portrait', 'letter', true);
                return;
            }
            $db = (new Cita())->pdo();
            $stSerie = $db->prepare("SELECT DATE(fecha_hora) dia, COUNT(*) total FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ? GROUP BY dia ORDER BY dia");
            $stSerie->execute([$idPsico, $inicio, $fin]);
            $serie = $stSerie->fetchAll(PDO::FETCH_ASSOC);
            $labels = array_column($serie, 'dia');
            $datos = array_map(fn($r) => (int) $r['total'], $serie);
            $chart = count($datos) >= 2 ? ChartHelper::generarLineChart($datos, $labels, 'Citas por Día', 700, 300) : ChartHelper::generarBarChart($datos, $labels, 'Citas por Día', 700, 300);
            $stPac = $db->prepare("SELECT COUNT(DISTINCT id_paciente) FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ?");
            $stPac->execute([$idPsico, $inicio, $fin]);
            $pacUnicos = (int) $stPac->fetchColumn();
            $totalCitasRango = array_sum($datos);
            $generado = date('d/m/Y H:i:s');
            $html = "<!doctype html><html><head><meta charset='utf-8'><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px}h1{margin:0 0 10px}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #ccc;padding:6px;font-size:10px}</style></head><body><h1>Reporte de Rango de Fechas</h1><div><strong>Rango:</strong> $inicio a $fin<br><strong>Generado:</strong> $generado</div><div style='margin:12px 0'><strong>Pacientes Únicos:</strong> $pacUnicos &nbsp; | &nbsp; <strong>Total Citas:</strong> $totalCitasRango</div><div style='text-align:center;margin-bottom:15px'><img src='$chart' style='width:100%;max-width:700px'></div></body></html>";
            PDFHelper::generarPDF($html, 'Rango_' . $inicio . '_a_' . $fin . '_' . date('Ymd'), 'portrait', 'letter', true);
            return;
        }

        // Collect same datasets used by the view
        $citasPorMes = $this->citasPorMes($idPsico, 12);
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);

        // Psicólogo info
        $psico = new Psicologo();
        $dataPsico = $psico->get($idPsico);
        $nombrePsico = $dataPsico['nombre'] ?? 'Psicólogo';

        // Prepare charts
        $mesesLabels = array_column($citasPorMes, 'mes');
        $mesesData = array_map('intval', array_column($citasPorMes, 'total'));
        $chartCitasMes = ChartHelper::generarBarChart($mesesData, $mesesLabels, 'Citas por Mes (Ultimos 12 meses)', 700, 300);

        $estadosLabels = [];
        $estadosData = [];
        foreach ($citasPorEstado as $est) {
            $estadosLabels[] = ucfirst($est['estado_cita']);
            $estadosData[] = (int) $est['total'];
        }
        $chartEstado = ChartHelper::generarPieChart($estadosData, $estadosLabels, 'Distribucion de Citas por Estado', 600, 350);

        $ingresosLabels = array_column($ingresosPorMes, 'mes');
        $ingresosData = array_map('floatval', array_column($ingresosPorMes, 'total'));
        $chartIngresos = ChartHelper::generarLineChart($ingresosData, $ingresosLabels, 'Ingresos Mensuales', 700, 300);

        // pdf_graficas -> header + charts + footer
        if ($formato === 'pdf_graficas') {
            $periodoStr = date('Y');
            $generado = date('d/m/Y H:i:s');
            $html = <<<HTML
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;margin:15px}.header{text-align:center;margin-bottom:10px}.footer{text-align:center;font-size:9px;color:#666;margin-top:12px}.page-break{page-break-after:always}</style>
</head>
<body>
    <div class="header"><h2>GRAFICAS - ESTADISTICAS DE {$nombrePsico}</h2><div class="muted">Generado: {$generado}</div></div>
    <div style="text-align:center;margin-bottom:18px"><img src="{$chartCitasMes}" style="width:100%;max-width:700px;margin-bottom:10px"></div>
    <div style="text-align:center;margin-bottom:18px"><img src="{$chartEstado}" style="width:80%;max-width:600px;margin-bottom:10px"></div>
    <div style="text-align:center;margin-bottom:18px"><img src="{$chartIngresos}" style="width:100%;max-width:700px;margin-bottom:10px"></div>
    <div class="footer">Sistema de Gestion de Consultorio Psicologico - Reporte Confidencial</div>
</body>
</html>
HTML;
            PDFHelper::generarPDF($html, 'Graficas_Psicologo_' . preg_replace('/[^A-Za-z0-9_]/', '_', $nombrePsico) . '_' . date('Ymd'), 'landscape', 'letter', true);
            return;
        }

        // pdf_datos -> resumen ampliado con secciones: Resumen Ejecutivo, Citas por Estado, Horarios Populares, Top Pacientes
        if ($formato === 'pdf_datos') {
            // build rows for pacientesFrecuentes
            $rows = '';
            $pos = 1;
            foreach ($pacientesFrecuentes as $p) {
                $nombre = $p['nombre'] ?: ('Paciente #' . ($p['id_paciente'] ?? ''));
                $rows .= '<tr>'
                    . '<td style="padding:6px;border:1px solid #ddd;text-align:center">' . $pos++ . '</td>'
                    . '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td style="padding:6px;border:1px solid #ddd;text-align:center">' . ((int) $p['total_citas']) . '</td>'
                    . '</tr>';
            }

            // Citas por Estado rows
            $rowsEstado = '';
            foreach ($citasPorEstado as $e) {
                $label = htmlspecialchars(ucfirst($e['estado_cita']), ENT_QUOTES, 'UTF-8');
                $rowsEstado .= '<tr>'
                    . '<td style="padding:6px;border:1px solid #ddd">' . $label . '</td>'
                    . '<td style="padding:6px;border:1px solid #ddd;text-align:right">' . ((int) $e['total']) . '</td>'
                    . '</tr>';
            }

            // Horarios Populares rows
            $rowsHorarios = '';
            foreach ($horariosPopulares as $h) {
                $hora = htmlspecialchars($h['hora'] ?? $h['turno'] ?? '', ENT_QUOTES, 'UTF-8');
                $rowsHorarios .= '<tr>'
                    . '<td style="padding:6px;border:1px solid #ddd">' . $hora . '</td>'
                    . '<td style="padding:6px;border:1px solid #ddd;text-align:right">' . ((int) $h['total']) . '</td>'
                    . '</tr>';
            }

            $periodoStr = date('Y');
            $generado = date('d/m/Y H:i:s');
            $ingresoFmt = '$' . number_format($ingresoTotal, 2);
            $tasaCancel = is_numeric($tasaCancelacion) ? round($tasaCancelacion, 2) . '%' : 'N/A';
            $promIngreso = is_numeric($promedioIngresoDiario) ? '$' . number_format($promedioIngresoDiario, 2) : 'N/A';

            $html = <<<HTML
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px;color:#222}h1{color:#2C3E50;margin-bottom:4px}h2{color:#34495E;margin-top:14px;margin-bottom:6px}table{width:100%;border-collapse:collapse;margin-bottom:8px}th{background:#f7f7f7;padding:8px;border:1px solid #e1e1e1;text-align:left}td{padding:8px;border:1px solid #e1e1e1}small.muted{color:#666;font-size:10px}.two-cols{display:flex;gap:16px}.col{flex:1}</style>
        </head>
        <body>
            <h1>REPORTE DE DATOS - ESTADISTICAS</h1>
            <div class="muted">Psicólogo: {$nombrePsico} — Generado: {$generado}</div>

            <h2>Resumen Ejecutivo</h2>
            <div class="two-cols">
                <div class="col">
                    <table>
                        <tr><th>Métrica</th><th style="text-align:right">Valor</th></tr>
                        <tr><td>Total de Citas</td><td style="text-align:right">{$totalCitas}</td></tr>
                        <tr><td>Pacientes Únicos</td><td style="text-align:right">{$totalPacientes}</td></tr>
                        <tr><td>Ingresos Totales</td><td style="text-align:right">{$ingresoFmt}</td></tr>
                    </table>
                </div>
                <div class="col">
                    <table>
                        <tr><th>Métrica</th><th style="text-align:right">Valor</th></tr>
                        <tr><td>Tasa de Cancelación</td><td style="text-align:right">{$tasaCancel}</td></tr>
                        <tr><td>Ingreso Promedio Diario</td><td style="text-align:right">{$promIngreso}</td></tr>
                    </table>
                </div>
            </div>

            <h2>Citas por Estado</h2>
            <table>
                <thead><tr><th>Estado</th><th style="text-align:right">Cantidad</th></tr></thead>
                <tbody>{$rowsEstado}</tbody>
            </table>

            <h2>Horarios Populares</h2>
            <table>
                <thead><tr><th>Horario</th><th style="text-align:right">Veces</th></tr></thead>
                <tbody>{$rowsHorarios}</tbody>
            </table>

            <h2>Top Pacientes Frecuentes</h2>
            <table>
                <thead><tr><th style="width:5%">#</th><th>Paciente</th><th style="width:10%;text-align:right">Citas</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>

            <div class="muted" style="margin-top:12px;font-size:10px">Sistema de Gestion de Consultorio Psicologico - Reporte Confidencial</div>
        </body>
        </html>
        HTML;
            PDFHelper::generarPDF($html, 'Datos_Psicologo_' . preg_replace('/[^A-Za-z0-9_]/', '_', $nombrePsico) . '_' . date('Ymd'), 'portrait', 'letter', true);
            return;
        }

        // Fallback: generate the full professional PDF (existing implementation)
        $this->exportarEstadisticasPDF();
    }

    private function countEstadoDia(int $idPsico, string $estado, string $fecha): int
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND estado_cita=? AND DATE(fecha_hora)=?");
        $st->execute([$idPsico, $estado, $fecha]);
        return (int) $st->fetchColumn();
    }

    private function ingresosPsicologo(int $idPsico): float
    {
        $db = (new Pago())->pdo();
        $st = $db->prepare("SELECT COALESCE(SUM(p.monto_total),0) FROM Pago p JOIN Cita c ON c.id=p.id_cita WHERE c.id_psicologo=? AND p.estado_pago='pagado'");
        $st->execute([$idPsico]);
        return (float) $st->fetchColumn();
    }

    public function citas(): void
    {
        $this->requirePsicologo();
        // Respuesta AJAX de slots (fallback si /slots directo falla en hosting)
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'slots') {
            $this->slots();
            return;
        }
        $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        $pacM = new Paciente();
        $evalM = new Evaluacion();
        $list = $citaM->listarPsicologo($idPsico);
        // Unificar y ordenar por fecha_hora ASC manteniendo estado_cita original
        $todas = array_merge($list['pendientes'], $list['realizadas'], $list['canceladas'] ?? []);
        usort($todas, function ($a, $b) {
            return strcmp($a['fecha_hora'], $b['fecha_hora']);
        });

        // Agregar conteo de evaluaciones a cada cita
        foreach ($todas as &$cita) {
            $cita['count_evaluaciones'] = $evalM->contarPorCita((int) $cita['id']);
        }
        unset($cita); // Limpiar referencia

        // Repartir de nuevo en pendientes/realizadas/canceladas ya ordenadas
        $list['pendientes'] = array_values(array_filter($todas, fn($c) => $c['estado_cita'] === 'pendiente'));
        $list['realizadas'] = array_values(array_filter($todas, fn($c) => $c['estado_cita'] === 'realizada'));
        $list['canceladas'] = array_values(array_filter($todas, fn($c) => $c['estado_cita'] === 'cancelada'));
        $pacientes = $pacM->listarTodos();
        $this->render('psicologo/citas', ['data' => $list, 'pacientes' => $pacientes]);
    }

    public function crear(): void
    {
        $this->requirePsicologo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->safeRedirect(RUTA . 'psicologo/citas');
            return;
        }
        $idPsico = $this->currentPsicologoId();
        if ($idPsico <= 0) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=psico');
            return;
        }
        $idPaciente = (int) ($_POST['id_paciente'] ?? 0);
        $fecha = trim($_POST['fecha_hora'] ?? '');
        $motivo = trim($_POST['motivo_consulta'] ?? '');
        if (strlen($motivo) > 255)
            $motivo = substr($motivo, 0, 255);
        if (!$idPaciente || !$fecha) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=datos');
            return;
        }
        // Validar formato fecha: Y-m-d H:i:s o Y-m-d H:i
        $fh = DateTime::createFromFormat('Y-m-d H:i:s', $fecha) ?: DateTime::createFromFormat('Y-m-d H:i', $fecha);
        if (!$fh) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=formato');
            return;
        }
        // Normalizar a segundos :00
        $fh->setTime((int) $fh->format('H'), (int) $fh->format('i'), 0);
        // Validar minutos 00 o 30
        $min = (int) $fh->format('i');
        if ($min !== 0 && $min !== 30) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=minutos');
            return;
        }
        // No permitir pasado
        $now = new DateTime();
        if ($fh <= $now) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=pasado');
            return;
        }
        // Validar contra horarios configurados en Horario_Psicologo
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
        $diaSemanaMap = ['Mon' => 'lunes', 'Tue' => 'martes', 'Wed' => 'miércoles', 'Thu' => 'jueves', 'Fri' => 'viernes', 'Sat' => 'sábado', 'Sun' => 'domingo'];
        $diaBD = $diaSemanaMap[$fh->format('D')] ?? 'lunes';
        // Variantes sin acento para compatibilidad con datos existentes (miercoles/sabado)
        $variants = [$diaBD];
        if ($diaBD === 'miércoles')
            $variants[] = 'miercoles';
        if ($diaBD === 'sábado')
            $variants[] = 'sabado';
        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $dbChk2 = (new Cita())->pdo();
        $sqlHor = "SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana IN ($placeholders)";
        $stH = $dbChk2->prepare($sqlHor);
        $params = array_merge([$idPsico], $variants);
        $stH->execute($params);
        $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);
        $enRango = false;
        foreach ($bloques as $b) {
            if ($fh->format('H:i:s') >= $b['hora_inicio'] && $fh->format('H:i:s') < $b['hora_fin']) {
                $enRango = true;
                break;
            }
        }
        if (!$enRango) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=fuera_horario');
            return;
        }
        $fecha = $fh->format('Y-m-d H:i:s');
        // Evitar choque con una existente misma fecha_hora para ese psicólogo
        $dbChk = (new Cita())->pdo();
        $stChk = $dbChk->prepare('SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND fecha_hora=? AND estado=\'activo\'');
        $stChk->execute([$idPsico, $fecha]);
        if ((int) $stChk->fetchColumn() > 0) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=ocupado');
            return;
        }
        // Verificar paciente existe
        $pacM = new Paciente();
        $pac = $pacM->getById($idPaciente);
        if (!$pac) {
            $this->safeRedirect(RUTA . 'psicologo/citas?err=paciente');
            return;
        }
        try {
            $citaM = new Cita();
            // Placeholder único para no violar UNIQUE(qr_code) (evitamos '')
            $placeholder = 'PEND_' . bin2hex(random_bytes(6));
            $id = $citaM->crear([
                'id_paciente' => $idPaciente,
                'id_psicologo' => $idPsico,
                'fecha_hora' => $fecha,
                'motivo_consulta' => $motivo,
                'qr_code' => $placeholder
            ]);
            // Generar QR usando el id y guardar la RUTA final (qrcodes/cita_id_ID.png)
            $final = null;
            try {
                // Pasamos nombre sin .png porque el helper lo añade si falta
                $final = QRHelper::generarQR('CITA:' . $id, 'cita', 'cita_id_' . $id);
            } catch (Throwable $e) { /* si falla dejamos placeholder */
            }
            $pdo = (new Cita())->pdo();
            // Si QR ok guardamos ruta, si no al menos guardamos el id para mantener único
            $valorQR = $final ?: (string) $id;
            $pdo->prepare("UPDATE Cita SET qr_code=? WHERE id=?")->execute([$valorQR, $id]);
            $this->safeRedirect(RUTA . 'psicologo/citas?ok=1');
            return;
        } catch (Throwable $e) {
            // Guardar mensaje detallado temporal (no en producción)
            $_SESSION['crear_cita_error'] = $e->getMessage();
            $this->safeRedirect(RUTA . 'psicologo/citas?err=ex');
            return;
        }
    }

    public function scan(): void
    {
        $this->requirePsicologo();
        $this->render('psicologo/scan');
    }

    public function scanProcesar(): void
    {
        $this->requirePsicologo();
        $rawInput = trim($_POST['token'] ?? '');
        $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        // Nueva política: solo aceptar formato QR oficial CITA:<id>
        if (stripos($rawInput, 'CITA:') !== 0) {
            $res = ['ok' => false, 'msg' => 'Formato inválido. Escanee un QR válido (CITA:<id>).'];
        } else {
            $num = substr($rawInput, 5);
            if (!ctype_digit($num)) {
                $res = ['ok' => false, 'msg' => 'ID inválido en el QR'];
            } else {
                $id = (int) $num;
                $cita = $citaM->obtener($id);
                if (!$cita) {
                    $res = ['ok' => false, 'msg' => 'Cita no encontrada'];
                } elseif ((int) $cita['id_psicologo'] !== $idPsico) {
                    $res = ['ok' => false, 'msg' => 'No pertenece a este psicólogo'];
                } else {
                    if ($cita['estado_cita'] === 'pendiente')
                        $citaM->marcarRealizada($id);
                    $cita['estado_cita'] = 'realizada';
                    $res = ['ok' => true, 'msg' => 'Cita confirmada', 'cita' => $cita];
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    // Nuevo: solo consulta (no confirma) - ahora retorna info para "atender"
    public function scanConsultar(): void
    {
        $this->requirePsicologo();
        $rawInput = trim($_POST['token'] ?? '');
        $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();

        if (stripos($rawInput, 'CITA:') !== 0) {
            $res = ['ok' => false, 'msg' => 'Formato inválido. Use CITA:<id>'];
        } else {
            $num = substr($rawInput, 5);
            if (!ctype_digit($num)) {
                $res = ['ok' => false, 'msg' => 'ID inválido'];
            } else {
                $id = (int) $num;
                $cita = $citaM->obtener($id);
                if (!$cita) {
                    $res = ['ok' => false, 'msg' => 'Cita no encontrada'];
                } elseif ((int) $cita['id_psicologo'] !== $idPsico) {
                    $res = ['ok' => false, 'msg' => 'No pertenece a este psicólogo'];
                } else {
                    // Obtener nombre del paciente
                    $pacM = new Paciente();
                    $pac = $pacM->getById((int) $cita['id_paciente']);
                    $cita['nombre_paciente'] = $pac['nombre'] ?? 'Desconocido';

                    // Determinar acción según estado
                    $accion = $cita['estado_cita'] === 'pendiente' ? 'atender' : 'ver';

                    $res = ['ok' => true, 'cita' => $cita, 'accion' => $accion, 'msg' => 'Cita encontrada'];
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    // Ya no se usa para confirmar desde scanner - solo si fuera necesario en otro contexto
    public function scanConfirmar(): void
    {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        $id = (int) ($_POST['id'] ?? 0);
        $citaM = new Cita();
        $cita = $citaM->obtener($id);
        if (!$id || !$cita) {
            $res = ['ok' => false, 'msg' => 'Cita no encontrada'];
        } elseif ((int) $cita['id_psicologo'] !== $idPsico) {
            $res = ['ok' => false, 'msg' => 'No pertenece a este psicólogo'];
        } elseif ($cita['estado_cita'] === 'realizada') {
            $res = ['ok' => true, 'msg' => 'Ya estaba realizada', 'cita' => $cita];
        } else {
            $citaM->marcarRealizada($id);
            $cita['estado_cita'] = 'realizada';
            $res = ['ok' => true, 'msg' => 'Cita confirmada', 'cita' => $cita];
        }
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    // Mostrar vista de atender cita (con evaluaciones)
    public function atenderCita(): void
    {
        $this->requirePsicologo();
        $idCita = (int) ($_GET['id'] ?? 0);
        $idPsico = $this->currentPsicologoId();

        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);

        if (!$cita || (int) $cita['id_psicologo'] !== $idPsico) {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&err=nf');
        }

        // Obtener paciente
        $pacM = new Paciente();
        $paciente = $pacM->getById((int) $cita['id_paciente']);

        // Obtener evaluaciones existentes
        $evalM = new Evaluacion();
        $evaluaciones = $evalM->obtenerPorCita($idCita);

        // Determinar si se puede editar (solo si no está realizada o cancelada)
        $puedeEditar = !in_array($cita['estado_cita'], ['realizada', 'cancelada']);

        $this->render('psicologo/atender_cita', [
            'cita' => $cita,
            'paciente' => $paciente,
            'evaluaciones' => $evaluaciones,
            'puedeEditar' => $puedeEditar
        ]);
    }

    // Guardar evaluación (AJAX)
    public function guardarEvaluacion(): void
    {
        $this->requirePsicologo();
        // Asegurar que no haya salida previa que contamine el JSON
        if (ob_get_length()) {
            @ob_end_clean();
        }
        header('Content-Type: application/json');

        $idCita = (int) ($_POST['id_cita'] ?? 0);
        $estadoEmocional = (int) ($_POST['estado_emocional'] ?? 0);
        $comentarios = trim($_POST['comentarios'] ?? '');

        $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);

        if (!$cita || (int) $cita['id_psicologo'] !== $idPsico) {
            echo json_encode(['ok' => false, 'msg' => 'Cita no válida']);
            exit;
        }

        if (in_array($cita['estado_cita'], ['realizada', 'cancelada'])) {
            echo json_encode(['ok' => false, 'msg' => 'No se puede agregar evaluaciones a citas finalizadas']);
            exit;
        }

        if ($estadoEmocional < 1 || $estadoEmocional > 10) {
            echo json_encode(['ok' => false, 'msg' => 'Estado emocional debe estar entre 1 y 10']);
            exit;
        }

        $evalM = new Evaluacion();
        try {
            $idEval = $evalM->crear($idCita, $estadoEmocional, $comentarios);

            if ($idEval) {
                // Siempre usar array manual para evitar problemas de timing con obtener()
                $evaluacion = [
                    'id' => $idEval,
                    'id_cita' => $idCita,
                    'estado_emocional' => $estadoEmocional,
                    'comentarios' => $comentarios,
                    'estado' => 'activo'
                ];

                // Contar evaluaciones actuales
                $totalEval = $evalM->contarPorCita($idCita);

                echo json_encode([
                    'ok' => true,
                    'msg' => 'Evaluación guardada correctamente',
                    'evaluacion' => $evaluacion,
                    'total' => $totalEval
                ]);
                exit;
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Error al guardar evaluación']);
                exit;
            }
        } catch (Exception $e) {
            error_log('Error en guardarEvaluacion: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }

    // Finalizar cita (marcarla como realizada)
    public function finalizarCita(): void
    {
        $this->requirePsicologo();
        $idCita = (int) ($_POST['id_cita'] ?? 0);
        $idPsico = $this->currentPsicologoId();

        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);

        if (!$cita || (int) $cita['id_psicologo'] !== $idPsico) {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&err=nf');
        }

        if ($cita['estado_cita'] === 'realizada') {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/atenderCita&id=' . $idCita . '&msg=ya_realizada');
        }

        // Verificar que tenga al menos una evaluación
        $evalM = new Evaluacion();
        $countEval = $evalM->contarPorCita($idCita);

        if ($countEval === 0) {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/atenderCita&id=' . $idCita . '&err=sin_eval');
        }

        // Marcar como realizada
        if ($citaM->marcarRealizada($idCita)) {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&ok=finalizada');
        } else {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/atenderCita&id=' . $idCita . '&err=update');
        }
    }

    // Cancelar cita
    public function cancelarCita(): void
    {
        $this->requirePsicologo();
        $idCita = (int) ($_POST['id_cita'] ?? 0);
        $idPsico = $this->currentPsicologoId();

        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);

        if (!$cita || (int) $cita['id_psicologo'] !== $idPsico) {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&err=nf');
        }

        if ($cita['estado_cita'] === 'cancelada') {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&msg=ya_cancelada');
        }

        if ($cita['estado_cita'] === 'realizada') {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&err=ya_realizada');
        }

        // ✅ NUEVA VALIDACIÓN: No permitir cancelar si ya tiene evaluaciones
        $evalM = new Evaluacion();
        $countEval = $evalM->contarPorCita($idCita);

        if ($countEval > 0) {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&err=con_evaluaciones');
        }

        // Actualizar estado a cancelada Y liberar el slot (estado='inactivo')
        try {
            $db = $citaM->pdo();
            // ✅ Cambiar estado_cita='cancelada' Y estado='inactivo' para liberar horario
            $st = $db->prepare('UPDATE Cita SET estado_cita = \'cancelada\', estado = \'inactivo\' WHERE id = ?');
            $st->execute([$idCita]);
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&ok=cancelada');
        } catch (Exception $e) {
            error_log('Error cancelando cita: ' . $e->getMessage());
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas&err=cancel');
        }
    }

    /** Devuelve slots disponibles (intervalo 30 o 60) para una fecha dada */
    public function slots(): void
    {
        $this->requirePsicologo();
        header('Content-Type: application/json');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $interval = (int) ($_GET['interval'] ?? 30);
        if (!in_array($interval, [30, 60], true))
            $interval = 30;
        $idPsico = $this->currentPsicologoId();
        if ($idPsico <= 0) {
            echo json_encode(['error' => 'psicologo_no_mapeado', 'diag' => ($_SESSION['diag_psicologo_id'] ?? '')]);
            return;
        }
        // Validar formato fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['error' => 'formato_fecha']);
            return;
        }
        $hoy = new DateTime('today');
        $fReq = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($fReq < $hoy) {
            echo json_encode(['error' => 'fecha_pasada']);
            return;
        }
        // Obtener horarios configurados para ese día de semana
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
        $diaSemanaMap = ['Mon' => 'lunes', 'Tue' => 'martes', 'Wed' => 'miércoles', 'Thu' => 'jueves', 'Fri' => 'viernes', 'Sat' => 'sábado', 'Sun' => 'domingo'];
        $diaBD = $diaSemanaMap[$fReq->format('D')] ?? 'lunes';
        $pdo = (new Cita())->pdo();
        $stH = $pdo->prepare("SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana=? ORDER BY hora_inicio");
        $stH->execute([$idPsico, $diaBD]);
        $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);
        if (!$bloques) {
            echo json_encode(['fecha' => $fecha, 'interval' => $interval, 'slots' => [], 'dia' => $diaBD]);
            return;
        }
        $stC = $pdo->prepare("SELECT fecha_hora FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora)=? AND estado='activo'");
        $stC->execute([$idPsico, $fecha]);
        $ocupadasReg = $stC->fetchAll(PDO::FETCH_ASSOC);
        $ocupadas = [];
        foreach ($ocupadasReg as $r) {
            $hm = substr($r['fecha_hora'], 11, 5);
            $ocupadas[$hm] = true; // marca exacto
        }
        $slots = [];
        $now = new DateTime();
        $isToday = $fecha === $now->format('Y-m-d');
        foreach ($bloques as $b) {
            $ini = new DateTime($fecha . ' ' . $b['hora_inicio']);
            $fin = new DateTime($fecha . ' ' . $b['hora_fin']);
            while ($ini < $fin) {
                $h = $ini->format('H:i');
                if ($ini < $fin && !$this->slotOcupado($h, $interval, $ocupadas) && (!$isToday || $ini > $now)) {
                    $slots[] = $h;
                }
                $ini->modify('+' . $interval . ' minutes');
            }
        }
        sort($slots);
        echo json_encode(['fecha' => $fecha, 'interval' => $interval, 'slots' => $slots, 'dia' => $diaBD]);
    }

    private function slotOcupado(string $h, int $interval, array $ocupadas): bool
    {
        if (isset($ocupadas[$h]))
            return true; // misma hora exacta ya ocupada
        if ($interval === 60) {
            // si hay intervalo de 60, considerar que cita ocupada a mitad o inicio bloquea toda la hora
            // Revisar también h+30
            [$H, $M] = explode(':', $h);
            $h30 = sprintf('%02d:%02d', (int) $H, (int) $M + 30 >= 60 ? ((int) $H + 1) % 24 : (int) $M + 30);
            if (isset($ocupadas[$h30]))
                return true;
        }
        return false;
    }

    public function pagar(): void
    {
        $this->requirePsicologo();
        $idCita = (int) ($_POST['id_cita'] ?? 0);
        if (!$idCita) {
            $this->safeRedirect('index.php?url=psicologo/citas&err=cita');
        }
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);
        if (!$cita) {
            $this->safeRedirect('index.php?url=psicologo/citas&err=nf');
        }
        $idPsico = $this->currentPsicologoId();
        if ($idPsico <= 0) {
            $this->safeRedirect('index.php?url=psicologo/citas&err=psico');
        }
        if ((int) $cita['id_psicologo'] !== $idPsico) {
            $this->safeRedirect('index.php?url=psicologo/citas&err=own');
        }
        if ($cita['estado_cita'] !== 'realizada') {
            $this->safeRedirect('index.php?url=psicologo/citas&err=estado');
        }
        $pagoM = new Pago();
        $idPago = $pagoM->registrarPagoCita($idCita, 50.0);
        // Ticket (simple)
        $ticketM = new TicketPago();
        $ex = $ticketM->obtenerPorPago($idPago);
        if (!$ex) {
            $codigo = strtoupper(substr(hash('sha256', 'pago' . $idPago . microtime()), 0, 10));
            $numero = $idPago; // simple correlativo
            try {
                $qrRuta = QRHelper::generarQR('PAGO:' . $idPago, 'ticket', 'ticket_' . $idPago);
            } catch (Throwable $e) {
                $qrRuta = '';
            }
            $ticketM->crear([
                'id_pago' => $idPago,
                'codigo' => $codigo,
                'numero_ticket' => $numero,
                'qr_code' => $qrRuta
            ]);
        }
        $this->safeRedirect('index.php?url=psicologo/citas&ok=pagado');
    }

    // ============================================
    // MÉTODOS AUXILIARES PARA ESTADÍSTICAS
    // ============================================

    private function countCitasRango(int $idPsico, string $inicio, string $fin): int
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ? AND estado='activo'");
        $st->execute([$idPsico, $inicio, $fin]);
        return (int) $st->fetchColumn();
    }

    private function ingresosPsicologoRango(int $idPsico, string $inicio, string $fin): float
    {
        $db = (new Pago())->pdo();
        $st = $db->prepare("SELECT COALESCE(SUM(p.monto_total),0) FROM Pago p JOIN Cita c ON c.id=p.id_cita WHERE c.id_psicologo=? AND p.estado_pago='pagado' AND DATE(c.fecha_hora) BETWEEN ? AND ?");
        $st->execute([$idPsico, $inicio, $fin]);
        return (float) $st->fetchColumn();
    }

    private function proximasCitasPendientes(int $idPsico, int $limit): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT c.*, p.nombre as paciente_nombre FROM Cita c LEFT JOIN Paciente p ON p.id=c.id_paciente WHERE c.id_psicologo=? AND c.estado_cita='pendiente' AND DATE(c.fecha_hora) >= CURDATE() AND c.estado='activo' ORDER BY c.fecha_hora ASC LIMIT ?");
        $st->bindValue(1, $idPsico, PDO::PARAM_INT);
        $st->bindValue(2, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function estadisticasUltimos30Dias(int $idPsico): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT estado_cita, COUNT(*) as total FROM Cita WHERE id_psicologo=? AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND estado='activo' GROUP BY estado_cita");
        $st->execute([$idPsico]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function contarPacientesUnicos(int $idPsico): int
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(DISTINCT id_paciente) FROM Cita WHERE id_psicologo=?");
        $st->execute([$idPsico]);
        return (int) $st->fetchColumn();
    }

    private function citasPorMes(int $idPsico, int $meses): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("
            SELECT DATE_FORMAT(fecha_hora, '%Y-%m') as mes, COUNT(*) as total 
            FROM Cita 
            WHERE id_psicologo=? AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY mes 
            ORDER BY mes ASC
        ");
        $st->execute([$idPsico, $meses]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function citasPorEstado(int $idPsico): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT estado_cita, COUNT(*) as total FROM Cita WHERE id_psicologo=? GROUP BY estado_cita");
        $st->execute([$idPsico]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ingresosPorMes(int $idPsico, int $meses): array
    {
        $db = (new Pago())->pdo();
        $st = $db->prepare("
            SELECT DATE_FORMAT(c.fecha_hora, '%Y-%m') as mes, SUM(p.monto_total) as total 
            FROM Pago p 
            JOIN Cita c ON c.id=p.id_cita 
            WHERE c.id_psicologo=? AND p.estado_pago='pagado' AND c.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY mes 
            ORDER BY mes ASC
        ");
        $st->execute([$idPsico, $meses]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function pacientesMasFrecuentes(int $idPsico, int $limit): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("
            SELECT p.nombre, p.dui, COUNT(*) as total_citas, c.id_paciente
            FROM Cita c 
            JOIN Paciente p ON p.id=c.id_paciente 
            WHERE c.id_psicologo=?
            GROUP BY c.id_paciente 
            ORDER BY total_citas DESC 
            LIMIT ?
        ");
        $st->bindValue(1, $idPsico, PDO::PARAM_INT);
        $st->bindValue(2, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function horariosPopulares(int $idPsico): array
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("
            SELECT HOUR(fecha_hora) as hora, COUNT(*) as total 
            FROM Cita 
            WHERE id_psicologo=? AND estado_cita='realizada'
            GROUP BY hora 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $st->execute([$idPsico]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tasaCancelacion(int $idPsico): float
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=?");
        $st->execute([$idPsico]);
        $total = (int) $st->fetchColumn();

        if ($total === 0)
            return 0.0;

        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND estado_cita='cancelada'");
        $st->execute([$idPsico]);
        $canceladas = (int) $st->fetchColumn();

        return ($canceladas / $total) * 100;
    }

    private function promedioIngresoDiario(int $idPsico): float
    {
        $db = (new Pago())->pdo();
        $st = $db->prepare("
            SELECT DATEDIFF(MAX(c.fecha_hora), MIN(c.fecha_hora)) as dias 
            FROM Pago p 
            JOIN Cita c ON c.id=p.id_cita 
            WHERE c.id_psicologo=? AND p.estado_pago='pagado'
        ");
        $st->execute([$idPsico]);
        $dias = (int) $st->fetchColumn();

        if ($dias === 0)
            $dias = 1;

        $ingresos = $this->ingresosPsicologo($idPsico);
        return $ingresos / $dias;
    }

    private function countCitasTotal(int $idPsico): int
    {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=?");
        $st->execute([$idPsico]);
        return (int) $st->fetchColumn();
    }

    public function exportarEstadisticasExcel(): void
    {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();

        require_once __DIR__ . '/../helpers/ExcelHelper.php';

        // Obtener todos los datos
        $citasPorMes = $this->citasPorMes($idPsico, 12);
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);

        // Preparar las secciones del reporte
        $sheets = [];

        // 1. Resumen General
        $sheets['RESUMEN GENERAL'] = [
            'headers' => ['Métrica', 'Valor'],
            'data' => [
                ['Total de Citas', $totalCitas],
                ['Pacientes Únicos', $totalPacientes],
                ['Ingresos Totales', '$' . number_format($ingresoTotal, 2)],
                ['Promedio Ingreso Diario', '$' . number_format($promedioIngresoDiario, 2)],
                ['Tasa de Cancelación', number_format($tasaCancelacion, 2) . '%']
            ]
        ];

        // 2. Citas por Mes
        $dataCitasMes = [];
        foreach ($citasPorMes as $row) {
            $dataCitasMes[] = [$row['mes'], $row['total']];
        }
        $sheets['CITAS POR MES (Últimos 12 meses)'] = [
            'headers' => ['Mes', 'Total de Citas'],
            'data' => $dataCitasMes
        ];

        // 3. Citas por Estado
        $dataCitasEstado = [];
        foreach ($citasPorEstado as $row) {
            $dataCitasEstado[] = [ucfirst($row['estado_cita']), $row['total']];
        }
        $sheets['CITAS POR ESTADO'] = [
            'headers' => ['Estado', 'Total'],
            'data' => $dataCitasEstado
        ];

        // 4. Ingresos por Mes
        $dataIngresos = [];
        foreach ($ingresosPorMes as $row) {
            $dataIngresos[] = [$row['mes'], '$' . number_format($row['total'], 2)];
        }
        $sheets['INGRESOS POR MES'] = [
            'headers' => ['Mes', 'Ingresos'],
            'data' => $dataIngresos
        ];

        // 5. Top 10 Pacientes Frecuentes
        $dataPacientes = [];
        $pos = 1;
        foreach ($pacientesFrecuentes as $pac) {
            $nombre = $pac['nombre'] ?: 'Paciente #' . $pac['id_paciente'];
            $dataPacientes[] = [$pos++, $nombre, $pac['total_citas']];
        }
        $sheets['TOP 10 PACIENTES FRECUENTES'] = [
            'headers' => ['#', 'Nombre del Paciente', 'Total de Citas'],
            'data' => $dataPacientes
        ];

        // 6. Horarios Más Populares
        if (!empty($horariosPopulares)) {
            $dataHorarios = [];
            foreach ($horariosPopulares as $h) {
                $dataHorarios[] = [$h['hora'] . ':00', $h['total']];
            }
            $sheets['HORARIOS MÁS SOLICITADOS'] = [
                'headers' => ['Hora', 'Citas Agendadas'],
                'data' => $dataHorarios
            ];
        }

        // Nombre del archivo con fecha
        $filename = 'Estadisticas_Psicologo_' . date('Y-m-d_His');

        // Intentar exportar a Excel XLSX, si falla usará CSV automáticamente
        ExcelHelper::exportarMultiplesHojas($sheets, $filename, 'Estadísticas Psicólogo');
    }

    public function exportarEstadisticasPDF(): void
    {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();

        require_once __DIR__ . '/../helpers/PDFHelper.php';
        require_once __DIR__ . '/../helpers/ChartHelper.php';

        // Obtener todos los datos
        $citasPorMes = $this->citasPorMes($idPsico, 12);
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);

        // Obtener nombre del psicólogo
        $psico = new Psicologo();
        $dataPsico = $psico->get($idPsico);
        $nombrePsico = $dataPsico['nombre'] ?? 'Psicólogo';

        // Calcular más estadísticas
        $citasRealizadas = 0;
        $citasCanceladas = 0;
        foreach ($citasPorEstado as $row) {
            if ($row['estado_cita'] === 'realizada')
                $citasRealizadas = $row['total'];
            if ($row['estado_cita'] === 'cancelada')
                $citasCanceladas = $row['total'];
        }
        $tasaAsistencia = $totalCitas > 0 ? ($citasRealizadas / $totalCitas) * 100 : 0;

        // Generar HTML para el PDF (HTML 4.01 compatible con DomPDF)
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { color: #2c3e50; font-size: 18px; margin: 5px 0; }
        .header .subtitle { color: #7f8c8d; font-size: 10px; }
        .header .date { color: #95a5a6; font-size: 9px; }
        .summary { background: #ecf0f1; padding: 12px; margin-bottom: 15px; border-radius: 5px; }
        .summary h2 { color: #2c3e50; font-size: 13px; margin: 0 0 10px 0; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .summary-grid { display: table; width: 100%; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; padding: 6px; background: white; margin: 3px; border-left: 3px solid #3498db; }
        .summary-cell:nth-child(even) { background: #f8f9fa; }
        .stat-label { font-weight: bold; color: #2c3e50; font-size: 9px; }
        .stat-value { color: #2980b9; font-size: 12px; font-weight: bold; display: block; margin-top: 3px; }
        h2 { color: #34495e; font-size: 12px; margin-top: 15px; margin-bottom: 8px; border-bottom: 2px solid #3498db; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 8px; }
        th { background: #3498db; color: white; padding: 5px 3px; text-align: left; font-size: 9px; font-weight: bold; }
        td { padding: 4px 3px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 6px; font-size: 8px; font-weight: bold; border-radius: 3px; }
        .badge-success { background: #27ae60; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .highlight { background: #fff3cd; padding: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
        .footer { text-align: center; font-size: 8px; color: #7f8c8d; margin-top: 15px; border-top: 1px solid #ddd; padding-top: 8px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE ESTADISTICO PROFESIONAL</h1>
        <div class="subtitle">Psicologo: ' . htmlspecialchars($nombrePsico, ENT_QUOTES, 'UTF-8') . '</div>
        <div class="date">Generado el ' . date('d/m/Y H:i:s') . '</div>
    </div>
    
    <div class="summary">
        <h2>Resumen Ejecutivo</h2>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">
                    <span class="stat-label">Total de Citas</span>
                    <span class="stat-value">' . (int) $totalCitas . '</span>
                </div>
                <div class="summary-cell">
                    <span class="stat-label">Citas Realizadas</span>
                    <span class="stat-value">' . (int) $citasRealizadas . '</span>
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">
                    <span class="stat-label">Pacientes Unicos</span>
                    <span class="stat-value">' . (int) $totalPacientes . '</span>
                </div>
                <div class="summary-cell">
                    <span class="stat-label">Tasa de Asistencia</span>
                    <span class="stat-value">' . number_format((float) $tasaAsistencia, 1) . '%</span>
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">
                    <span class="stat-label">Ingresos Totales</span>
                    <span class="stat-value">$' . number_format((float) $ingresoTotal, 2) . '</span>
                </div>
                <div class="summary-cell">
                    <span class="stat-label">Ingreso Promedio/Dia</span>
                    <span class="stat-value">$' . number_format((float) $promedioIngresoDiario, 2) . '</span>
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">
                    <span class="stat-label">Tasa de Cancelacion</span>
                    <span class="stat-value">' . number_format((float) $tasaCancelacion, 2) . '%</span>
                </div>
                <div class="summary-cell">
                    <span class="stat-label">Ingreso Promedio/Cita</span>
                    <span class="stat-value">$' . number_format($totalCitas > 0 ? $ingresoTotal / $totalCitas : 0, 2) . '</span>
                </div>
            </div>
        </div>
    </div>
    
    ' . ($tasaCancelacion > 25 ? '<div class="highlight">
        <strong>Nota Importante:</strong> Su tasa de cancelacion (' . number_format($tasaCancelacion, 1) . '%) esta por encima del promedio recomendado (25%). 
        Considere implementar recordatorios o politicas de confirmacion.
    </div>' : '') . '
    
    <h2>Tendencia de Citas (Ultimos 12 meses)</h2>
    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th class="text-center">Citas</th>
                <th class="text-center">% del Total</th>
                <th class="text-right">Tendencia</th>
            </tr>
        </thead>
        <tbody>';

        $maxCitas = !empty($citasPorMes) ? max(array_column($citasPorMes, 'total')) : 1;
        foreach ($citasPorMes as $row) {
            $mes = htmlspecialchars($row['mes'] ?? '', ENT_QUOTES, 'UTF-8');
            $porcentaje = $totalCitas > 0 ? ($row['total'] / $totalCitas) * 100 : 0;
            $barra = str_repeat('█', round(($row['total'] / $maxCitas) * 10));
            $html .= '<tr>
            <td>' . $mes . '</td>
            <td class="text-center"><strong>' . (int) $row['total'] . '</strong></td>
            <td class="text-center">' . number_format($porcentaje, 1) . '%</td>
            <td class="text-right">' . $barra . '</td>
        </tr>';
        }

        $html .= '</tbody>
    </table>
    
    <h2>Distribucion de Citas por Estado</h2>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th class="text-center">Total</th>
                <th class="text-center">Porcentaje</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($citasPorEstado as $row) {
            $porcentaje = $totalCitas > 0 ? ($row['total'] / $totalCitas) * 100 : 0;
            $badgeClass = 'badge';
            if ($row['estado_cita'] === 'realizada')
                $badgeClass .= ' badge-success';
            elseif ($row['estado_cita'] === 'pendiente')
                $badgeClass .= ' badge-warning';
            elseif ($row['estado_cita'] === 'cancelada')
                $badgeClass .= ' badge-danger';

            $estadoTexto = htmlspecialchars(ucfirst($row['estado_cita'] ?? ''), ENT_QUOTES, 'UTF-8');

            $html .= '<tr>
            <td><span class="' . $badgeClass . '">' . $estadoTexto . '</span></td>
            <td class="text-center">' . (int) $row['total'] . '</td>
            <td class="text-center">' . number_format((float) $porcentaje, 1) . '%</td>
        </tr>';
        }

        $html .= '</tbody>
    </table>
    
    <div class="page-break"></div>';

        // ===== GENERAR GRAFICAS CON CHARTHELPER =====

        // 1. Citas por mes (gráfico de barras)
        $mesesLabels = array_column($citasPorMes, 'mes');
        $mesesData = array_map('intval', array_column($citasPorMes, 'total'));
        $chartCitasMes = ChartHelper::generarBarChart($mesesData, $mesesLabels, 'Citas por Mes (Ultimos 12 meses)', 700, 300);

        // 2. Citas por estado (gráfico de pie)
        $estadosLabels = [];
        $estadosData = [];
        foreach ($citasPorEstado as $est) {
            $estadosLabels[] = ucfirst($est['estado_cita']);
            $estadosData[] = (int) $est['total'];
        }
        $chartEstado = ChartHelper::generarPieChart($estadosData, $estadosLabels, 'Distribucion de Citas por Estado', 600, 350);

        // 3. Ingresos por mes (gráfico de líneas)
        $ingresosLabels = array_column($ingresosPorMes, 'mes');
        $ingresosData = array_map('floatval', array_column($ingresosPorMes, 'total'));
        $chartIngresos = ChartHelper::generarLineChart($ingresosData, $ingresosLabels, 'Ingresos Mensuales', 700, 300);

        $html .= '
    <h2>Graficas Estadisticas</h2>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . $chartCitasMes . '" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . $chartEstado . '" style="width: 80%; max-width: 600px; margin-bottom: 15px;">
    </div>
    
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . $chartIngresos . '" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    
    <div class="page-break"></div>
    
    <h2>Analisis de Ingresos Mensuales</h2>
    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th class="text-right">Ingresos</th>
                <th class="text-center">% del Total</th>
                <th class="text-center">vs Promedio</th>
            </tr>
        </thead>
        <tbody>';

        $totalIngresos = array_sum(array_column($ingresosPorMes, 'total'));
        $promedioMensual = count($ingresosPorMes) > 0 ? $totalIngresos / count($ingresosPorMes) : 0;
        foreach ($ingresosPorMes as $row) {
            $mes = htmlspecialchars($row['mes'] ?? '', ENT_QUOTES, 'UTF-8');
            $porcentaje = $totalIngresos > 0 ? ($row['total'] / $totalIngresos) * 100 : 0;
            $vsPromedio = $promedioMensual > 0 ? (($row['total'] - $promedioMensual) / $promedioMensual) * 100 : 0;
            $indicador = $vsPromedio > 0 ? '(+' . number_format($vsPromedio, 0) . '%)' : ($vsPromedio < 0 ? '(' . number_format($vsPromedio, 0) . '%)' : '');
            $html .= '<tr>
            <td>' . $mes . '</td>
            <td class="text-right"><strong>$' . number_format((float) $row['total'], 2) . '</strong></td>
            <td class="text-center">' . number_format($porcentaje, 1) . '%</td>
            <td class="text-center">' . $indicador . '</td>
        </tr>';
        }

        $html .= '</tbody>
    </table>
    
    <h2>Top 10 Pacientes Mas Frecuentes</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Nombre del Paciente</th>
                <th class="text-center">Total Citas</th>
                <th class="text-center">% del Total</th>
                <th class="text-center">Categoria</th>
            </tr>
        </thead>
        <tbody>';

        $pos = 1;
        foreach ($pacientesFrecuentes as $pac) {
            $nombre = $pac['nombre'] ? htmlspecialchars($pac['nombre'], ENT_QUOTES, 'UTF-8') : 'Paciente #' . (int) $pac['id_paciente'];
            $porcentaje = $totalCitas > 0 ? ($pac['total_citas'] / $totalCitas) * 100 : 0;
            $categoria = $pac['total_citas'] >= 5 ? 'Frecuente' : ($pac['total_citas'] >= 3 ? 'Regular' : 'Ocasional');
            $html .= '<tr>
            <td class="text-center"><strong>' . $pos++ . '</strong></td>
            <td>' . $nombre . '</td>
            <td class="text-center">' . (int) $pac['total_citas'] . '</td>
            <td class="text-center">' . number_format($porcentaje, 1) . '%</td>
            <td class="text-center">' . $categoria . '</td>
        </tr>';
        }

        $html .= '</tbody>
    </table>';

        if (!empty($horariosPopulares)) {
            $html .= '
    <h2>Horarios Mas Solicitados</h2>
    <table>
        <thead>
            <tr>
                <th>Hora</th>
                <th class="text-center">Citas Agendadas</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($horariosPopulares as $h) {
                $html .= '<tr>
            <td>' . (int) $h['hora'] . ':00</td>
            <td class="text-center">' . (int) $h['total'] . '</td>
        </tr>';
            }

            $html .= '</tbody>
    </table>';
        }

        $html .= '
    
    <div class="footer">
        <strong>Sistema de Gestion de Consultorio Psicologico</strong><br>
        Reporte Profesional Confidencial - ' . htmlspecialchars($nombrePsico, ENT_QUOTES, 'UTF-8') . '<br>
        Total de Registros: ' . $totalCitas . ' citas | ' . $totalPacientes . ' pacientes unicos<br>
        Generado: ' . date('d/m/Y H:i:s') . '
    </div>
</body>
</html>';

        // Generar PDF (PDFHelper agrega .pdf automáticamente)
        $filename = 'Reporte_' . preg_replace('/[^A-Za-z0-9_]/', '_', $nombrePsico) . '_' . date('Ymd_His');
        PDFHelper::generarPDF($html, $filename, true);
    }
}
