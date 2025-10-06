<?php
/** Portal público de Paciente (sin credenciales de Usuario) */
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/SolicitudCambio.php';

class PublicController {

    private string $viewsPath;
    public function __construct() {
        $this->viewsPath = __DIR__ . '/../Views/public/';
    }

    private function render(string $vista, array $data = []): void {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista pública faltante: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }

    // Página principal portal
    public function portal(): void {
        $this->render('portal');
    }

    // Lista de psicólogos activos (no requiere DUI)
    public function disponibilidad(): void {
        $psicologos = (new Psicologo())->listarActivos();
        $this->render('disponibilidad', ['psicologos'=>$psicologos]);
    }

    // Paso 1: ingresar DUI
    public function buscar_dui(): void {
        $pac = new Paciente();
        $msg = '';
        $dui = $_POST['dui'] ?? '';
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $row = $pac->getByDui($dui);
            if($row){
                $_SESSION['tmp_dui'] = $row['dui'];
                header('Location: '.RUTA.'public/buscarDuiCodigo');
                exit;
            } else {
                $msg = 'DUI no encontrado';
            }
        }
        $this->render('public/buscar_dui', compact('msg','dui'));
    }

    public function buscarDuiCodigo(): void {
        if(empty($_SESSION['tmp_dui'])){ header('Location: '.RUTA.'public/buscar_dui'); exit; }
        $pac = new Paciente();
        $msg = '';
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $dui = $_SESSION['tmp_dui'];
            $codigo = $_POST['codigo'] ?? '';
            $row = $pac->getByDuiCodigo($dui,$codigo);
            if($row){
                unset($_SESSION['tmp_dui']);
                $_SESSION['paciente_id'] = $row['id'];
                header('Location: '.RUTA.'public/panel');
                exit;
            } else {
                $msg = 'Código inválido';
            }
        }
        $dui = $_SESSION['tmp_dui'];
        $this->render('public/acceso', compact('msg','dui'));
    }

    // Panel tras validar código
    public function panel(): void {
        $paciente = $this->requirePortal();
        $this->render('panel', ['paciente'=>$paciente]);
    }

    public function citas(): void {
        $paciente = $this->requirePortal();
        $citas = (new Cita())->listarPaciente($paciente['id']);
        $this->render('citas', ['citas'=>$citas]);
    }

    public function pagos(): void {
        $paciente = $this->requirePortal();
        $pagos = (new Pago())->listarPaciente($paciente['id']);
        $this->render('pagos', ['pagos'=>$pagos]);
    }

    public function solicitud(): void {
        $paciente = $this->requirePortal();
        $msg=''; $ok=false;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $campo = trim($_POST['campo'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            if ($campo === '' || $valor === '') {
                $msg = 'Completa los campos.';
            } else {
                (new SolicitudCambio())->crear($paciente['id'], $campo, $valor);
                $msg='Solicitud enviada.';
                $ok=true;
            }
        }
        $this->render('solicitud', ['msg'=>$msg,'ok'=>$ok]);
    }

    public function salir(): void {
        unset($_SESSION['portal_paciente']);
        header('Location: '.RUTA.'public/portal');
        exit;
    }

    private function requirePortal(): array {
        if (!isset($_SESSION['portal_paciente'])) {
            header('Location: ' . RUTA . 'public/portal'); exit;
        }
        $pac = (new Paciente())->getByCodigo($_SESSION['portal_paciente']['codigo']);
        if (!$pac) {
            unset($_SESSION['portal_paciente']);
            header('Location: ' . RUTA . 'public/portal'); exit;
        }
        return $pac;
    }
}