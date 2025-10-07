<?php
/** Controlador de Tickets (router: /ticket/accion/id) */
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/../helpers/PDFHelper.php';

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
        // Si es psicólogo autenticado mostrar sus tickets
        if(isset($_SESSION['usuario']) && ($_SESSION['usuario']['rol'] ?? '')==='psicologo'){
            $idPs = $this->mapUserToPsicologoId();
            if($idPs <= 0){
                echo '<div class="alert alert-warning">No se encontró el identificador del psicólogo para este usuario.</div>';
                return;
            }
            $model = new TicketPago();
            $tickets = $model->listarPorPsicologo($idPs);
            $this->render('tickets/lista_psicologo',[ 'tickets'=>$tickets ]);
            return;
        }
        echo '<h2 class="h5">Tickets</h2><p>Indica un ID: /ticket/ver/{id}</p>';
    }

    // /ticket/ver/{idTicket}
    public function ver($id = 0): void {
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
        // Obtener datos de pago asociados para mostrar en ticket
        $pagoModel = new Pago();
        $pago = null;
        if(isset($ticket['id_pago'])){
            $pago = $pagoModel->obtener((int)$ticket['id_pago']);
        }
        $this->render('tickets/ver', ['ticket' => $ticket, 'pago'=>$pago]);
    }

    // /ticket/verPago/{idPago}
    public function verPago($idPago = 0): void {
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
        $pagoModel = new Pago();
        $pago = $pagoModel->obtener($idPago);
        $this->render('tickets/ver', ['ticket' => $ticket, 'pago'=>$pago]);
    }

    // /ticket/consultarPago/{idPago} - Endpoint JSON para scanner
    public function consultarPago($idPago = 0): void {
        header('Content-Type: application/json');
        $idPago = (int)$idPago;
        if ($idPago <= 0) {
            echo json_encode(['ok'=>false, 'msg'=>'ID de pago inválido']);
            return;
        }
        $model = new TicketPago();
        $ticket = $model->obtenerPorPago($idPago);
        if (!$ticket) {
            echo json_encode(['ok'=>false, 'msg'=>'Ticket no encontrado']);
            return;
        }
        $pagoModel = new Pago();
        $pago = $pagoModel->obtener($idPago);
        
        echo json_encode([
            'ok' => true,
            'ticket' => $ticket,
            'pago' => $pago,
            'msg' => 'Ticket encontrado'
        ]);
    }

    /** Endpoint proxy: /ticket/qr/{idTicket}?dl=1
     * Sirve la imagen del QR aunque la carpeta qrcodes no sea accesible directamente.
     * Si falta intenta regenerar (para tickets ligados a pago) usando patrón ticket_{id_pago}.png
     */
    public function qr($id = 0): void {
        $id = (int)$id;
        if($id<=0){ http_response_code(400); echo 'ID inválido'; return; }
        $ticketM = new TicketPago();
        $ticket = $ticketM->obtener($id);
        if(!$ticket){ http_response_code(404); echo 'Ticket no encontrado'; return; }
            $rel = trim($ticket['qr_code'] ?? '');
            $rel = ltrim($rel,'/');
            if(!$rel && isset($ticket['id_pago'])){
                $rel = 'qrcodes/ticket_'.(int)$ticket['id_pago'].'.png';
            }
            // Normalizar casos antiguos que guardaron con public/
            $rel = preg_replace('#^public/#','',$rel);
            if($rel && !str_starts_with($rel,'qrcodes/')){
                if(!str_contains($rel,'/')) $rel = 'qrcodes/'.$rel; // solo nombre
            }
            $base = __DIR__ . '/../public/';
            $rutaFs = realpath($base.$rel) ?: ($base.$rel);
        if(!is_file($rutaFs) || filesize($rutaFs)===0){
            // intentar regenerar si hay id_pago
            $idPago = (int)($ticket['id_pago'] ?? 0);
            if($idPago>0){
                try {
                    $nuevo = 'ticket_'.$idPago.'.png';
                    $rutaGen = QRHelper::generarQR('PAGO:'.$idPago,'ticket',$nuevo); // devuelve qrcodes/ticket_id.png
                    // actualizar BD
                    $pdo = (new TicketPago())->db; // acceso base
                } catch(Throwable $e){ /* ignorar */ }
                // recomputar ruta
                    $rel = 'qrcodes/ticket_'.$idPago.'.png';
                    $rutaFs = realpath($base.$rel) ?: ($base.$rel);
            }
        }
        if(!is_file($rutaFs)){
            http_response_code(404); echo 'QR no disponible'; return; }
        $download = isset($_GET['dl']);
        header('Content-Type: image/png');
        if($download){ header('Content-Disposition: attachment; filename="ticket_'.$id.'.png"'); }
        header('Content-Length: '.filesize($rutaFs));
        readfile($rutaFs);
    }

    // /ticket/pdf/{idTicket} - Generar PDF del ticket
    public function pdf($id = 0): void {
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID inválido';
            return;
        }
        
        $model = new TicketPago();
        $ticket = $model->obtener($id);
        
        if (!$ticket) {
            http_response_code(404);
            echo 'Ticket no encontrado';
            return;
        }
        
        // Obtener datos de pago asociados
        $pagoModel = new Pago();
        $pago = null;
        if (isset($ticket['id_pago'])) {
            $pago = $pagoModel->obtener((int)$ticket['id_pago']);
        }
        
        // Generar PDF
        try {
            PDFHelper::generarTicketPDF($ticket, $pago);
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Error generando PDF: ' . $e->getMessage();
        }
    }

    /** Mapear usuario logueado (rol psicologo) a Psicologo.id */
    private function mapUserToPsicologoId(): int {
        if(!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol']??'')!=='psicologo') return 0;
        if(isset($_SESSION['psicologo_id']) && (int)$_SESSION['psicologo_id']>0) return (int)$_SESSION['psicologo_id'];
        $idUsuario = (int)$_SESSION['usuario']['id'];
        $m = new Psicologo();
        $row = $m->obtenerPorUsuario($idUsuario);
        if($row){ $_SESSION['psicologo_id'] = (int)$row['id']; return (int)$row['id']; }
        return 0;
    }
}
