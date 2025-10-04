<?php
/** Controlador de Pagos */
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/PagoExtra.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/../helpers/UrlHelper.php';

class PagoController extends BaseController {
    public function index(){
        $model = new Pago();
        $pagos = $model->listar();
        $this->view('pagos/listado', ['pagos'=>$pagos]);
    }

    public function ver(){
        $id = $_GET['id'] ?? null;
        if(!$id){ echo 'ID requerido'; return; }
        $pagoModel = new Pago();
        $extraModel = new PagoExtra();
        $ticketModel = new TicketPago();

        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion'])){
            if($_POST['accion']==='agregar_extra'){
                $extraModel->agregar($id, $_POST['descripcion'], $_POST['monto']);
                $pagoModel->recalcularTotal($id);
            }
            if($_POST['accion']==='marcar_pagado'){
                $pagoModel->recalcularTotal($id);
                $pagoModel->marcarPagado($id);
                // Crear ticket si no existe
                $ticket = $ticketModel->obtenerPorPago($id);
                if(!$ticket){
                    $codigo = 'TCK-' . strtoupper(bin2hex(random_bytes(4)));
                    $numero = date('YmdHis') . '-' . $id;
                    $url = base_url() . 'index.php?controller=Ticket&action=verPago&id=' . $id;
                    $qr = QRHelper::generarQR('PAGO:' . $id . ' URL:' . $url, 'ticket_'.$id);
                    $ticketId = $ticketModel->crear([
                        'id_pago' => $id,
                        'codigo' => $codigo,
                        'numero_ticket' => $numero,
                        'qr_code' => $qr
                    ]);
                }
            }
        }

        $pago = $pagoModel->obtener($id);
        $extras = $extraModel->listarPorPago($id);
        $ticket = $ticketModel->obtenerPorPago($id);

        $this->view('pagos/ver', [
            'pago'=>$pago,
            'extras'=>$extras,
            'ticket'=>$ticket
        ]);
    }
}
