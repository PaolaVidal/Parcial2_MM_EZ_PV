<?php
/** Controlador de Pagos (router: /pago/accion/id) */
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/PagoExtra.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/BaseController.php';

class PagoController extends BaseController
{

    private string $viewsPath;

    public function __construct()
    {
        // Ajusta mayúsculas según tu carpeta real
        $this->viewsPath = __DIR__ . '/../Views/';
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

    public function index(): void
    {
        if (!isset($_SESSION['usuario'])) {
            echo '<div class="alert alert-warning">No autenticado.</div>';
            return;
        }
        $rol = $_SESSION['usuario']['rol'];
        $pagoModel = new Pago();
        $pagos = [];

        if ($rol === 'paciente') {
            $pacM = new Paciente();
            $idPac = 0;
            if (method_exists($pacM, 'obtenerPorUsuario')) {
                $paciente = $pacM->obtenerPorUsuario((int) $_SESSION['usuario']['id']);
                $idPac = $paciente['id'] ?? 0;
            } elseif (isset($_SESSION['usuario']['id_paciente'])) {
                $idPac = (int) $_SESSION['usuario']['id_paciente'];
            }
            if ($idPac) {
                $pagos = $pagoModel->listarPaciente($idPac);
            }
        } elseif ($rol === 'psicologo') {
            $psico = (new Psicologo())->obtenerPorUsuario((int) $_SESSION['usuario']['id']);
            $idPs = $psico['id'] ?? 0;
            if ($idPs) {
                $pagos = $pagoModel->listarPsicologo($idPs);
            }
        } else {
            $pagos = $pagoModel->listarTodos();
        }

        $this->render('pagos/listado', ['pagos' => $pagos, 'rol' => $rol]);
    }

    public function ver($id): void
    {
        $id = (int) $id;
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID inválido.</div>';
            return;
        }

        $pagoModel = new Pago();
        $extraModel = new PagoExtra();
        $ticketModel = new TicketPago();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
            $accion = $_POST['accion'];

            if ($accion === 'agregar_extra') {
                $desc = trim($_POST['descripcion'] ?? '');
                $monto = (float) ($_POST['monto'] ?? 0);
                if ($desc === '' || $monto <= 0) {
                    echo '<div class="alert alert-danger">Descripción y monto válidos requeridos.</div>';
                } else {
                    $extraModel->agregar($id, $desc, $monto);
                    $pagoModel->recalcularTotal($id);
                }
            }

            if ($accion === 'marcar_pagado') {
                $pagoModel->recalcularTotal($id);
                $pagoModel->marcarPagado($id);

                $ticket = $ticketModel->obtenerPorPago($id);
                if (!$ticket) {
                    try {
                        $codigo = 'TCK-' . strtoupper(bin2hex(random_bytes(4)));
                    } catch (Throwable $e) {
                        $codigo = 'TCK-' . time();
                    }
                    $numero = date('YmdHis') . '-' . $id;
                    $ticketUrl = RUTA . 'ticket/verPago/' . $id;
                    $qr = QRHelper::generarQR('PAGO:' . $id . ' URL:' . $ticketUrl, 'ticket_' . $id);

                    $ticketModel->crear([
                        'id_pago' => $id,
                        'codigo' => $codigo,
                        'numero_ticket' => $numero,
                        'qr_code' => $qr
                    ]);
                }
            }

            $this->safeRedirect(RUTA . 'pago/ver/' . $id);
            return;
        }

        $pago = $pagoModel->obtener($id);
        if (!$pago) {
            echo '<div class="alert alert-warning">Pago no encontrado.</div>';
            return;
        }
        $extras = $extraModel->listarPorPago($id);
        $ticket = $ticketModel->obtenerPorPago($id);

        $this->render('pagos/ver', [
            'pago' => $pago,
            'extras' => $extras,
            'ticket' => $ticket
        ]);
    }

    /** Registrar pago directamente a partir de una cita (usado por admin desde tickets) */
    public function registrarPorCita(): void
    {
        // Solo admin puede usar este endpoint
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado</div>';
            return;
        }

        $idCita = (int) ($_POST['id_cita'] ?? 0);
        $monto = isset($_POST['monto']) ? (float) $_POST['monto'] : 0.0;
        if (!$idCita) {
            $_SESSION['flash_error'] = 'ID de cita inválido';
            $this->safeRedirect(url('admin', 'tickets'));
            return;
        }

        $pagoModel = new Pago();
        $ticketModel = new TicketPago();

        try {
            $idPago = $pagoModel->registrarPagoCita($idCita, $monto > 0 ? $monto : 50.0);

            // Crear ticket si no existe
            $ticket = $ticketModel->obtenerPorPago($idPago);
            if (!$ticket) {
                try {
                    $codigo = 'TCK-' . strtoupper(bin2hex(random_bytes(4)));
                } catch (Throwable $e) {
                    $codigo = 'TCK-' . time();
                }
                $numero = date('YmdHis') . '-' . $idPago;
                $ticketUrl = RUTA . 'ticket/verPago/' . $idPago;
                $qr = QRHelper::generarQR('PAGO:' . $idPago . ' URL:' . $ticketUrl, 'ticket_' . $idPago);

                $ticketModel->crear([
                    'id_pago' => $idPago,
                    'codigo' => $codigo,
                    'numero_ticket' => $numero,
                    'qr_code' => $qr
                ]);
            }

            $_SESSION['flash_ok'] = 'Pago registrado correctamente (ID ' . $idPago . ')';
        } catch (Throwable $e) {
            error_log('Error registrarPorCita: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error registrando el pago: ' . $e->getMessage();
        }

        $this->safeRedirect(url('admin', 'tickets'));
    }

    /** Crear un pago pendiente para una cita (no marca como pagado) desde admin */
    public function crearPendientePorCita(): void
    {
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado</div>';
            return;
        }
        $idCita = (int) ($_POST['id_cita'] ?? 0);
        $monto = isset($_POST['monto']) ? (float) $_POST['monto'] : 50.0;
        if (!$idCita) {
            $_SESSION['flash_error'] = 'ID de cita inválido';
            $this->safeRedirect(url('admin', 'tickets'));
            return;
        }
        $pagoModel = new Pago();
        try {
            // If exists, do nothing; else create with pending state
            $exists = $pagoModel->obtenerPorCita($idCita);
            if (!$exists) {
                // Use crearParaCita but ensure it remains pending: crearParaCita marks pendiente by default
                $idPago = $pagoModel->crearParaCita($idCita, $monto);
                // Crear ticket asociado al pago (QR) pero mantener pago pendiente
                $ticketModel = new TicketPago();
                $ticket = $ticketModel->obtenerPorPago($idPago);
                if (!$ticket) {
                    try {
                        $codigo = 'TCK-' . strtoupper(bin2hex(random_bytes(4)));
                    } catch (Throwable $e) {
                        $codigo = 'TCK-' . time();
                    }
                    $numero = date('YmdHis') . '-' . $idPago;
                    $ticketUrl = RUTA . 'ticket/verPago/' . $idPago;
                    $qr = QRHelper::generarQR('PAGO:' . $idPago . ' URL:' . $ticketUrl, 'ticket_' . $idPago);

                    $ticketModel->crear([
                        'id_pago' => $idPago,
                        'codigo' => $codigo,
                        'numero_ticket' => $numero,
                        'qr_code' => $qr
                    ]);
                }
                $_SESSION['flash_ok'] = 'Pago pendiente creado (ID ' . $idPago . ')';
            } else {
                $_SESSION['flash_error'] = 'Ya existe un pago para esa cita (ID ' . $exists['id'] . ')';
            }
        } catch (Throwable $e) {
            error_log('Error crearPendientePorCita: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creando pago pendiente: ' . $e->getMessage();
        }
        $this->safeRedirect(url('admin', 'tickets'));
    }
}
