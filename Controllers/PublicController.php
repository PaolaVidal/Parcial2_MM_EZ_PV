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
    public function buscarDui(): void {
        $msg = '';
        $dui = trim($_POST['dui'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($dui === '') {
                $msg = 'Ingresa DUI.';
            } else {
                $pac = (new Paciente())->getByDui($dui);
                if ($pac) {
                    header('Location: ' . RUTA . 'public/acceso?dui=' . urlencode($dui));
                    exit;
                } else {
                    $msg = 'No encontrado.';
                }
            }
        }
        $this->render('buscar_dui', ['msg'=>$msg,'dui'=>$dui]);
    }

    // Paso 2: validar código de acceso
    public function acceso(): void {
        $dui = trim($_GET['dui'] ?? '');
        $msg = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dui    = trim($_POST['dui'] ?? '');
            $codigo = trim($_POST['codigo'] ?? '');
            if ($dui === '' || $codigo === '') {
                $msg = 'Campos requeridos.';
            } else {
                $pac = (new Paciente())->getByDui($dui);
                if ($pac && hash_equals($pac['codigo_acceso'], $codigo)) {
                    $_SESSION['portal_paciente'] = [
                        'id_paciente' => $pac['id'],
                        'dui'         => $pac['dui'],
                        'codigo'      => $pac['codigo_acceso']
                    ];
                    header('Location: ' . RUTA . 'public/panel');
                    exit;
                } else {
                    $msg = 'Código inválido.';
                }
            }
        }
        $this->render('acceso', ['dui'=>$dui,'msg'=>$msg]);
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