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
        $usuarioModel = new Usuario();
        $pacienteModel = new Paciente();
        $citaModel = new Cita();
        $pagoModel = new Pago();

        // Contadores básicos
        $usuariosCounts = $usuarioModel->conteoActivosInactivos();
        $citaStats = $citaModel->estadisticasEstado();

        // Datos adicionales para el dashboard mejorado
        $totalPacientes = count($pacienteModel->listarTodos());
        $pacientesActivos = count(array_filter($pacienteModel->listarTodos(), fn($p) => ($p['estado'] ?? 'activo') === 'activo'));

        // Citas del mes actual
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');
        $todasCitas = method_exists($citaModel, 'citasPorRango') ? $citaModel->citasPorRango($inicioMes, $finMes) : [];
        $citasMes = count($todasCitas);
        $citasPendientes = count(array_filter($todasCitas, fn($c) => $c['estado_cita'] === 'pendiente'));

        // Ingresos del mes
        $ingresosMesData = $pagoModel->ingresosPorMes((int) date('Y'));
        $mesActual = (int) date('m');
        $ingresosMes = 0;
        foreach ($ingresosMesData as $im) {
            if ((int) substr($im['mes'], 0, 2) === $mesActual) {
                $ingresosMes = $im['total'];
                break;
            }
        }

        // Pagos completados
        $todosPagos = method_exists($pagoModel, 'listarTodos') ? $pagoModel->listarTodos() : [];
        $pagosPagados = count(array_filter($todosPagos, fn($p) => ($p['estado_pago'] ?? '') === 'pagado'));

        // Citas próximas (con JOIN a paciente y psicologo)
        $pdo = $citaModel->pdo();
        $stProx = $pdo->query("
            SELECT c.*, 
                   pac.nombre as paciente_nombre,
                   u.nombre as psicologo_nombre
            FROM Cita c
            LEFT JOIN Paciente pac ON c.id_paciente = pac.id
            LEFT JOIN Psicologo ps ON c.id_psicologo = ps.id
            LEFT JOIN Usuario u ON ps.id_usuario = u.id
            WHERE c.fecha_hora >= NOW() 
              AND c.estado_cita = 'pendiente'
            ORDER BY c.fecha_hora ASC
            LIMIT 5
        ");
        $proximasCitas = $stProx->fetchAll(PDO::FETCH_ASSOC);

        // Pagos pendientes (con JOIN a paciente)
        $stPagos = $pdo->query("
            SELECT p.*, 
                   pac.nombre as paciente_nombre
            FROM Pago p
            LEFT JOIN Cita c ON p.id_cita = c.id
            LEFT JOIN Paciente pac ON c.id_paciente = pac.id
            WHERE p.estado_pago = 'pendiente'
            ORDER BY p.fecha DESC
            LIMIT 5
        ");
        $pagosPendientesLista = $stPagos->fetchAll(PDO::FETCH_ASSOC);

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
            ob_clean(); // Limpiar cualquier salida previa
            header('Content-Type: application/json');
            try {
                $citas = $this->filtrarCitasAdmin($citaModel);
                echo json_encode(['citas' => $citas]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['error' => 'Error al cargar citas: ' . $e->getMessage(), 'citas' => []]);
                exit;
            }
        }
        // AJAX evaluaciones - obtener evaluaciones de una cita
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'evaluaciones') {
            ob_clean(); // Limpiar cualquier salida previa
            header('Content-Type: application/json');
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
            } catch (Exception $e) {
                echo json_encode(['error' => 'Error al cargar evaluaciones: ' . $e->getMessage()]);
                exit;
            }
        }
        // AJAX slots para un psicólogo destino en fecha dada (para reasignar)
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'slots') {
            ob_clean(); // Limpiar cualquier salida previa
            header('Content-Type: application/json');
            try {
                $idPs = (int) ($_GET['ps'] ?? 0);
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $interval = 30; // fijo
                $resp = ['ps' => $idPs, 'fecha' => $fecha, 'slots' => []];

                if (!$idPs || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    echo json_encode($resp);
                    exit;
                }

                // Obtener bloques horario (similar a PsicologoController::slots)
                $diaMap = ['Mon' => 'lunes', 'Tue' => 'martes', 'Wed' => 'miércoles', 'Thu' => 'jueves', 'Fri' => 'viernes', 'Sat' => 'sábado', 'Sun' => 'domingo'];
                $dt = DateTime::createFromFormat('Y-m-d', $fecha);
                if (!$dt) {
                    echo json_encode($resp);
                    exit;
                }

                $diaBD = $diaMap[$dt->format('D')] ?? 'lunes';

                // Variantes sin acento para compatibilidad (miercoles, sabado)
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

            } catch (Exception $e) {
                echo json_encode(['error' => true, 'message' => $e->getMessage(), 'slots' => []]);
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
        if (method_exists($citaModel, 'citasPorRango')) {
            $base = $citaModel->citasPorRango(date('Y-m-01'), '2999-12-31');
        } elseif (method_exists($citaModel, 'todas')) {
            $base = $citaModel->todas();
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

    /* ================== Horarios Psicólogos =================== */
    public function horarios(): void
    {
        $this->requireAdmin();
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
        $psM = new Psicologo();
        $hM = new HorarioPsicologo();
        $msg = '';
        $err = '';
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
                } elseif ($accion === 'eliminar') {
                    $idH = (int) ($_POST['id_horario'] ?? 0);
                    if ($idH) {
                        $hM->eliminar($idH);
                        $msg = 'Horario eliminado';
                    }
                }
            } catch (Throwable $e) {
                $err = $e->getMessage();
            }
            $this->safeRedirect(url('admin', 'horarios') . ($err ? '&err=' . urlencode($err) : '&ok=1'));
        }
        $psicologos = $psM->listarTodos();
        $idSel = (int) ($_GET['ps'] ?? 0);
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

        // Exportación
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
            'psicologos' => $psicologosLista
        ]);
    }

    private function exportarEstadisticas(string $formato, int $anio, string $mes, int $psicologoFiltro): void
    {
        require_once __DIR__ . '/../helpers/PDFHelper.php';
        require_once __DIR__ . '/../helpers/ExcelHelper.php';

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

        // Obtener datos
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

        if ($formato === 'pdf') {
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

            // Generar HTML para el PDF (compatible con DomPDF)
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
    
    <div class="page-break"></div>
    
    <h2>Detalle Completo de Citas</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Psicologo</th>
                <th>Especialidad</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($citas as $c) {
                $paciente = htmlspecialchars($c['paciente'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $psicologo = htmlspecialchars($c['psicologo'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $especialidad = htmlspecialchars($c['especialidad'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $estado = $c['estado'] ?? 'pendiente';
                $badgeClass = $estado === 'realizada' ? 'badge-success' : ($estado === 'pendiente' ? 'badge-warning' : 'badge-danger');

                $html .= '<tr>
                <td>' . (int) $c['id'] . '</td>
                <td>' . date('d/m/Y H:i', strtotime($c['fecha_hora'])) . '</td>
                <td>' . $paciente . '</td>
                <td>' . $psicologo . '</td>
                <td>' . $especialidad . '</td>
                <td class="text-center"><span class="badge ' . $badgeClass . '">' . ucfirst($estado) . '</span></td>
                <td class="text-right">$' . number_format((float) $c['monto'], 2) . '</td>
            </tr>';
            }

            $html .= '</tbody>
    </table>
    
    <div class="footer">
        Sistema de Gestion de Consultorio Psicologico - Reporte Confidencial<br>
        Pagina 1 de 1 - ' . $totalCitas . ' registros totales
    </div>
</body>
</html>';

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

            // Crear archivo Excel con todas las hojas
            $hojas = [
                ['titulo' => 'RESUMEN', 'data' => $dataResumen],
                ['titulo' => 'CITAS', 'data' => $dataCitas],
                ['titulo' => 'CITAS POR ESTADO', 'data' => $dataEstados],
                ['titulo' => 'INGRESOS POR MES', 'data' => $dataIngresos],
                ['titulo' => 'TOP 10 PSICOLOGOS', 'data' => $dataPsicologos],
                ['titulo' => 'TOP 10 PACIENTES', 'data' => $dataPacientes],
                ['titulo' => 'HORARIOS', 'data' => $dataHorarios]
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