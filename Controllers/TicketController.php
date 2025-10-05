<?php
/** Controlador de Tickets (router: /ticket/accion/id) */
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Pago.php';

class TicketController {

    private string $viewsPath;

    public function __construct() {
        $this->viewsPath = __DIR__ . '/../views/'; // cambia a ../vistas/ si es necesario
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
        // Opcional: listado de tickets
        echo '<h2 class="h5">Tickets</h2><p>Indica un ID: /ticket/ver/{id}</p>';
    }

    // /ticket/ver/{idTicket}
    public function ver($id): void {
        $id = (int)$id;
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID inválido.</div>';
            return;
        }
        $model  = new TicketPago();
        $ticket = $model->obtener($id);
        if (!$ticket) {
            echo '<div class="alert alert-warning">Ticket no encontrado.</div>';
            return;
        }
        $this->render('tickets/ver', ['ticket' => $ticket]);
    }

    // /ticket/verPago/{idPago}
    public function verPago($idPago): void {
        $idPago = (int)$idPago;
        if ($idPago <= 0) {
            echo '<div class="alert alert-danger">ID de pago inválido.</div>';
            return;
        }
        $model  = new TicketPago();
        $ticket = $model->obtenerPorPago($idPago);
        if (!$ticket) {
            echo '<div class="alert alert-warning">Ticket para pago no encontrado.</div>';
            return;
        }
        $this->render('tickets/ver', ['ticket' => $ticket]);
    }
}
