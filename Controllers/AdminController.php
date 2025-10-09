<?php
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Psicologo.php';
require_once __DIR__ . '/../Models/Cita.php';
require_once __DIR__ . '/../Models/Pago.php';
require_once __DIR__ . '/../Models/HorarioPsicologo.php';
require_once __DIR__ . '/../Models/SolicitudCambio.php';
require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController
{
    private string $viewsPath;

    public function __construct()
    {
        $this->viewsPath = __DIR__ . '/../Views/admin/';
    }

    protected function requireAdmin(): void
    {
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado</div>';
            exit;
        }
    }

    protected function render($vista, $data = [])
    {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista admin faltante: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }

    public function dashboard(): void
    {
        $this->requireAdmin();
        // Defaults to avoid undefined variable warnings (will be overwritten)
        $usuariosCounts = ['activo' => 0, 'inactivo' => 0];
        $citaStats = [];
        $totalPacientes = 0;
        $pacientesActivos = 0;
        $citasMes = 0;
        $citasPendientes = 0;
        $ingresosMes = 0.0;
        $pagosPagados = 0;
        $proximasCitas = [];
        $pagosPendientesLista = [];
        // Inicializaciones restauradas (se habían perdido en un refactor previo)
        $usuarioModel = new Usuario();
        $pacienteModel = new Paciente();
        $citaModel = new Cita();
        $pagoModel = new Pago();

        // Contadores básicos
        $usuariosCounts = $usuarioModel->conteoActivosInactivos();
        $citaStats = $citaModel->estadisticasEstado();

        // Pacientes
        $listaPacientes = method_exists($pacienteModel, 'listarTodos') ? $pacienteModel->listarTodos() : [];
        $totalPacientes = count($listaPacientes);
        $pacientesActivos = count(array_filter($listaPacientes, fn($p) => ($p['estado'] ?? 'activo') === 'activo'));

        // Citas del mes actual
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');
        $todasCitasMes = method_exists($citaModel, 'citasPorRango') ? $citaModel->citasPorRango($inicioMes, $finMes) : [];
        $citasMes = count($todasCitasMes);
        $citasPendientes = count(array_filter($todasCitasMes, fn($c) => ($c['estado_cita'] ?? '') === 'pendiente'));

        // Ingresos del mes actual (a partir de ingresosPorMes del modelo Pago)
        $ingresosMesData = $pagoModel->ingresosPorMes((int) date('Y'));
        $mesActual = (int) date('m');
        $ingresosMes = 0.0;
        foreach ($ingresosMesData as $im) {
            if ((int) $im['mes'] === $mesActual || (int) substr($im['mes'], 0, 2) === $mesActual) { // soporta formatos '05' o '05-2025'
                $ingresosMes = (float) $im['total'];
                break;
            }
        }

        // Pagos pagados totales
        $todosPagos = method_exists($pagoModel, 'listarTodos') ? $pagoModel->listarTodos() : [];
        $pagosPagados = count(array_filter($todosPagos, fn($p) => ($p['estado_pago'] ?? '') === 'pagado'));

        // Próximas citas (5 próximas pendientes)
        $pdo = $citaModel->pdo();
        $stProx = $pdo->query("SELECT c.*, pac.nombre AS paciente_nombre, u.nombre AS psicologo_nombre
                                FROM Cita c
                                LEFT JOIN Paciente pac ON pac.id = c.id_paciente
                                LEFT JOIN Psicologo ps ON ps.id = c.id_psicologo
                                LEFT JOIN Usuario u ON u.id = ps.id_usuario
                                WHERE c.fecha_hora >= NOW() AND c.estado_cita='pendiente'
                                ORDER BY c.fecha_hora ASC LIMIT 5");
        $proximasCitas = $stProx ? $stProx->fetchAll(PDO::FETCH_ASSOC) : [];

        // Pagos pendientes (5 más recientes)
        $stPend = $pdo->query("SELECT p.*, pac.nombre AS paciente_nombre
                               FROM Pago p
                               LEFT JOIN Cita c ON c.id = p.id_cita
                               LEFT JOIN Paciente pac ON pac.id = c.id_paciente
                               WHERE p.estado_pago='pendiente'
                               ORDER BY p.fecha DESC LIMIT 5");
        $pagosPendientesLista = $stPend ? $stPend->fetchAll(PDO::FETCH_ASSOC) : [];
        $this->render('dashboard', [
            'usuariosCounts' => $usuariosCounts,
            'citaStats' => $citaStats,
            'totalPacientes' => $totalPacientes,
            'pacientesActivos' => $pacientesActivos,
            'citasMes' => $citasMes,
            'citasPendientes' => $citasPendientes,
            'ingresosMes' => $ingresosMes,
            'pagosPagados' => $pagosPagados,
            'proximasCitas' => $proximasCitas,
            'pagosPendientesLista' => $pagosPendientesLista
        ]);
    }

    /* ================= Usuarios ================= */
    public function usuarios(): void
    {
        $this->requireAdmin();
        $usuarioModel = new Usuario();
        $msg = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            if ($accion === 'crear') {
                $nombre = trim($_POST['nombre'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $rol = $_POST['rol'] ?? 'paciente';
                $pass = $_POST['password'] ?? '';
                if ($nombre && $email && $pass) {
                    $usuarioModel->crear(['nombre' => $nombre, 'email' => $email, 'password' => $pass, 'rol' => $rol]);
                } else {
                    $msg = 'Datos incompletos';
                }
            }
            if ($accion === 'estado') {
                $id = (int) ($_POST['id'] ?? 0);
                $estado = $_POST['estado'] ?? '';
                $usuarioModel->cambiarEstado($id, $estado);
            }
            if ($accion === 'reset') {
                $id = (int) ($_POST['id'] ?? 0);
                $usuarioModel->resetPassword($id, 'Temp1234');
            }
        }
        $usuarios = $usuarioModel->listarTodos();
        $this->render('usuarios', ['usuarios' => $usuarios, 'msg' => $msg]);
    }

    /* ================ Psicologos ================ */
    public function psicologos(): void
    {
        $this->requireAdmin();
        $psModel = new Psicologo();
        $usrModel = new Usuario();
        require_once __DIR__ . '/../Models/Especialidad.php';
        $espModel = new Especialidad();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            try {
                switch ($accion) {
                    case 'crear':
                        $nombre = trim($_POST['nombre'] ?? '');
                        $email = trim($_POST['email'] ?? '');
                        $password = $_POST['password'] ?? '';
                        $idEspecialidad = (int) ($_POST['id_especialidad'] ?? 0);
                        if (!$nombre || !$email || !$password || !$idEspecialidad) {
                            throw new Exception('Complete todos los campos requeridos');
                        }
                        $idU = $usrModel->crear([
                            'nombre' => $nombre,
                            'email' => $email,
                            'password' => $password,
                            'rol' => 'psicologo'
                        ]);
                        $psModel->crear($idU, [
                            'id_especialidad' => $idEspecialidad,
                            'experiencia' => trim($_POST['experiencia'] ?? ''),
                            'horario' => trim($_POST['horario'] ?? '')
                        ]);
                        $_SESSION['flash_ok'] = 'Psicólogo creado correctamente';
                        break;
                    case 'editar':
                        $idPs = (int) ($_POST['id'] ?? 0);
                        $idU = (int) ($_POST['id_usuario'] ?? 0);
                        $idEspecialidad = (int) ($_POST['id_especialidad'] ?? 0);
                        if (!$idPs || !$idU || !$idEspecialidad) {
                            throw new Exception('Datos incompletos para editar');
                        }
                        $usrModel->actualizar($idU, [
                            'nombre' => trim($_POST['nombre'] ?? ''),
                            'email' => trim($_POST['email'] ?? '')
                        ]);
                        if (($np = trim($_POST['new_password'] ?? '')) !== '') {
                            $usrModel->actualizarPassword($idU, $np);
                        }
                        $psModel->actualizar($idPs, [
                            'id_especialidad' => $idEspecialidad,
                            'experiencia' => trim($_POST['experiencia'] ?? ''),
                            'horario' => trim($_POST['horario'] ?? '')
                        ]);
                        $_SESSION['flash_ok'] = 'Psicólogo actualizado';
                        break;
                    case 'estado':
                        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
                        $estado = $_POST['estado'] ?? '';
                        if (!$idUsuario || !$estado) {
                            throw new Exception('Datos inválidos para cambiar estado');
                        }
                        $usrModel->cambiarEstado($idUsuario, $estado);
                        $_SESSION['flash_ok'] = 'Estado actualizado';
                        break;
                    default:
                        throw new Exception('Acción no reconocida');
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
            // Redirección segura para evitar warnings de headers ya enviados
            $this->safeRedirect(url('admin', 'psicologos'));
        }
        $psicologos = $psModel->listarTodos();
        $especialidades = $espModel->listarActivas();
        $masSolic = method_exists($psModel, 'masSolicitados') ? $psModel->masSolicitados() : [];
        // Enriquecer masSolic con nombres de psicólogos
        if (!empty($masSolic)) {
            // crear mapa id -> nombre
            $psTodos = $psModel->listarTodos();
            $map = [];
            foreach ($psTodos as $pp) {
                $map[(int) $pp['id']] = $pp['nombre'] ?? ($pp['nombre'] ?? null);
            }
            foreach ($masSolic as $k => $m) {
                $id = (int) ($m['id_psicologo'] ?? $m['id'] ?? 0);
                $masSolic[$k]['nombre'] = $map[$id] ?? ($masSolic[$k]['nombre'] ?? null);
            }
        }
        $this->render('psicologos', [
            'psicologos' => $psicologos,
            'especialidades' => $especialidades,
            'masSolic' => $masSolic,
            'error' => $_SESSION['flash_error'] ?? '',
            'ok' => $_SESSION['flash_ok'] ?? ''
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_ok']);
    }

    public function pacientes(): void
    {
        $this->requireAdmin();
        $pacModel = new Paciente();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            // Normaliza teléfono y DUI
            $tel = isset($_POST['telefono']) ? preg_replace('/\D/', '', $_POST['telefono']) : '';
            if (strlen($tel) === 8)
                $tel = substr($tel, 0, 4) . '-' . substr($tel, 4);

            // DUI: formato 8-1 (########-#)
            $dui = isset($_POST['dui']) ? preg_replace('/\D/', '', $_POST['dui']) : '';
            if (strlen($dui) === 9)
                $dui = substr($dui, 0, 8) . '-' . substr($dui, 8);

            $fecha = $_POST['fecha_nacimiento'] ?? '';
            if ($fecha) {
                $hoy = date('Y-m-d');
                if ($fecha > $hoy)
                    $fecha = $hoy;
                if ($fecha < '1900-01-01')
                    $fecha = '1900-01-01';
            }
            $dataBase = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => $tel,
                'direccion' => trim($_POST['direccion'] ?? ''),
                'dui' => $dui,
                'fecha_nacimiento' => $fecha,
                'genero' => $_POST['genero'] ?? '',
                'historial_clinico' => trim($_POST['historial_clinico'] ?? '')
            ];
            try {
                if ($accion === 'crear') {
                    $pacModel->crear($dataBase);
                    $_SESSION['flash_pac_ok'] = 'Paciente creado correctamente';
                } elseif ($accion === 'editar') {
                    $pacModel->actualizar((int) $_POST['id'], $dataBase);
                    $_SESSION['flash_pac_ok'] = 'Paciente actualizado';
                } elseif ($accion === 'estado') {
                    $pacModel->cambiarEstado((int) $_POST['id'], $_POST['estado']);
                    $_SESSION['flash_pac_ok'] = 'Estado actualizado';
                } elseif ($accion === 'regen_code') {
                    $pacModel->regenerarCodigoAcceso((int) $_POST['id']);
                    $_SESSION['flash_pac_ok'] = 'Código regenerado';
                }
            } catch (InvalidArgumentException $e) {
                $_SESSION['flash_pac_error'] = $e->getMessage();
            } catch (Throwable $e) {
                $_SESSION['flash_pac_error'] = 'Error: ' . $e->getMessage();
            }
            $redir = url('admin', 'pacientes');
            $this->safeRedirect($redir);
        }
        $lista = $pacModel->listarTodos();
        $this->render('pacientes', [
            'pacientes' => $lista,
            'flash_ok' => $_SESSION['flash_pac_ok'] ?? '',
            'flash_error' => $_SESSION['flash_pac_error'] ?? ''
        ]);
        unset($_SESSION['flash_pac_ok'], $_SESSION['flash_pac_error']);
    }

    /* ================== Citas =================== */
    public function citas(): void
    {
        $this->requireAdmin();
        $citaModel = new Cita();
        $psModel = new Psicologo();
        // JSON fetch (AJAX list)
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
            // Ensure all buffers are cleared to avoid layout/html contamination
            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $citas = $this->filtrarCitasAdmin($citaModel);
                echo json_encode(['citas' => $citas]);
                exit;
            } catch (Throwable $e) {
                error_log('Error en AJAX list citas: ' . $e->getMessage());
                echo json_encode(['error' => 'Error al cargar citas: ' . $e->getMessage(), 'citas' => []]);
                exit;
            }
        }
        // AJAX evaluaciones - obtener evaluaciones de una cita
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'evaluaciones') {
            // Ensure all buffers are cleared to avoid layout/html contamination
            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $idCita = (int) ($_GET['id_cita'] ?? 0);
                if (!$idCita) {
                    echo json_encode(['error' => 'ID de cita no válido']);
                    exit;
                }
                require_once __DIR__ . '/../Models/Evaluacion.php';
                $evalModel = new Evaluacion();
                $evaluaciones = $evalModel->obtenerPorCita($idCita);
                echo json_encode(['evaluaciones' => $evaluaciones]);
                exit;
            } catch (Throwable $e) {
                error_log('Error en AJAX evaluaciones: ' . $e->getMessage());
                echo json_encode(['error' => 'Error al cargar evaluaciones: ' . $e->getMessage()]);
                exit;
            }
        }
        // AJAX slots para un psicólogo destino en fecha dada (para reasignar)
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'slots') {
            // Hardened response: always return valid JSON, avoid stray output
            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            $resp = ['ps' => 0, 'fecha' => '', 'slots' => []];
            try {
                $idPs = (int) ($_GET['ps'] ?? 0);
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $interval = 30; // fijo
                $resp['ps'] = $idPs;
                $resp['fecha'] = $fecha;

                if (!$idPs || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    echo json_encode($resp);
                    exit;
                }

                // Obtener bloques horario (similar a PsicologoController::proximosSlots)
                $diaMap = ['Mon' => 'lunes', 'Tue' => 'martes', 'Wed' => 'miércoles', 'Thu' => 'jueves', 'Fri' => 'viernes', 'Sat' => 'sábado', 'Sun' => 'domingo'];
                $dt = DateTime::createFromFormat('Y-m-d', $fecha);
                if (!$dt) {
                    echo json_encode($resp);
                    exit;
                }

                $diaBD = $diaMap[$dt->format('D')] ?? 'lunes';
                $variants = [$diaBD];
                if ($diaBD === 'miércoles')
                    $variants[] = 'miercoles';
                if ($diaBD === 'sábado')
                    $variants[] = 'sabado';

                $placeholders = implode(',', array_fill(0, count($variants), '?'));
                $pdo = $citaModel->pdo();

                $stH = $pdo->prepare("SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana IN ($placeholders) ORDER BY hora_inicio");
                $stH->execute(array_merge([$idPs], $variants));
                $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);

                if (!$bloques) {
                    $resp['message'] = 'Sin horarios configurados para este psicólogo en ' . $diaBD;
                    echo json_encode($resp);
                    exit;
                }

                $stC = $pdo->prepare("SELECT fecha_hora FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora)=? AND estado='activo'");
                $stC->execute([$idPs, $fecha]);
                $ocup = [];
                foreach ($stC->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $ocup[substr($r['fecha_hora'], 11, 5)] = true;
                }

                $slots = [];
                $now = new DateTime();
                $isToday = $fecha === $now->format('Y-m-d');

                foreach ($bloques as $b) {
                    $ini = new DateTime($fecha . ' ' . $b['hora_inicio']);
                    $fin = new DateTime($fecha . ' ' . $b['hora_fin']);
                    while ($ini < $fin) {
                        $h = $ini->format('H:i');
                        if (!isset($ocup[$h]) && (!$isToday || $ini > $now)) {
                            $slots[] = $h;
                        }
                        $ini->modify('+' . $interval . ' minutes');
                    }
                }

                sort($slots);
                $resp['slots'] = $slots;
                $resp['dia'] = $diaBD;
                $resp['bloques_count'] = count($bloques);
                $resp['ocupadas_count'] = count($ocup);

                echo json_encode($resp);
                exit;
            } catch (Throwable $e) {
                // Capture any error and return JSON with message
                error_log('Error en AJAX slots: ' . $e->getMessage());
                echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $op = $_POST['op'] ?? '';
            $id = (int) ($_POST['id'] ?? 0);
            try {
                switch ($op) {
                    case 'cancelar':
                        $motivo = trim($_POST['motivo'] ?? '');
                        if ($id && $motivo) {
                            // Verificar que no tenga evaluaciones
                            require_once __DIR__ . '/../Models/Evaluacion.php';
                            $evalModel = new Evaluacion();
                            $countEval = $evalModel->contarPorCita($id);

                            if ($countEval > 0) {
                                throw new Exception('No se puede cancelar una cita que ya tiene evaluaciones registradas.');
                            }

                            // Cancelar y liberar horario
                            $pdo = $citaModel->pdo();
                            $st = $pdo->prepare("UPDATE Cita SET estado_cita='cancelada', estado='inactivo', motivo_consulta=CONCAT(motivo_consulta, '\n[CANCELADA] ', ?) WHERE id=?");
                            $st->execute([$motivo, $id]);
                        }
                        break;
                    case 'reprogramar':
                        $fh = trim($_POST['fecha_hora'] ?? '');
                        if ($id && $fh) {
                            // Mantener formato y validar minutos 00/30
                            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $fh) ?: DateTime::createFromFormat('Y-m-d H:i', $fh);
                            if (!$dt)
                                throw new Exception('Formato fecha/hora inválido');
                            $m = (int) $dt->format('i');
                            if ($m !== 0 && $m !== 30)
                                throw new Exception('Minutos deben ser 00 ó 30');
                            $citaModel->reprogramar($id, $dt->format('Y-m-d H:i:00'));
                        }
                        break;
                    case 'reasignar':
                        $ps = (int) ($_POST['id_psicologo'] ?? 0);
                        $fhSel = trim($_POST['fecha_hora'] ?? ''); // nuevo slot seleccionado
                        if ($id && $ps && $fhSel) {
                            $cita = $citaModel->obtener($id);
                            if (!$cita)
                                throw new Exception('Cita no encontrada');
                            if ($cita['estado_cita'] === 'realizada')
                                throw new Exception('No se puede reasignar una cita realizada');
                            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fhSel) ?: DateTime::createFromFormat('Y-m-d H:i', $fhSel);
                            if (!$dt)
                                throw new Exception('Fecha/hora nueva inválida');
                            $m = (int) $dt->format('i');
                            if ($m !== 0 && $m !== 30)
                                throw new Exception('Minutos deben ser 00 o 30');
                            $fhFmt = $dt->format('Y-m-d H:i:00');
                            if (!$this->psicologoDisponible($citaModel, $ps, $fhFmt, 0))
                                throw new Exception('Destino ocupado en ese horario');
                            // Actualizar psicólogo y fecha/hora
                            $pdo = $citaModel->pdo();
                            $st = $pdo->prepare("UPDATE Cita SET id_psicologo=?, fecha_hora=?, estado_cita='pendiente' WHERE id=?");
                            $st->execute([$ps, $fhFmt, $id]);
                        }
                        break;
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
            $this->safeRedirect(url('admin', 'citas'));
        }

        $psicologos = method_exists($psModel, 'listarActivos')
            ? $psModel->listarActivos()
            : (method_exists($psModel, 'listarTodos') ? $psModel->listarTodos() : []);

        $citas = $this->filtrarCitasAdmin($citaModel);
        $this->render('citas', ['citas' => $citas, 'psicologos' => $psicologos]);
    }

    // Aplica filtros GET: estado, fecha, texto (id, motivo), psicologo
    private function filtrarCitasAdmin(Cita $citaModel): array
    {
        // Prefer a detailed listing that includes joined patient/psychologist names
        if (method_exists($citaModel, 'citasPorRangoDetallado')) {
            // Use a wide range to include all recent records (same intent as previous citasPorRango usage)
            $base = $citaModel->citasPorRangoDetallado(date('Y-m-01') . ' 00:00:00', '2999-12-31 23:59:59');
        } elseif (method_exists($citaModel, 'todas')) {
            // Fallback: todas() already returns paciente_nombre and psicologo_nombre for active records
            $base = $citaModel->todas();
        } elseif (method_exists($citaModel, 'citasPorRango')) {
            // Last resort: use citasPorRango (may not include joined names)
            $base = $citaModel->citasPorRango(date('Y-m-01'), '2999-12-31');
        } else {
            $base = [];
        }
        $estado = $_GET['estado'] ?? '';
        $fecha = $_GET['fecha'] ?? '';
        $texto = strtolower(trim($_GET['texto'] ?? ''));
        $ps = (int) ($_GET['ps'] ?? 0);

        // Filtrar citas
        $citasFiltradas = array_values(array_filter($base, function ($c) use ($estado, $fecha, $texto, $ps) {
            if ($estado && $c['estado_cita'] !== $estado)
                return false;
            if ($fecha && substr($c['fecha_hora'], 0, 10) !== $fecha)
                return false;
            if ($ps && (int) $c['id_psicologo'] !== $ps)
                return false;
            if ($texto) {
                $hay = false;
                if (strpos((string) $c['id'], $texto) !== false)
                    $hay = true;
                elseif (isset($c['motivo_consulta']) && strpos(strtolower($c['motivo_consulta']), $texto) !== false)
                    $hay = true;
                elseif (strpos((string) $c['id_paciente'], $texto) !== false)
                    $hay = true;
                if (!$hay)
                    return false;
            }
            return true;
        }));

        // Agregar conteo de evaluaciones a cada cita
        require_once __DIR__ . '/../Models/Evaluacion.php';
        $evalModel = new Evaluacion();
        foreach ($citasFiltradas as $key => $cita) {
            // Normalize name fields so the view can display names regardless of source
            if (isset($cita['paciente']) && !isset($cita['paciente_nombre'])) {
                $citasFiltradas[$key]['paciente_nombre'] = $cita['paciente'];
            }
            if (isset($cita['psicologo']) && !isset($cita['psicologo_nombre'])) {
                $citasFiltradas[$key]['psicologo_nombre'] = $cita['psicologo'];
            }
            // Older methods may already include paciente_nombre / psicologo_nombre
            $citasFiltradas[$key]['count_evaluaciones'] = $evalModel->contarPorCita((int) $cita['id']);
        }

        return $citasFiltradas;
    }

    private function psicologoDisponible(Cita $citaModel, int $idPs, string $fechaHora, int $excluirId = 0): bool
    {
        // Checar si ya existe cita en ese horario exacto
        $pdo = $citaModel->pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND fecha_hora=? AND id<>? AND estado='activo'");
        $st->execute([$idPs, $fechaHora, $excluirId]);
        return (int) $st->fetchColumn() === 0;
    }

    /* ================== Pagos =================== */
    public function pagos(): void
    {
        $this->requireAdmin();
        $pagoModel = new Pago();
        $pendientes = $pagoModel->listarPendientes();
        $ingresosMes = $pagoModel->ingresosPorMes((int) date('Y'));
        $ingPorPsico = $pagoModel->ingresosPorPsicologo();
        $this->render('pagos', ['pendientes' => $pendientes, 'ingresosMes' => $ingresosMes, 'ingPorPsico' => $ingPorPsico]);
    }

    /* ================== Tickets =================== */
    public function tickets(): void
    {
        $this->requireAdmin();
        require_once __DIR__ . '/../Models/TicketPago.php';
        $ticketModel = new TicketPago();
        $tickets = $ticketModel->listarTodos();
        $this->render('tickets', ['tickets' => $tickets]);
    }

    /** Buscar citas (JSON) usado por la UI admin para generar pagos pendientes */
    public function buscarCitas(): void
    {
        $this->requireAdmin();
        $q = trim($_GET['q'] ?? '');
        $id = (int) ($_GET['id'] ?? 0);
        $fecha = $_GET['fecha'] ?? '';
        $ps = $_GET['ps'] ?? '';

        $citaModel = new Cita();
        $pdo = $citaModel->pdo();

        $where = [];
        $params = [];
        if ($id) {
            $where[] = 'c.id = ?';
            $params[] = $id;
        }
        if ($fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $where[] = 'DATE(c.fecha_hora) = ?';
            $params[] = $fecha;
        }
        if ($ps) {
            // allow numeric id or text name
            if (is_numeric($ps)) {
                $where[] = 'c.id_psicologo = ?';
                $params[] = (int) $ps;
            } else {
                $where[] = 'u.nombre LIKE ?';
                $params[] = "%$ps%";
            }
        }
        if ($q) {
            $where[] = '(p.nombre LIKE ? OR p.dui LIKE ? OR c.motivo_consulta LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $sql = "SELECT c.id, c.fecha_hora, c.id_paciente, COALESCE(p.nombre, '') paciente_nombre, c.id_psicologo, COALESCE(u.nombre,'') psicologo_nombre
                FROM Cita c
                LEFT JOIN Paciente p ON p.id = c.id_paciente
                LEFT JOIN Psicologo ps ON ps.id = c.id_psicologo
                LEFT JOIN Usuario u ON u.id = ps.id_usuario
                " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
                ORDER BY c.fecha_hora DESC LIMIT 50";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // Return JSON
        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['results' => $rows]);
        exit;
    }

    /* ================== Horarios Psicólogos =================== */
    public function horarios(): void
    {
        $this->requireAdmin();
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
        $psM = new Psicologo();
        $hM = new HorarioPsicologo();
        $msg = '';
        $err = '';
        $idSelGet = (int) ($_GET['ps'] ?? 0); // conservar selección actual en GET
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            try {
                if ($accion === 'crear') {
                    $idPs = (int) ($_POST['id_psicologo'] ?? 0);
                    $dia = $_POST['dia_semana'] ?? '';
                    $ini = $_POST['hora_inicio'] ?? '';
                    $fin = $_POST['hora_fin'] ?? '';
                    $hM->crear($idPs, $dia, $ini, $fin);
                    $msg = 'Horario agregado';
                    $idSelGet = $idPs; // mantener selección
                } elseif ($accion === 'eliminar') {
                    $idH = (int) ($_POST['id_horario'] ?? 0);
                    $idPs = (int) ($_POST['id_psicologo'] ?? 0);
                    if ($idPs) {
                        $idSelGet = $idPs;
                    }
                    if ($idH) {
                        $hM->eliminar($idH);
                        $msg = 'Horario eliminado';
                    }
                }
            } catch (Throwable $e) {
                $err = $e->getMessage();
            }
            // Redirigir conservando psicólogo seleccionado
            $base = url('admin', 'horarios', $idSelGet ? ['ps' => $idSelGet] : []);
            $this->safeRedirect($base . ($err ? '&err=' . urlencode($err) : '&ok=1'));
        }
        $psicologos = $psM->listarTodos();
        $idSel = $idSelGet;
        $horarios = $idSel ? $hM->listarPorPsicologo($idSel) : [];
        $this->render('horarios', ['psicologos' => $psicologos, 'horarios' => $horarios, 'idSel' => $idSel]);
    }

    /* =============== Solicitudes de Cambio =============== */
    public function solicitudes(): void
    {
        $this->requireAdmin();

        $model = new SolicitudCambio();

        // Filtros
        $estadoFiltro = $_GET['estado'] ?? 'pendiente';
        $campoFiltro = $_GET['campo'] ?? '';
        $buscarDui = $_GET['buscar'] ?? '';
        $orden = $_GET['orden'] ?? 'DESC'; // DESC = más recientes primero

        // Obtener solicitudes con filtros
        $solicitudes = $model->listarConFiltros($estadoFiltro, $campoFiltro, $buscarDui, $orden);

        $this->render('solicitudes', [
            'solicitudes' => $solicitudes,
            'estadoFiltro' => $estadoFiltro,
            'campoFiltro' => $campoFiltro,
            'buscarDui' => $buscarDui,
            'orden' => $orden
        ]);
    }

    public function procesarSolicitud(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->safeRedirect(RUTA . 'admin/solicitudes');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $idPaciente = (int) ($_POST['id_paciente'] ?? 0);
        $campo = $_POST['campo_original'] ?? '';
        $valorNuevo = $_POST['valor_nuevo'] ?? '';

        if (!$id || !in_array($accion, ['aprobar', 'rechazar'])) {
            $_SESSION['msg_solicitud'] = 'Acción inválida';
            $_SESSION['msg_tipo'] = 'danger';
            $this->safeRedirect(RUTA . 'admin/solicitudes');
            return;
        }

        $solicitudModel = new SolicitudCambio();
        $pacienteModel = new Paciente();

        // Cambiar estado de la solicitud
        $nuevoEstado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
        $solicitudModel->actualizarEstado($id, $nuevoEstado);

        // Si se aprueba, actualizar el paciente
        if ($accion === 'aprobar' && $idPaciente && $campo && $valorNuevo) {
            $pacienteModel->actualizarCampo($idPaciente, $campo, $valorNuevo);
            $_SESSION['msg_solicitud'] = "Solicitud #$id aprobada y datos del paciente actualizados correctamente";
            $_SESSION['msg_tipo'] = 'success';
        } elseif ($accion === 'rechazar') {
            $_SESSION['msg_solicitud'] = "Solicitud #$id rechazada";
            $_SESSION['msg_tipo'] = 'warning';
        }

        $this->safeRedirect(RUTA . 'admin/solicitudes');
    }

    // safeRedirect heredado de BaseController

    /* ================== Estadísticas =================== */
    public function estadisticas(): void
    {
        $this->requireAdmin();

        // Filtros
        $anio = (int) ($_GET['anio'] ?? date('Y'));
        $mes = $_GET['mes'] ?? '';
        $psicologoFiltro = (int) ($_GET['psicologo'] ?? 0);

        // Exportación directa
        if (isset($_GET['export'])) {
            $this->exportarEstadisticas($_GET['export'], $anio, $mes, $psicologoFiltro);
            return;
        }

        // Modelos
        $citaModel = new Cita();
        $pagoModel = new Pago();
        $psicologoModel = new Psicologo();
        $pacienteModel = new Paciente();
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
        $horarioModel = new HorarioPsicologo();

        $pdo = $citaModel->pdo();

        // Construcción de WHERE para filtros
        $where = ["YEAR(c.fecha_hora) = ?"];
        $params = [$anio];

        if ($mes) {
            $where[] = "MONTH(c.fecha_hora) = ?";
            $params[] = (int) $mes;
        }
        if ($psicologoFiltro) {
            $where[] = "c.id_psicologo = ?";
            $params[] = $psicologoFiltro;
        }

        $whereSQL = implode(' AND ', $where);

        // Estadísticas generales
        $stats = [
            'total_citas' => 0,
            'citas_realizadas' => 0,
            'citas_pendientes' => 0,
            'ingresos_totales' => 0
        ];

        $stStats = $pdo->prepare("
            SELECT 
                COUNT(c.id) as total,
                SUM(CASE WHEN c.estado_cita = 'realizada' THEN 1 ELSE 0 END) as realizadas,
                SUM(CASE WHEN c.estado_cita = 'pendiente' THEN 1 ELSE 0 END) as pendientes
            FROM Cita c
            WHERE $whereSQL
        ");
        $stStats->execute($params);
        $r = $stStats->fetch(PDO::FETCH_ASSOC);
        $stats['total_citas'] = $r['total'] ?? 0;
        $stats['citas_realizadas'] = $r['realizadas'] ?? 0;
        $stats['citas_pendientes'] = $r['pendientes'] ?? 0;

        // Ingresos totales
        $stIngresos = $pdo->prepare("
            SELECT COALESCE(SUM(p.monto_total), 0) as total
            FROM Pago p
            JOIN Cita c ON p.id_cita = c.id
            WHERE $whereSQL AND p.estado_pago = 'pagado'
        ");
        $stIngresos->execute($params);
        $stats['ingresos_totales'] = $stIngresos->fetchColumn();

        // Citas por mes
        $stCitasMes = $pdo->prepare("
            SELECT DATE_FORMAT(c.fecha_hora, '%m-%Y') as mes, COUNT(*) as total
            FROM Cita c
            WHERE YEAR(c.fecha_hora) = ?
            " . ($psicologoFiltro ? "AND c.id_psicologo = ?" : "") . "
            GROUP BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
            ORDER BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
        ");
        $paramsMes = [$anio];
        if ($psicologoFiltro)
            $paramsMes[] = $psicologoFiltro;
        $stCitasMes->execute($paramsMes);
        $citasPorMes = $stCitasMes->fetchAll(PDO::FETCH_ASSOC);

        // Citas por estado
        $stEstado = $pdo->prepare("
            SELECT estado_cita as estado, COUNT(*) as total
            FROM Cita c
            WHERE $whereSQL
            GROUP BY estado_cita
        ");
        $stEstado->execute($params);
        $citasPorEstado = $stEstado->fetchAll(PDO::FETCH_ASSOC);

        // Ingresos por mes
        $stIngresosMes = $pdo->prepare("
            SELECT DATE_FORMAT(c.fecha_hora, '%m-%Y') as mes, COALESCE(SUM(p.monto_total), 0) as total
            FROM Pago p
            JOIN Cita c ON p.id_cita = c.id
            WHERE YEAR(c.fecha_hora) = ?
            " . ($psicologoFiltro ? "AND c.id_psicologo = ?" : "") . "
              AND p.estado_pago = 'pagado'
            GROUP BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
            ORDER BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
        ");
        $stIngresosMes->execute($paramsMes);
        $ingresosPorMes = $stIngresosMes->fetchAll(PDO::FETCH_ASSOC);

        // Top 10 Psicólogos
        $stTopPs = $pdo->prepare("
            SELECT 
                ps.id,
                u.nombre,
                e.nombre as especialidad,
                COUNT(c.id) as total_citas,
                COALESCE(SUM(p.monto_total), 0) as ingresos
            FROM Psicologo ps
            JOIN Usuario u ON ps.id_usuario = u.id
            LEFT JOIN Especialidad e ON ps.id_especialidad = e.id
            LEFT JOIN Cita c ON ps.id = c.id_psicologo AND $whereSQL
            LEFT JOIN Pago p ON c.id = p.id_cita AND p.estado_pago = 'pagado'
            GROUP BY ps.id, u.nombre, e.nombre
            ORDER BY total_citas DESC
            LIMIT 10
        ");
        $stTopPs->execute($params);
        $topPsicologos = $stTopPs->fetchAll(PDO::FETCH_ASSOC);

        // Top 10 Pacientes
        $stTopPac = $pdo->prepare("
            SELECT 
                pac.id,
                pac.nombre,
                COUNT(c.id) as total_citas,
                COALESCE(SUM(p.monto_total), 0) as total_pagado
            FROM Paciente pac
            LEFT JOIN Cita c ON pac.id = c.id_paciente AND $whereSQL
            LEFT JOIN Pago p ON c.id = p.id_cita AND p.estado_pago = 'pagado'
            GROUP BY pac.id, pac.nombre
            ORDER BY total_citas DESC
            LIMIT 10
        ");
        $stTopPac->execute($params);
        $topPacientes = $stTopPac->fetchAll(PDO::FETCH_ASSOC);

        // Horarios completos por psicólogo
        $psicologos = $psicologoModel->listarTodos();
        $horariosCompletos = [];
        foreach ($psicologos as $ps) {
            $horarios = $horarioModel->listarPorPsicologo($ps['id']);
            $horariosPorDia = [];
            foreach ($horarios as $h) {
                $dia = $h['dia_semana'];
                if (!isset($horariosPorDia[$dia]))
                    $horariosPorDia[$dia] = [];
                $horariosPorDia[$dia][] = $h;
            }
            $horariosCompletos[] = [
                'id' => $ps['id'],
                'nombre' => $ps['nombre'],
                'horarios' => $horariosPorDia
            ];
        }

        // Lista de psicólogos para el filtro
        $psicologosLista = $psicologoModel->listarActivos();

        // Datasets adicionales para nuevas gráficas/reportes
        $usuariosActivosInactivos = (new Usuario())->conteoActivosInactivos();
        $citasPorPsicologoGlobal = (new Cita())->citasPorPsicologo();
        $pacientesPorPsicologo = (new Cita())->pacientesAtendidosPorPsicologo();
        $ingresosPorEspecialidadComparativo = (new Pago())->ingresosPorEspecialidad();

        $this->render('estadisticas', [
            'anio' => $anio,
            'mes' => $mes,
            'psicologo' => $psicologoFiltro,
            'stats' => $stats,
            'citasPorMes' => $citasPorMes,
            'citasPorEstado' => $citasPorEstado,
            'ingresosPorMes' => $ingresosPorMes,
            'topPsicologos' => $topPsicologos,
            'topPacientes' => $topPacientes,
            'horariosCompletos' => $horariosCompletos,
            'psicologos' => $psicologosLista,
            // Nuevos datasets
            'usuariosActivosInactivos' => $usuariosActivosInactivos,
            'citasPorPsicologoGlobal' => $citasPorPsicologoGlobal,
            'pacientesPorPsicologo' => $pacientesPorPsicologo,
            'ingresosPorEspecialidadComparativo' => $ingresosPorEspecialidadComparativo
        ]);
    }

    private function exportarEstadisticas(string $formato, int $anio, string $mes, int $psicologoFiltro): void
    {
        require_once __DIR__ . '/../helpers/PDFHelper.php';
        require_once __DIR__ . '/../helpers/ExcelHelper.php';
        require_once __DIR__ . '/../helpers/ChartHelper.php';

        // Asegurar que no haya salida previa (evita PDF corrupto / headers enviados)
        while (ob_get_level()) {
            @ob_end_clean();
        }

        // Validaciones de entorno para PDF de gráficas
        if (str_starts_with($formato, 'pdf') && in_array($formato, ['pdf_graficas', 'pdf_datos', 'pdf'])) {
            if (!extension_loaded('gd')) {
                // Fallback: generar PDF simple sin gráficas explicando el problema
                $htmlFallback = '<html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;margin:25px;} .alert{border:1px solid #dc3545;background:#f8d7da;color:#842029;padding:15px;border-radius:6px;} h1{font-size:20px;margin-top:0;color:#2C3E50;} code{background:#eee;padding:2px 4px;border-radius:3px;} ul{margin-top:8px}</style></head><body>'
                    . '<h1>Exportación de Gráficas</h1>'
                    . '<div class="alert"><strong>Advertencia:</strong> La extensión <code>gd</code> de PHP no está habilitada, por lo que no se pueden generar las imágenes de las gráficas.<br>'
                    . 'Activa GD en <code>php.ini</code> (quita el ; a la línea <code>extension=gd</code>) y reinicia Apache. Luego vuelve a intentar.</div>'
                    . '<p>Parámetros solicitados: Año <strong>' . htmlspecialchars((string) $anio) . '</strong>' . ($mes ? ', Mes <strong>' . htmlspecialchars($mes) . '</strong>' : '') . '.</p>'
                    . '<p>Mientras tanto este PDF se genera como fallback sin gráficas.</p>'
                    . '</body></html>';
                PDFHelper::generarPDF($htmlFallback, 'Graficas_Estadisticas_FALLO_GD_' . $anio . ($mes ? '_' . $mes : ''), 'portrait', 'letter', true);
                return;
            }
        }

        // Obtener los mismos datos que la vista
        $citaModel = new Cita();
        $pdo = $citaModel->pdo();

        $where = ["YEAR(c.fecha_hora) = ?"];
        $params = [$anio];
        if ($mes) {
            $where[] = "MONTH(c.fecha_hora) = ?";
            $params[] = (int) $mes;
        }
        if ($psicologoFiltro) {
            $where[] = "c.id_psicologo = ?";
            $params[] = $psicologoFiltro;
        }
        $whereSQL = implode(' AND ', $where);

        // Soporte de exportes individualizados adicionales:
        // formatos especiales esperados:
        // pdf_pacientes_psicologo / excel_pacientes_psicologo
        // pdf_disponibilidad / excel_disponibilidad
        // pdf_citas_rango / excel_citas_rango  (requiere GET['inicio'], GET['fin'])
        // pdf_ingresos_mes / excel_ingresos_mes

        $formatoLower = strtolower($formato);

        // Rango fechas si se solicita citas_rango
        $inicioRango = $_GET['inicio'] ?? null;
        $finRango = $_GET['fin'] ?? null;

        // Acceso a modelos para reportes individuales
        $citaRptModel = new Cita();
        $pagoRptModel = new Pago();
        $psicoRptModel = new Psicologo();
        $usrRptModel = new Usuario();
        $horarioRptModel = new HorarioPsicologo();

        // Export individual: pacientes atendidos por psicólogo (tabla global)
        if (in_array($formatoLower, ['pdf_pacientes_psicologo', 'excel_pacientes_psicologo'], true)) {
            $pacientesPsico = $citaRptModel->pacientesAtendidosPorPsicologo();
            if ($formatoLower === 'excel_pacientes_psicologo') {
                $data = [['Psicólogo', 'Pacientes Únicos']];
                foreach ($pacientesPsico as $r) {
                    $data[] = [$r['psicologo'], $r['pacientes_unicos']];
                }
                ExcelHelper::exportarMultiplesHojas([
                    ['titulo' => 'PACIENTES_X_PSICO', 'data' => $data]
                ], 'reporte_pacientes_psicologo_' . date('Ymd'));
                return;
            }
            // PDF
            $rows = '';
            foreach ($pacientesPsico as $r) {
                $rows .= '<tr><td>' . htmlspecialchars($r['psicologo'], ENT_QUOTES, 'UTF-8') . '</td><td style="text-align:right">' . (int) $r['pacientes_unicos'] . '</td></tr>';
            }
            if ($rows === '')
                $rows = '<tr><td colspan="2">Sin datos</td></tr>';
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px}h1{margin:0 0 10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;font-size:11px}th{background:#f2f2f2;text-align:left}</style></head><body><h1>Reporte de Pacientes Atendidos por Psicólogo</h1><table><thead><tr><th>Psicólogo</th><th>Pacientes Únicos</th></tr></thead><tbody>' . $rows . '</tbody></table></body></html>';
            PDFHelper::generarPDF($html, 'Pacientes_Por_Psicologo_' . date('Ymd'), 'portrait', 'letter', true);
            return;
        }

        // Export individual: disponibilidad de horarios
        if (in_array($formatoLower, ['pdf_disponibilidad', 'excel_disponibilidad'], true)) {
            $psList = $psicoRptModel->listarTodos();
            $dataDisp = [['Psicólogo', 'Bloques Horario', 'Detalle']];
            $rowsPdf = '';
            foreach ($psList as $ps) {
                $hor = $horarioRptModel->listarPorPsicologo($ps['id']);
                $detalle = [];
                foreach ($hor as $h) {
                    $detalle[] = ucfirst($h['dia_semana']) . ' ' . substr($h['hora_inicio'], 0, 5) . '-' . substr($h['hora_fin'], 0, 5);
                }
                $detalleStr = $detalle ? implode(', ', $detalle) : 'Sin horarios';
                $dataDisp[] = [$ps['nombre'], count($hor), $detalleStr];
                $rowsPdf .= '<tr><td>' . htmlspecialchars($ps['nombre'], ENT_QUOTES, 'UTF-8') . '</td><td style="text-align:right">' . count($hor) . '</td><td>' . htmlspecialchars($detalleStr, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            if ($formatoLower === 'excel_disponibilidad') {
                ExcelHelper::exportarMultiplesHojas([
                    ['titulo' => 'DISPONIBILIDAD', 'data' => $dataDisp]
                ], 'reporte_disponibilidad_' . date('Ymd'));
                return;
            }
            if ($rowsPdf === '')
                $rowsPdf = '<tr><td colspan="3">Sin datos</td></tr>';
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px}h1{margin:0 0 10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;font-size:10px}th{background:#f2f2f2;text-align:left}</style></head><body><h1>Reporte de Disponibilidad de Horarios</h1><table><thead><tr><th>Psicólogo</th><th>Bloques</th><th>Detalle</th></tr></thead><tbody>' . $rowsPdf . '</tbody></table></body></html>';
            PDFHelper::generarPDF($html, 'Disponibilidad_Horarios_' . date('Ymd'), 'landscape', 'letter', true);
            return;
        }

        // Export individual: citas por rango
        if (in_array($formatoLower, ['pdf_citas_rango', 'excel_citas_rango'], true)) {
            if (!$inicioRango || !$finRango) {
                // Respuesta simple
                $html = '<html><body><strong>Debe especificar parámetros inicio y fin (YYYY-mm-dd).</strong></body></html>';
                PDFHelper::generarPDF($html, 'Citas_Rango_ERROR', 'portrait', 'letter', true);
                return;
            }
            $citasRango = $citaRptModel->citasPorRangoDetallado($inicioRango . ' 00:00:00', $finRango . ' 23:59:59');
            if ($formatoLower === 'excel_citas_rango') {
                $data = [['ID', 'Fecha', 'Hora', 'Paciente', 'Psicólogo', 'Estado']];
                foreach ($citasRango as $c) {
                    $data[] = [
                        $c['id'],
                        date('d/m/Y', strtotime($c['fecha_hora'])),
                        date('H:i', strtotime($c['fecha_hora'])),
                        $c['paciente'] ?? '',
                        $c['psicologo'] ?? '',
                        $c['estado_cita'] ?? ''
                    ];
                }
                ExcelHelper::exportarMultiplesHojas([
                    ['titulo' => 'CITAS_RANGO', 'data' => $data]
                ], 'reporte_citas_rango_' . date('Ymd'));
                return;
            }
            $rows = '';
            foreach ($citasRango as $c) {
                $rows .= '<tr>'
                    . '<td>' . (int) $c['id'] . '</td>'
                    . '<td>' . date('d/m/Y', strtotime($c['fecha_hora'])) . '</td>'
                    . '<td>' . date('H:i', strtotime($c['fecha_hora'])) . '</td>'
                    . '<td>' . htmlspecialchars($c['paciente'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($c['psicologo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($c['estado_cita'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '</tr>';
            }
            if ($rows === '')
                $rows = '<tr><td colspan="6">Sin datos en el rango</td></tr>';
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;margin:18px}h1{margin:0 0 10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:5px;font-size:9px}th{background:#f0f0f0}</style></head><body><h1>Reporte de Citas (' . htmlspecialchars($inicioRango) . ' a ' . htmlspecialchars($finRango) . ')</h1><table><thead><tr><th>ID</th><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Psicólogo</th><th>Estado</th></tr></thead><tbody>' . $rows . '</tbody></table></body></html>';
            PDFHelper::generarPDF($html, 'Citas_Rango_' . date('Ymd'), 'portrait', 'letter', true);
            return;
        }

        // Export individual: ingresos por mes (listado simple)
        if (in_array($formatoLower, ['pdf_ingresos_mes', 'excel_ingresos_mes'], true)) {
            $ingMes = $pagoRptModel->ingresosPorMes($anio);
            if ($formatoLower === 'excel_ingresos_mes') {
                $data = [['Mes', 'Ingresos']];
                foreach ($ingMes as $im) {
                    $data[] = [$im['mes'], number_format($im['total'], 2)];
                }
                ExcelHelper::exportarMultiplesHojas([
                    ['titulo' => 'INGRESOS_MES', 'data' => $data]
                ], 'reporte_ingresos_mes_' . $anio . '_' . date('Ymd'));
                return;
            }
            $rows = '';
            foreach ($ingMes as $im) {
                $rows .= '<tr><td>' . htmlspecialchars($im['mes']) . '</td><td style="text-align:right">$' . number_format($im['total'], 2) . '</td></tr>';
            }
            if ($rows === '')
                $rows = '<tr><td colspan="2">Sin ingresos</td></tr>';
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;margin:18px}h1{margin:0 0 10px}table{width:60%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px}th{background:#f2f2f2;text-align:left}</style></head><body><h1>Reporte de Ingresos por Mes - ' . (int) $anio . '</h1><table><thead><tr><th>Mes</th><th>Ingresos</th></tr></thead><tbody>' . $rows . '</tbody></table></body></html>';
            PDFHelper::generarPDF($html, 'Ingresos_Mes_' . $anio . '_' . date('Ymd'), 'portrait', 'letter', true);
            return;
        }

        // Obtener datos básicos (flujo original)
        $stCitas = $pdo->prepare("
            SELECT 
                c.id,
                c.fecha_hora,
                pac.nombre as paciente,
                u.nombre as psicologo,
                e.nombre as especialidad,
                c.estado_cita as estado,
                COALESCE(p.monto_total, 0) as monto
            FROM Cita c
            LEFT JOIN Paciente pac ON c.id_paciente = pac.id
            LEFT JOIN Psicologo ps ON c.id_psicologo = ps.id
            LEFT JOIN Usuario u ON ps.id_usuario = u.id
            LEFT JOIN Especialidad e ON ps.id_especialidad = e.id
            LEFT JOIN Pago p ON c.id = p.id_cita
            WHERE $whereSQL
            ORDER BY c.fecha_hora DESC
        ");
        $stCitas->execute($params);
        $citas = $stCitas->fetchAll(PDO::FETCH_ASSOC);

        // Datos adicionales solicitados
        $usuarioModel = new Usuario();
        $usuariosActivosInactivos = $usuarioModel->conteoActivosInactivos();
        $citaStatsModel = new Cita();
        $citasPorPsicoGlobal = $citaStatsModel->citasPorPsicologo();
        $pacientesPorPsico = $citaStatsModel->pacientesAtendidosPorPsicologo();
        $pagoModel = new Pago();
        $ingresosPorEspecialidad = $pagoModel->ingresosPorEspecialidad();

        if ($formato === 'pdf' || $formato === 'pdf_graficas' || $formato === 'pdf_datos') {
            // Calcular estadísticas (común para PDFs)
            $totalCitas = count($citas);
            $realizadas = count(array_filter($citas, fn($c) => $c['estado'] === 'realizada'));
            $pendientes = count(array_filter($citas, fn($c) => $c['estado'] === 'pendiente'));
            $canceladas = count(array_filter($citas, fn($c) => $c['estado'] === 'cancelada'));
            $ingresoTotal = array_sum(array_column($citas, 'monto'));
            $promedioIngreso = $totalCitas > 0 ? $ingresoTotal / $totalCitas : 0;

            // Agrupar por psicólogo (para top)
            $porPsicologo = [];
            foreach ($citas as $c) {
                $ps = $c['psicologo'] ?? 'Desconocido';
                if (!isset($porPsicologo[$ps])) {
                    $porPsicologo[$ps] = ['total' => 0, 'ingresos' => 0, 'especialidad' => $c['especialidad'] ?? 'N/A'];
                }
                $porPsicologo[$ps]['total']++;
                $porPsicologo[$ps]['ingresos'] += (float) $c['monto'];
            }
            arsort($porPsicologo);
            $topPsicologos = array_slice($porPsicologo, 0, 5, true);

            // Si el formato es pdf_graficas -> mostramos solo resumen + gráficas
            $soloGraficas = $formato === 'pdf_graficas';
            // Si el formato es pdf_datos -> mostramos resumen + top psicologos (sin detalle de citas)
            $soloDatos = $formato === 'pdf_datos';
            // Calcular estadísticas
            $totalCitas = count($citas);
            $realizadas = count(array_filter($citas, fn($c) => $c['estado'] === 'realizada'));
            $pendientes = count(array_filter($citas, fn($c) => $c['estado'] === 'pendiente'));
            $canceladas = count(array_filter($citas, fn($c) => $c['estado'] === 'cancelada'));
            $ingresoTotal = array_sum(array_column($citas, 'monto'));
            $promedioIngreso = $totalCitas > 0 ? $ingresoTotal / $totalCitas : 0;

            // Agrupar por psicólogo
            $porPsicologo = [];
            foreach ($citas as $c) {
                $ps = $c['psicologo'] ?? 'Desconocido';
                if (!isset($porPsicologo[$ps])) {
                    $porPsicologo[$ps] = ['total' => 0, 'ingresos' => 0, 'especialidad' => $c['especialidad'] ?? 'N/A'];
                }
                $porPsicologo[$ps]['total']++;
                $porPsicologo[$ps]['ingresos'] += (float) $c['monto'];
            }
            arsort($porPsicologo);
            $topPsicologos = array_slice($porPsicologo, 0, 5, true);

            // Generar HTML para el PDF (compatible con DomPDF) - secciones ampliadas
            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #2C3E50; padding-bottom: 10px; }
        .header h1 { color: #2C3E50; font-size: 20px; margin: 5px 0; }
        .header .subtitle { color: #7f8c8d; font-size: 11px; }
        .summary { background: #ecf0f1; padding: 12px; margin-bottom: 15px; border-radius: 5px; }
        .summary h2 { color: #2C3E50; font-size: 14px; margin: 0 0 10px 0; border-bottom: 2px solid #3498DB; padding-bottom: 5px; }
        .stat-row { margin: 8px 0; padding: 5px; background: white; border-left: 4px solid #3498DB; }
        .stat-label { font-weight: bold; color: #2C3E50; display: inline-block; width: 45%; }
        .stat-value { color: #2980b9; font-weight: bold; font-size: 11px; }
        h2 { color: #2C3E50; font-size: 13px; margin-top: 20px; margin-bottom: 8px; border-bottom: 2px solid #3498DB; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 8px; }
        th { background: #3498DB; color: white; padding: 6px 4px; text-align: left; font-weight: bold; font-size: 9px; }
        td { padding: 5px 4px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-success { background: #27ae60; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .footer { text-align: center; font-size: 8px; color: #7f8c8d; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE ESTADISTICAS DEL SISTEMA</h1>
        <div class="subtitle">Periodo: ' . htmlspecialchars($anio . ($mes ? '/' . $mes : ' (Todo el ano)')) . '</div>
        <div class="subtitle">Generado: ' . date('d/m/Y H:i:s') . '</div>
    </div>
    
    <div class="summary">
        <h2>Resumen Ejecutivo</h2>
        <div class="stat-row">
            <span class="stat-label">Total de Citas:</span>
            <span class="stat-value">' . $totalCitas . '</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Citas Realizadas:</span>
            <span class="stat-value">' . $realizadas . ' (' . ($totalCitas > 0 ? round(($realizadas / $totalCitas) * 100, 1) : 0) . '%)</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Citas Pendientes:</span>
            <span class="stat-value">' . $pendientes . ' (' . ($totalCitas > 0 ? round(($pendientes / $totalCitas) * 100, 1) : 0) . '%)</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Citas Canceladas:</span>
            <span class="stat-value">' . $canceladas . ' (' . ($totalCitas > 0 ? round(($canceladas / $totalCitas) * 100, 1) : 0) . '%)</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Ingresos Totales:</span>
            <span class="stat-value">$' . number_format($ingresoTotal, 2) . '</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Ingreso Promedio por Cita:</span>
            <span class="stat-value">$' . number_format($promedioIngreso, 2) . '</span>
        </div>
    </div>
    
    <h2>Top 5 Psicologos mas Productivos</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Psicologo</th>
                <th>Especialidad</th>
                <th class="text-center">Total Citas</th>
                <th class="text-right">Ingresos</th>
            </tr>
        </thead>
        <tbody>';

            $pos = 1;
            foreach ($topPsicologos as $nombre => $data) {
                $html .= '<tr>
                <td>' . $pos++ . '</td>
                <td>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($data['especialidad'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="text-center">' . $data['total'] . '</td>
                <td class="text-right">$' . number_format($data['ingresos'], 2) . '</td>
            </tr>';
            }

            $html .= '</tbody>
    </table>
    
    <h2>Usuarios Activos vs Inactivos</h2>
    <table>
        <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
        <tbody>
            <!--UA_ROWS-->
        </tbody>
    </table>

    <h2>Pacientes Atendidos por Psicologo</h2>
    <table>
        <thead><tr><th>Psicologo</th><th class="text-center">Pacientes Unicos</th></tr></thead>
        <tbody>
            <!--PAC_PSICO_ROWS-->
        </tbody>
    </table>

    <h2>Citas por Psicologo (Global)</h2>
    <table>
        <thead><tr><th>Psicologo</th><th class="text-center">Total Citas</th></tr></thead>
        <tbody>
            <!--CITAS_PSICO_ROWS-->
        </tbody>
    </table>

    <h2>Ingresos por Especialidad</h2>
    <table>
        <thead><tr><th>Especialidad</th><th class="text-right">Ingresos</th></tr></thead>
        <tbody>
            <!--ING_ESP_ROWS-->
        </tbody>
    </table>

    <div class="page-break"></div>';

            $uaRows = '<tr><td>Activos</td><td class="text-right">' . (int) $usuariosActivosInactivos['activo'] . '</td></tr>' .
                '<tr><td>Inactivos</td><td class="text-right">' . (int) $usuariosActivosInactivos['inactivo'] . '</td></tr>';
            $html = str_replace('<!--UA_ROWS-->', $uaRows, $html);

            $pacRows = '';
            foreach ($pacientesPorPsico as $r) {
                $pacRows .= '<tr><td>' . htmlspecialchars($r['psicologo'], ENT_QUOTES, 'UTF-8') . '</td><td class="text-center">' . (int) $r['pacientes_unicos'] . '</td></tr>';
            }
            if ($pacRows === '')
                $pacRows = '<tr><td colspan="2">Sin datos</td></tr>';
            $html = str_replace('<!--PAC_PSICO_ROWS-->', $pacRows, $html);

            $citasRows = '';
            foreach ($citasPorPsicoGlobal as $r) {
                $citasRows .= '<tr><td>' . htmlspecialchars($r['psicologo'], ENT_QUOTES, 'UTF-8') . '</td><td class="text-center">' . (int) $r['total'] . '</td></tr>';
            }
            if ($citasRows === '')
                $citasRows = '<tr><td colspan="2">Sin datos</td></tr>';
            $html = str_replace('<!--CITAS_PSICO_ROWS-->', $citasRows, $html);

            $ingEspRows = '';
            foreach ($ingresosPorEspecialidad as $r) {
                $esp = $r['especialidad'] ?: 'N/D';
                $ingEspRows .= '<tr><td>' . htmlspecialchars($esp, ENT_QUOTES, 'UTF-8') . '</td><td class="text-right">$' . number_format((float) $r['total'], 2) . '</td></tr>';
            }
            if ($ingEspRows === '')
                $ingEspRows = '<tr><td colspan="2">Sin datos</td></tr>';
            $html = str_replace('<!--ING_ESP_ROWS-->', $ingEspRows, $html);

            // Generar gráficas con ChartHelper (ya validamos GD arriba)
            // 1. Citas por mes
            $stCitasMes = $pdo->prepare("
        SELECT DATE_FORMAT(c.fecha_hora, '%m-%Y') as mes, COUNT(*) as total
        FROM Cita c
        WHERE YEAR(c.fecha_hora) = ?
        " . ($psicologoFiltro ? "AND c.id_psicologo = ?" : "") . "
        GROUP BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
        ORDER BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
    ");
            $paramsMes = [$anio];
            if ($psicologoFiltro)
                $paramsMes[] = $psicologoFiltro;
            $stCitasMes->execute($paramsMes);
            $citasPorMesData = $stCitasMes->fetchAll(PDO::FETCH_ASSOC);

            $mesesLabels = array_column($citasPorMesData, 'mes');
            $mesesData = array_column($citasPorMesData, 'total');
            $chartCitasMes = ChartHelper::generarBarChart($mesesData, $mesesLabels, 'Citas por Mes', 700, 300);

            // 2. Citas por estado
            $stEstado = $pdo->prepare("
        SELECT estado_cita as estado, COUNT(*) as total
        FROM Cita c
        WHERE $whereSQL
        GROUP BY estado_cita
    ");
            $stEstado->execute($params);
            $citasPorEstadoData = $stEstado->fetchAll(PDO::FETCH_ASSOC);

            $estadosLabels = [];
            $estadosData = [];
            foreach ($citasPorEstadoData as $est) {
                $estadosLabels[] = ucfirst($est['estado']);
                $estadosData[] = (int) $est['total'];
            }
            $chartEstado = ChartHelper::generarPieChart($estadosData, $estadosLabels, 'Citas por Estado', 600, 350);

            // 3. Ingresos por mes
            $stIngresosMes = $pdo->prepare("
        SELECT DATE_FORMAT(c.fecha_hora, '%m-%Y') as mes, COALESCE(SUM(p.monto_total), 0) as total
        FROM Pago p
        JOIN Cita c ON p.id_cita = c.id
        WHERE YEAR(c.fecha_hora) = ?
        " . ($psicologoFiltro ? "AND c.id_psicologo = ?" : "") . "
          AND p.estado_pago = 'pagado'
        GROUP BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
        ORDER BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
    ");
            $stIngresosMes->execute($paramsMes);
            $ingresosPorMesData = $stIngresosMes->fetchAll(PDO::FETCH_ASSOC);

            $ingresosLabels = array_column($ingresosPorMesData, 'mes');
            $ingresosData = array_map('floatval', array_column($ingresosPorMesData, 'total'));
            $chartIngresos = ChartHelper::generarLineChart($ingresosData, $ingresosLabels, 'Ingresos por Mes', 700, 300);

            // 4. Grafico comparativo ingresos por especialidad (bar)
            $labelsEsp = array_map(fn($r) => $r['especialidad'] ?: 'N/D', $ingresosPorEspecialidad);
            $dataEsp = array_map(fn($r) => (float) $r['total'], $ingresosPorEspecialidad);
            $chartIngresosEspecialidad = ChartHelper::generarBarChart($dataEsp, $labelsEsp, 'Ingresos por Especialidad', 700, 300);

            // 5. Usuarios activos vs inactivos (pie)
            $usuariosAI = (new Usuario())->conteoActivosInactivos();
            $uaLabels = ['Activos', 'Inactivos'];
            $uaData = [(int) $usuariosAI['activo'], (int) $usuariosAI['inactivo']];
            $chartUsuariosAI = ChartHelper::generarPieChart($uaData, $uaLabels, 'Usuarios Activos/Inactivos', 500, 350);

            // 6. Citas por psicólogo (bar) y Pacientes por psicólogo (bar)
            $citasPsico = (new Cita())->citasPorPsicologo();
            $labelsPsico = array_map(fn($r) => $r['psicologo'], $citasPsico);
            $dataCitasPsico = array_map(fn($r) => (int) $r['total'], $citasPsico);
            $chartCitasPsico = ChartHelper::generarBarChart($dataCitasPsico, $labelsPsico, 'Citas por Psicologo', 700, 300);

            $pacPsico = (new Cita())->pacientesAtendidosPorPsicologo();
            $labelsPacPsico = array_map(fn($r) => $r['psicologo'], $pacPsico);
            $dataPacPsico = array_map(fn($r) => (int) $r['pacientes_unicos'], $pacPsico);
            $chartPacPsico = ChartHelper::generarBarChart($dataPacPsico, $labelsPacPsico, 'Pacientes por Psicologo', 700, 300);

            // 7. Atendidas vs Canceladas (doughnut) usando estados ya consultados
            $realizadasCnt = 0;
            $canceladasCnt = 0;
            foreach ($citasPorEstadoData as $e) {
                if (($e['estado'] ?? $e['estado_cita'] ?? '') === 'realizada')
                    $realizadasCnt += (int) $e['total'];
                if (($e['estado'] ?? $e['estado_cita'] ?? '') === 'cancelada')
                    $canceladasCnt += (int) $e['total'];
            }
            $chartAtendidasCanceladas = ChartHelper::generarPieChart([
                $realizadasCnt,
                $canceladasCnt
            ], ['Atendidas', 'Canceladas'], 'Atendidas vs Canceladas', 500, 350);

            // Si el formato es solo gráficas debemos generar un PDF minimalista: solo header + gráficas + footer
            if ($soloGraficas) {
                // Preparar textos seguros para interpolación
                $periodoStr = htmlspecialchars($anio . ($mes ? '/' . $mes : ' (Todo el ano)'));
                $generadoStr = date('d/m/Y H:i:s');

                $htmlGraphs = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #2C3E50; padding-bottom: 10px; }
        .header h1 { color: #2C3E50; font-size: 20px; margin: 5px 0; }
        .header .subtitle { color: #7f8c8d; font-size: 11px; }
        .footer { text-align: center; font-size: 8px; color: #7f8c8d; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAFICAS - ESTADISTICAS DEL SISTEMA</h1>
        <div class="subtitle">Periodo: {$periodoStr}</div>
        <div class="subtitle">Generado: {$generadoStr}</div>
    </div>

    <h2>Graficas Estadisticas</h2>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartCitasMes}" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartEstado}" style="width: 80%; max-width: 600px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartIngresos}" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartIngresosEspecialidad}" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartUsuariosAI}" style="width: 80%; max-width: 500px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartCitasPsico}" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartPacPsico}" style="width: 100%; max-width: 700px; margin-bottom: 15px;">
    </div>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{$chartAtendidasCanceladas}" style="width: 80%; max-width: 500px; margin-bottom: 15px;">
    </div>
    <div class="page-break"></div>

    <div class="footer">
        Sistema de Gestion de Consultorio Psicologico - Reporte Confidencial
    </div>
</body>
</html>
HTML;

                PDFHelper::generarPDF($htmlGraphs, 'Graficas_Estadisticas_' . $anio . ($mes ? '_' . $mes : '') . '_' . date('Ymd'), 'landscape', 'letter', true);
                return;
            }

            // Si es pdf_datos -> generar un PDF minimalista y con tablas sencillas (Resumen + Top 5 Psicologos)
            if ($soloDatos) {
                // Construir filas de Top 5 Psicologos
                $rows = '';
                $pos = 1;
                foreach ($topPsicologos as $nombre => $data) {
                    $rows .= '<tr>'
                        . '<td style="padding:6px;border:1px solid #ddd;text-align:center">' . $pos++ . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($data['especialidad'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;text-align:center">' . ($data['total'] ?? 0) . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;text-align:right">$' . number_format($data['ingresos'] ?? 0, 2) . '</td>'
                        . '</tr>';
                }

                $periodoStr = htmlspecialchars($anio . ($mes ? '/' . $mes : ' (Todo el ano)'));
                $generadoStr = date('d/m/Y H:i:s');
                $porcRealizadas = $totalCitas > 0 ? round(($realizadas / $totalCitas) * 100, 1) : 0;
                $porcPendientes = $totalCitas > 0 ? round(($pendientes / $totalCitas) * 100, 1) : 0;
                $porcCanceladas = $totalCitas > 0 ? round(($canceladas / $totalCitas) * 100, 1) : 0;
                $ingresoFmt = '$' . number_format($ingresoTotal, 2);

                $htmlDatos = <<<HTML
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{ font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; margin:18px }
        h1,h2{ color:#2C3E50; margin:0 0 8px 0 }
        table { width:100%; border-collapse: collapse; margin-top:8px }
        th { background:#f2f2f2; padding:6px; border:1px solid #ddd; text-align:left; font-weight:bold }
        td { padding:6px; border:1px solid #ddd }
        .muted { color: #666; font-size: 9px }
        .footer { text-align:center; font-size:9px; color:#999; margin-top:12px }
    </style>
</head>
<body>
    <h1>REPORTE DE DATOS - ESTADISTICAS</h1>
    <div class="muted">Periodo: {$periodoStr} — Generado: {$generadoStr}</div>

    <h2>Resumen Ejecutivo</h2>
    <table>
        <tr><th>Métrica</th><th>Valor</th></tr>
        <tr><td>Total de Citas</td><td style="text-align:right">{$totalCitas}</td></tr>
        <tr><td>Citas Realizadas</td><td style="text-align:right">{$realizadas} ({$porcRealizadas}%)</td></tr>
        <tr><td>Citas Pendientes</td><td style="text-align:right">{$pendientes} ({$porcPendientes}%)</td></tr>
        <tr><td>Citas Canceladas</td><td style="text-align:right">{$canceladas} ({$porcCanceladas}%)</td></tr>
        <tr><td>Ingresos Totales</td><td style="text-align:right">{$ingresoFmt}</td></tr>
    </table>

    <h2>Top 5 Psicólogos</h2>
    <table>
        <thead>
            <tr><th style="width:5%">#</th><th>Psicólogo</th><th>Especialidad</th><th style="width:10%">Citas</th><th style="width:15%">Ingresos</th></tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>

    <div class="footer">Sistema de Gestion de Consultorio Psicologico - Reporte Confidencial — Página 1 de 1</div>
</body>
</html>
HTML;

                // Enviar PDF de datos
                PDFHelper::generarPDF($htmlDatos, 'Datos_Estadisticas_' . $anio . ($mes ? '_' . $mes : '') . '_' . date('Ymd'), 'portrait', 'letter', true);
                return;
            }

            // Si no es solo gráficas ni datos (pdf legacy) agregamos footer correctamente sin literales '\n'
            $html .= "\n    <div class=\"footer\">\n        Sistema de Gestion de Consultorio Psicologico - Reporte Confidencial<br>\n        Pagina 1 de 1 - " . $totalCitas . " registros totales\n    </div>\n</body>\n</html>";

            PDFHelper::generarPDF($html, 'Reporte_Estadisticas_' . $anio . ($mes ? '_' . $mes : '') . '_' . date('Ymd'), 'landscape', 'letter', true);

        } else if ($formato === 'excel') {
            // HOJA 1: Resumen General
            $dataResumen = [
                ['ESTADÍSTICAS DEL SISTEMA - ' . $anio . ($mes ? '/' . $mes : ''), '', '', ''],
                ['', '', '', ''],
                ['Métrica', 'Valor', '', ''],
                ['Total de Citas', count($citas), '', ''],
                ['Citas Realizadas', count(array_filter($citas, fn($c) => $c['estado'] === 'realizada')), '', ''],
                ['Citas Pendientes', count(array_filter($citas, fn($c) => $c['estado'] === 'pendiente')), '', ''],
                ['Citas Canceladas', count(array_filter($citas, fn($c) => $c['estado'] === 'cancelada')), '', ''],
                ['Ingresos Totales', '$' . number_format(array_sum(array_column($citas, 'monto')), 2), '', '']
            ];

            // HOJA 2: Citas Detalladas
            $dataCitas = [['ID', 'Fecha', 'Hora', 'Paciente', 'Psicólogo', 'Especialidad', 'Estado', 'Monto']];
            foreach ($citas as $c) {
                $dataCitas[] = [
                    $c['id'],
                    date('d/m/Y', strtotime($c['fecha_hora'])),
                    date('H:i', strtotime($c['fecha_hora'])),
                    $c['paciente'],
                    $c['psicologo'],
                    $c['especialidad'],
                    $c['estado'],
                    '$' . number_format($c['monto'], 2)
                ];
            }

            // HOJA 3: Citas por Estado
            $estadosCounts = [];
            foreach ($citas as $c) {
                $est = $c['estado'];
                if (!isset($estadosCounts[$est]))
                    $estadosCounts[$est] = 0;
                $estadosCounts[$est]++;
            }
            $dataEstados = [['Estado', 'Cantidad', 'Porcentaje']];
            $total = count($citas);
            foreach ($estadosCounts as $est => $cnt) {
                $dataEstados[] = [
                    ucfirst($est),
                    $cnt,
                    ($total > 0 ? round(($cnt / $total) * 100, 2) : 0) . '%'
                ];
            }

            // HOJA 4: Ingresos por Mes
            $stIngresos = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(c.fecha_hora, '%m-%Y') as mes,
                    COUNT(c.id) as citas,
                    COALESCE(SUM(p.monto_total), 0) as ingresos
                FROM Cita c
                LEFT JOIN Pago p ON c.id = p.id_cita AND p.estado_pago = 'pagado'
                WHERE $whereSQL
                GROUP BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
                ORDER BY DATE_FORMAT(c.fecha_hora, '%Y-%m')
            ");
            $stIngresos->execute($params);
            $ingresosMes = $stIngresos->fetchAll(PDO::FETCH_ASSOC);

            $dataIngresos = [['Mes', 'Total Citas', 'Ingresos']];
            foreach ($ingresosMes as $im) {
                $dataIngresos[] = [
                    $im['mes'],
                    $im['citas'],
                    '$' . number_format($im['ingresos'], 2)
                ];
            }

            // HOJA 5: Top 10 Psicólogos
            $stTopPs = $pdo->prepare("
                SELECT 
                    u.nombre,
                    e.nombre as especialidad,
                    COUNT(c.id) as total_citas,
                    COALESCE(SUM(p.monto_total), 0) as ingresos
                FROM Psicologo ps
                JOIN Usuario u ON ps.id_usuario = u.id
                LEFT JOIN Especialidad e ON ps.id_especialidad = e.id
                LEFT JOIN Cita c ON ps.id = c.id_psicologo AND $whereSQL
                LEFT JOIN Pago p ON c.id = p.id_cita AND p.estado_pago = 'pagado'
                GROUP BY ps.id, u.nombre, e.nombre
                ORDER BY total_citas DESC
                LIMIT 10
            ");
            $stTopPs->execute($params);
            $topPs = $stTopPs->fetchAll(PDO::FETCH_ASSOC);

            $dataPsicologos = [['Psicólogo', 'Especialidad', 'Total Citas', 'Ingresos']];
            foreach ($topPs as $ps) {
                $dataPsicologos[] = [
                    $ps['nombre'],
                    $ps['especialidad'],
                    $ps['total_citas'],
                    '$' . number_format($ps['ingresos'], 2)
                ];
            }

            // HOJA 6: Top 10 Pacientes
            $stTopPac = $pdo->prepare("
                SELECT 
                    pac.nombre,
                    pac.dui,
                    COUNT(c.id) as total_citas,
                    COALESCE(SUM(p.monto_total), 0) as total_pagado
                FROM Paciente pac
                LEFT JOIN Cita c ON pac.id = c.id_paciente AND $whereSQL
                LEFT JOIN Pago p ON c.id = p.id_cita AND p.estado_pago = 'pagado'
                GROUP BY pac.id, pac.nombre, pac.dui
                ORDER BY total_citas DESC
                LIMIT 10
            ");
            $stTopPac->execute($params);
            $topPac = $stTopPac->fetchAll(PDO::FETCH_ASSOC);

            $dataPacientes = [['Paciente', 'DUI', 'Total Citas', 'Total Pagado']];
            foreach ($topPac as $pac) {
                $dataPacientes[] = [
                    $pac['nombre'],
                    $pac['dui'] ?? 'N/A',
                    $pac['total_citas'],
                    '$' . number_format($pac['total_pagado'], 2)
                ];
            }

            // HOJA 7: Horarios de Psicólogos
            require_once __DIR__ . '/../Models/HorarioPsicologo.php';
            $horarioModel = new HorarioPsicologo();
            $psicologoModel = new Psicologo();
            $psicologos = $psicologoModel->listarTodos();

            $dataHorarios = [['Psicólogo', 'Día', 'Hora Inicio', 'Hora Fin']];
            foreach ($psicologos as $ps) {
                $horarios = $horarioModel->listarPorPsicologo($ps['id']);
                if (empty($horarios)) {
                    $dataHorarios[] = [$ps['nombre'], 'Sin horarios', '', ''];
                } else {
                    foreach ($horarios as $h) {
                        $dataHorarios[] = [
                            $ps['nombre'],
                            ucfirst($h['dia_semana']),
                            date('h:i A', strtotime($h['hora_inicio'])),
                            date('h:i A', strtotime($h['hora_fin']))
                        ];
                    }
                }
            }

            // Hojas adicionales solicitadas
            $dataUsuariosAI = [['Estado', 'Cantidad'], ['Activos', (int) $usuariosActivosInactivos['activo']], ['Inactivos', (int) $usuariosActivosInactivos['inactivo']]];

            $dataPacientesPsico = [['Psicólogo', 'Pacientes Únicos']];
            foreach ($pacientesPorPsico as $r) {
                $dataPacientesPsico[] = [$r['psicologo'], $r['pacientes_unicos']];
            }

            $dataCitasPsico = [['Psicólogo', 'Total Citas']];
            foreach ($citasPorPsicoGlobal as $r) {
                $dataCitasPsico[] = [$r['psicologo'], $r['total']];
            }

            $dataIngresosEsp = [['Especialidad', 'Ingresos']];
            foreach ($ingresosPorEspecialidad as $r) {
                $dataIngresosEsp[] = [$r['especialidad'] ?: 'N/D', '$' . number_format((float) $r['total'], 2)];
            }

            // Resumen disponibilidad (conteo bloques por psicologo)
            $dataDisponibilidad = [['Psicólogo', 'Bloques Horario']];
            foreach ($psicologos as $ps) {
                $horarios = $horarioModel->listarPorPsicologo($ps['id']);
                $dataDisponibilidad[] = [$ps['nombre'], count($horarios)];
            }

            // Crear archivo Excel con todas las hojas
            $hojas = [
                ['titulo' => 'RESUMEN', 'data' => $dataResumen],
                ['titulo' => 'CITAS', 'data' => $dataCitas],
                ['titulo' => 'CITAS POR ESTADO', 'data' => $dataEstados],
                ['titulo' => 'INGRESOS POR MES', 'data' => $dataIngresos],
                ['titulo' => 'TOP 10 PSICOLOGOS', 'data' => $dataPsicologos],
                ['titulo' => 'TOP 10 PACIENTES', 'data' => $dataPacientes],
                ['titulo' => 'HORARIOS', 'data' => $dataHorarios],
                ['titulo' => 'USUARIOS A/I', 'data' => $dataUsuariosAI],
                ['titulo' => 'PACIENTES X PSICO', 'data' => $dataPacientesPsico],
                ['titulo' => 'CITAS X PSICO', 'data' => $dataCitasPsico],
                ['titulo' => 'ING X ESPECIALIDAD', 'data' => $dataIngresosEsp],
                ['titulo' => 'DISPONIBILIDAD', 'data' => $dataDisponibilidad]
            ];

            ExcelHelper::exportarMultiplesHojas($hojas, 'estadisticas_admin_' . $anio . ($mes ? '_' . $mes : ''));
        }
    }

    /* ============ Endpoints JSON para Charts ============ */
    public function jsonUsuariosActivos(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');
        echo json_encode((new Usuario())->conteoActivosInactivos());
    }
    public function jsonCitasEstados(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');
        echo json_encode((new Cita())->estadisticasEstado());
    }
    public function jsonIngresosMes(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');
        echo json_encode((new Pago())->ingresosPorMes((int) date('Y')));
    }
    public function jsonIngresosPsicologo(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');
        echo json_encode((new Pago())->ingresosPorPsicologo());
    }
}