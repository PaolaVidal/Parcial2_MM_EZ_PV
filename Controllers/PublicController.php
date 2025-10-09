<?php
/** Portal público de Paciente (sin credenciales de Usuario) */
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../models/Especialidad.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../models/HorarioPsicologo.php';
require_once __DIR__ . '/BaseController.php';

class PublicController extends BaseController
{

    private string $viewsPath;
    public function __construct()
    {
        $this->viewsPath = __DIR__ . '/../Views/public/';
    }

    // Render específico para vistas públicas dentro de subcarpeta, mantiene compatibilidad con BaseController
    protected function render($vista, $data = []): void
    {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista pública faltante: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        // Asegurar que RUTA esté disponible
        if (!defined('RUTA')) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
            define('RUTA', $scheme . '://' . $host . $basePath);
        }
        extract($data);
        require $file;
    }

    // Página principal portal
    public function portal(): void
    {
        $this->render('portal');
    }

    // Lista de psicólogos activos con filtros de búsqueda y especialidad
    public function disponibilidad(): void
    {
        $nombre = trim($_GET['q'] ?? '');
        $idEsp = isset($_GET['esp']) && ctype_digit($_GET['esp']) ? (int) $_GET['esp'] : null;
        $psicoModel = new Psicologo();
        $psicologos = $psicoModel->listarActivosFiltrado($nombre, $idEsp);
        $horarioModel = new HorarioPsicologo();
        foreach ($psicologos as &$p) {
            $p['horarios'] = $horarioModel->listarPorPsicologo($p['id']);
        }
        unset($p);
        $especialidades = (new Especialidad())->listarActivas();
        $this->render('disponibilidad', [
            'psicologos' => $psicologos,
            'q' => $nombre,
            'esp' => $idEsp,
            'especialidades' => $especialidades
        ]);
    }

    // Login solo por Código de Acceso (DUI eliminado del formulario)
    public function acceso(): void
    {
        $pac = new Paciente();
        $msg = '';
        $codigo = $_POST['codigo'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($codigo)) {
                $msg = 'Ingresa tu código de acceso';
            } else {
                $row = $pac->getByCodigo($codigo);
                if ($row) {
                    // Sesión previa de otro rol: opcionalmente preservar, pero aquí la sobreescribimos si no es admin/psicologo
                    // Variables específicas (compatibilidad con código existente)
                    $_SESSION['paciente_id'] = $row['id'];
                    $_SESSION['paciente_nombre'] = $row['nombre'];
                    if (isset($row['dui'])) {
                        $_SESSION['paciente_dui'] = $row['dui'];
                    }
                    // Sesión unificada estilo usuario (para navbar y comprobaciones de rol)
                    $_SESSION['usuario'] = [
                        'id' => $row['id'],           // usamos id de paciente ya que no existe Usuario asociado
                        'id_paciente' => $row['id'],  // redundante pero explícito
                        'nombre' => $row['nombre'],
                        'rol' => 'paciente'
                    ];
                    $this->safeRedirect(RUTA . 'public/panel');
                    return;
                } else {
                    $msg = 'Código de acceso incorrecto';
                }
            }
        }
        // Compatibilidad: la vista ya no usa $dui, pero lo dejamos vacío por si se referencia
        $dui = '';
        $this->render('acceso', compact('msg', 'dui', 'codigo'));
    }

    // Panel tras validar código
    public function panel(): void
    {
        $paciente = $this->requirePortal();
        $this->render('panel', ['paciente' => $paciente]);
    }

    public function citas(): void
    {
        $paciente = $this->requirePortal();
        $citas = (new Cita())->listarPaciente($paciente['id']);
        $this->render('citas', ['citas' => $citas]);
    }

    public function pagos(): void
    {
        $paciente = $this->requirePortal();
        $pagos = (new Pago())->listarPaciente($paciente['id']);
        $this->render('pagos', ['pagos' => $pagos]);
    }

    public function solicitud(): void
    {
        $paciente = $this->requirePortal();
        $msg = '';
        $ok = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campo = trim($_POST['campo'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            if ($campo === '' || $valor === '') {
                $msg = 'Completa los campos.';
            } else {
                (new SolicitudCambio())->crear($paciente['id'], $campo, $valor);
                $msg = 'Solicitud enviada exitosamente. Será revisada por un administrador.';
                $ok = true;
            }
        }
        // Obtener historial de solicitudes del paciente
        $historial = (new SolicitudCambio())->listarPorPaciente($paciente['id']);
        $this->render('solicitud', ['msg' => $msg, 'ok' => $ok, 'historial' => $historial]);
    }

    public function salir(): void
    {
        unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
        if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['rol'] ?? '') === 'paciente') {
            unset($_SESSION['usuario']);
        }
        $this->safeRedirect(RUTA . 'public/portal');
    }

    private function requirePortal(): array
    {
        if (!isset($_SESSION['paciente_id'])) {
            $this->safeRedirect(RUTA . 'public/acceso'); // termina ejecución
        }
        $pac = (new Paciente())->getById((int) $_SESSION['paciente_id']);
        if (!$pac) {
            unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
            $this->safeRedirect(RUTA . 'public/acceso'); // termina ejecución
        }
        // Si existe campo estado y no está activo, forzar salida
        if (isset($pac['estado']) && $pac['estado'] !== 'activo') {
            unset($_SESSION['paciente_id'], $_SESSION['paciente_nombre'], $_SESSION['paciente_dui']);
            if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['rol'] ?? '') === 'paciente') {
                unset($_SESSION['usuario']);
            }
            $this->safeRedirect(RUTA . 'public/acceso?msg=Cuenta%20inactiva');
        }
        // Fallback: si el login se hizo antes de introducir la sesión unificada o se perdió $_SESSION['usuario']
        if (!isset($_SESSION['usuario']) || (($_SESSION['usuario']['rol'] ?? '') !== 'paciente')) {
            $_SESSION['usuario'] = [
                'id' => (int) $pac['id'],
                'id_paciente' => (int) $pac['id'],
                'nombre' => $pac['nombre'] ?? 'Paciente',
                'rol' => 'paciente'
            ];
        }
        return $pac; // siempre retorna array si no redirige
    }

    // safeRedirect ahora heredado de BaseController
}