<?php
/** Controlador de Tickets (router: /ticket/accion/id) */
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

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
            $idPs = (int)$_SESSION['usuario']['id'];
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
        $rel = $ticket['qr_code'] ?? '';
        $rel = ltrim($rel,'/');
        // Si ya viene con public/ lo mantenemos, de lo contrario ajustamos a public/qrcodes/
        if(!str_starts_with($rel,'public/')){
            // casos antiguos guardaban 'qrcodes/...'
            if(str_starts_with($rel,'qrcodes/')){
                $rel = 'public/'.$rel;
            } elseif($rel && !str_contains($rel,'/')) {
                $rel = 'public/qrcodes/'.$rel; // solo nombre
            } else if(!$rel && isset($ticket['id_pago'])) {
                $rel = 'public/qrcodes/ticket_'.(int)$ticket['id_pago'].'.png';
            }
        }
        $base = __DIR__ . '/../public/';
    // $base apunta a ../public/, por lo que si $rel comienza con public/ quitamos ese prefijo para componer ruta física correcta
    $relFs = preg_replace('#^public/#','',$rel);
    $rutaFs = realpath($base.$relFs) ?: ($base.$relFs);
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
                $rel = 'public/qrcodes/ticket_'.$idPago.'.png';
                $relFs = 'qrcodes/ticket_'.$idPago.'.png';
                $rutaFs = realpath($base.$relFs) ?: ($base.$relFs);
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
}
