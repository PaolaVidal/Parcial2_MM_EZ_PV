<?php
/** Controlador de Tickets */
require_once 'BaseController.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Pago.php';

class TicketController extends BaseController {
    public function ver(){
        $id = $_GET['id'] ?? null;
        if(!$id){ echo 'ID requerido'; return; }
        $model = new TicketPago();
        $ticket = $model->obtener($id);
        $this->view('tickets/ver', ['ticket'=>$ticket]);
    }

    public function verPago(){
        $idPago = $_GET['id'] ?? null;
        if(!$idPago){ echo 'ID pago requerido'; return; }
        $model = new TicketPago();
        $ticket = $model->obtenerPorPago($idPago);
        $this->view('tickets/ver', ['ticket'=>$ticket]);
    }
}
