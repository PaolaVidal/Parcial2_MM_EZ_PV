<?php
/** Controlador de Pagos (router: /pago/accion/id) */
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/PagoExtra.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class PagoController {

    private string $viewsPath;

    public function __construct() {
        // Ajusta mayúsculas según tu carpeta real
        $this->viewsPath = __DIR__ . '/../Views/';
    }

    private function render(string $vista, array $data = []): void {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista no encontrada: '.htmlspecialchars($vista).'</div>';
            return;
        }
        extract($data);
        require $file;
    }

    public function index(): void {
        if(!isset($_SESSION['usuario'])){
            echo '<div class="alert alert-warning">No autenticado.</div>';
            return;
        }
        $rol = $_SESSION['usuario']['rol'];
        $pagoModel = new Pago();
        $pagos = [];

        if ($rol === 'paciente') {
            $paciente = (new Paciente())->obtenerPorUsuario((int)$_SESSION['usuario']['id']);
            $idPac = $paciente['id'] ?? 0;
            if ($idPac) {
                $pagos = $pagoModel->listarPaciente($idPac);
            }
        } elseif ($rol === 'psicologo') {
            $psico = (new Psicologo())->obtenerPorUsuario((int)$_SESSION['usuario']['id']);
            $idPs = $psico['id'] ?? 0;
            if ($idPs) {
                $pagos = $pagoModel->listarPsicologo($idPs);
            }
        } else {
            $pagos = $pagoModel->listarTodos();
        }

        $this->render('pagos/listado', ['pagos'=>$pagos, 'rol'=>$rol]);
    }

    public function ver($id): void {
        $id = (int)$id;
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID inválido.</div>';
            return;
        }

        $pagoModel   = new Pago();
        $extraModel  = new PagoExtra();
        $ticketModel = new TicketPago();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
            $accion = $_POST['accion'];

            if ($accion === 'agregar_extra') {
                $desc  = trim($_POST['descripcion'] ?? '');
                $monto = (float)($_POST['monto'] ?? 0);
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
                        'id_pago'       => $id,
                        'codigo'        => $codigo,
                        'numero_ticket' => $numero,
                        'qr_code'       => $qr
                    ]);
                }
            }

            header('Location: ' . RUTA . 'pago/ver/' . $id);
            exit;
        }

        $pago = $pagoModel->obtener($id);
        if (!$pago) {
            echo '<div class="alert alert-warning">Pago no encontrado.</div>';
            return;
        }
        $extras = $extraModel->listarPorPago($id);
        $ticket = $ticketModel->obtenerPorPago($id);

        $this->render('pagos/ver', [
            'pago'   => $pago,
            'extras' => $extras,
            'ticket' => $ticket
        ]);
    }
}
