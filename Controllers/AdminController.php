<?php


require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../models/Paciente.php';

class SolicitudController {

    private string $viewsPath;
    public function __construct() {
        $this->viewsPath = __DIR__ . '/../Views/admin/solicitudes/';
    }

    private function render(string $vista, array $data=[]){
        $f = $this->viewsPath . $vista . '.php';
        if(!file_exists($f)){ echo '<div class="alert alert-danger">Vista admin faltante: '.htmlspecialchars($vista).'</div>'; return; }
        extract($data); require $f;
    }

    private function requireAdmin(): void {
        if(!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'admin'){
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado.</div>';
            exit;
        }
    }

    public function index(): void {
        $this->requireAdmin();
        $solModel = new SolicitudCambio();
        $pendientes = $solModel->listarPendientes();
        $this->render('listado', ['pendientes'=>$pendientes]);
    }

    public function procesar($id): void {
        $this->requireAdmin();
        $id = (int)$id;
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location: '.RUTA.'admin/solicitudes');
            exit;
        }

        $accion        = $_POST['accion'] ?? '';
        $campoOriginal = $_POST['campo_original'] ?? '';
        $valorNuevo    = $_POST['valor_nuevo'] ?? '';
        $idPaciente    = (int)($_POST['id_paciente'] ?? 0);

        $estado = $accion === 'aprobar' ? 'aprobado' : ($accion === 'rechazar' ? 'rechazado' : '');
        if($estado){
            $solModel  = new SolicitudCambio();
            $solicitud = $solModel->obtener($id);
            if($solicitud){
                $solModel->actualizarEstado($id,$estado);
                if($estado==='aprobado' && $idPaciente>0){
                    (new Paciente())->updateCampoPermitido($idPaciente,$campoOriginal,$valorNuevo);
                }
            }
        }
        header('Location: '.RUTA.'admin/solicitudes');
        exit;
    }
}