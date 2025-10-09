<?php
/** Controlador de Citas */
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/BaseController.php';

class CitaController extends BaseController
{

    private string $viewsPath;

    public function __construct()
    {
        $this->viewsPath = __DIR__ . '/../Views/'; // Usa la carpeta con mayúscula
    }

    protected function render($vista, $data = []): void
    {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista no encontrada: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }

    private function requireRoles(array $roles)
    {
        if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], $roles, true)) {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado</div>';
            exit;
        }
    }

    // Punto de entrada genérico desde el menú "Citas"
    public function index(): void
    {
        if (!isset($_SESSION['usuario'])) {
            $this->safeRedirect(RUTA . 'auth/login');
            return;
        }
        $rol = $_SESSION['usuario']['rol'] ?? '';
        if ($rol === 'paciente') {
            $this->safeRedirect(RUTA . 'cita/mis');
        } elseif ($rol === 'psicologo') {
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas');
        } elseif ($rol === 'admin') {
            // Admin puede reutilizar la pantalla del psicólogo o una admin específica más adelante
            $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas');
        } else {
            echo '<div class="alert alert-warning">Rol sin vista de citas asignada.</div>';
        }
    }

    // Paciente: listado + crear en misma vista
    public function mis(): void
    {
        $this->requireRoles(['paciente']);
        // Determinar ID de paciente desde la sesión unificada de acceso por código
        $idPaciente = 0;
        if (isset($_SESSION['paciente_id'])) {
            $idPaciente = (int) $_SESSION['paciente_id'];
        } elseif (isset($_SESSION['usuario']['id_paciente'])) {
            $idPaciente = (int) $_SESSION['usuario']['id_paciente'];
        } else {
            // En instalaciones antiguas quizá guardaron directamente el id en usuario
            $idPaciente = (int) ($_SESSION['usuario']['id'] ?? 0);
        }

        if ($idPaciente <= 0) {
            echo '<div class="alert alert-danger">Sesión de paciente inválida. Vuelve a iniciar sesión.</div>';
            return;
        }

        // Verificar existencia real del paciente
        $paciente = (new Paciente())->getById($idPaciente);
        if (!$paciente) {
            echo '<div class="alert alert-danger">Paciente no encontrado. Vuelve a iniciar sesión.</div>';
            return;
        }

        $mensaje = '';
        $tipoMsj = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha = trim($_POST['fecha_hora'] ?? '');
            $motivo = trim($_POST['motivo'] ?? '');
            $idPsico = (int) ($_POST['id_psicologo'] ?? 0);

            if ($fecha === '' || $motivo === '' || $idPsico <= 0) {
                $mensaje = 'Completa todos los campos.';
                $tipoMsj = 'danger';
            } else {
                $fechaNorm = str_replace('T', ' ', $fecha);
                $dt = DateTime::createFromFormat('Y-m-d H:i', substr($fechaNorm, 0, 16));
                if (!$dt) {
                    $mensaje = 'Fecha/hora inválida.';
                    $tipoMsj = 'danger';
                } elseif ($dt < new DateTime('-5 minutes')) {
                    $mensaje = 'La fecha debe ser futura.';
                    $tipoMsj = 'danger';
                } else {
                    // Validar psicólogo activo
                    $psActivos = (new Psicologo())->listarActivos();
                    $valido = false;
                    foreach ($psActivos as $ps) {
                        if ((int) $ps['id'] === $idPsico) {
                            $valido = true;
                            break;
                        }
                    }
                    if (!$valido) {
                        $mensaje = 'Psicólogo inválido.';
                        $tipoMsj = 'danger';
                    } else {
                        $token = bin2hex(random_bytes(8));
                        $citaModel = new Cita();
                        $idCita = $citaModel->crear([
                            'id_paciente' => $idPaciente,
                            'id_psicologo' => $idPsico,
                            'fecha_hora' => $dt->format('Y-m-d H:i:00'),
                            'motivo_consulta' => $motivo,
                            'qr_code' => $token
                        ]);
                        (new Pago())->crearParaCita($idCita);
                        $this->safeRedirect(RUTA . 'cita/mis');
                        return;
                    }
                }
            }
        }

        $citas = (new Cita())->listarPaciente($idPaciente);
        $psicologos = (new Psicologo())->listarActivos();

        $this->render('paciente/citas', [
            'citas' => $citas,
            'psicologos' => $psicologos,
            'mensaje' => $mensaje,
            'tipoMsj' => $tipoMsj
        ]);
    }

    // Psicólogo: citas pendientes
    public function pendientes(): void
    {
        $this->requireRoles(['psicologo']);
        // Redirigimos a la nueva pantalla unificada de citas del psicólogo
        $this->safeRedirect(RUTA . 'index.php?url=psicologo/citas');
        return;
    }

    // Redirige (form ya está en mis)
    public function crear(): void
    {
        $this->requireRoles(['paciente']);
        $this->safeRedirect(RUTA . 'cita/mis');
        return;
    }

    public function check($token): void
    {
        $this->requireRoles(['psicologo', 'admin']);
        $cita = (new Cita())->obtenerPorQr($token);
        if (!$cita) {
            echo '<div class="alert alert-warning">Cita no encontrada</div>';
            return;
        }
        $this->render('psicologo/cita_scan', ['cita' => $cita]);
    }

    public function marcarPagada($id): void
    {
        $this->requireRoles(['psicologo', 'admin']);
        $id = (int) $id;
        $citaModel = new Cita();
        $cita = $citaModel->obtener($id);
        if (!$cita) {
            echo '<div class="alert alert-danger">Cita no existe</div>';
            return;
        }
        $citaModel->marcarRealizada($id);

        $pagoModel = new Pago();
        $pagoId = $pagoModel->crearParaCita($id);
        $pagoModel->marcarPagado($pagoId);

        $ticketModel = new TicketPago();
        if (!$ticketModel->obtenerPorPago($pagoId)) {
            $codigo = 'TCK-' . strtoupper(bin2hex(random_bytes(4)));
            $numero = date('YmdHis') . '-' . $pagoId;
            $url = RUTA . 'ticket/verPago/' . $pagoId;
            $qr = QRHelper::generarQR($url, 'ticket_' . $pagoId);
            $ticketModel->crear([
                'id_pago' => $pagoId,
                'codigo' => $codigo,
                'numero_ticket' => $numero,
                'qr_code' => $qr
            ]);
        }
        $this->safeRedirect(RUTA . 'cita/pendientes');
        return;
    }
}
