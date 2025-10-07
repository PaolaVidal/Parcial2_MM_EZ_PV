<?php
/** Portal público de Paciente (sin credenciales de Usuario) */
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../models/HorarioPsicologo.php';

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

    // Lista de psicólogos activos con sus horarios (no requiere DUI)
    public function disponibilidad(): void {
        $psicologos = (new Psicologo())->listarActivos();
        $horarioModel = new HorarioPsicologo();
        
        // Agregar horarios a cada psicólogo
        foreach($psicologos as &$p) {
            $p['horarios'] = $horarioModel->listarPorPsicologo($p['id']);
        }
        
        $this->render('disponibilidad', ['psicologos'=>$psicologos]);
    }

    // Login unificado: DUI + Código en una sola pantalla
    public function acceso(): void {
        $pac = new Paciente();
        $msg = '';
        $dui = $_POST['dui'] ?? '';
        $codigo = $_POST['codigo'] ?? '';
        
        if($_SERVER['REQUEST_METHOD']==='POST'){
            if(empty($dui) || empty($codigo)){
                $msg = 'Por favor ingresa tu DUI y código de acceso';
            } else {
                $row = $pac->getByDuiCodigo($dui, $codigo);
                if($row){
                    $_SESSION['paciente_id'] = $row['id'];
                    $_SESSION['paciente_nombre'] = $row['nombre'];
                    $_SESSION['paciente_dui'] = $row['dui'];
                    header('Location: '.RUTA.'public/panel');
                    exit;
                } else {
                    $msg = 'DUI o código de acceso incorrecto';
                }
            }
        }
        $this->render('acceso', compact('msg','dui','codigo'));
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
        unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
        header('Location: '.RUTA.'public/portal');
        exit;
    }

    private function requirePortal(): array {
        if (!isset($_SESSION['paciente_id'])) {
            header('Location: ' . RUTA . 'public/acceso'); exit;
        }
        $pac = (new Paciente())->getById((int)$_SESSION['paciente_id']);
        if (!$pac) {
            unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
            header('Location: ' . RUTA . 'public/acceso'); exit;
        }
        return $pac;
    }
}