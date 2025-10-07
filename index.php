<?php
/**
 * Front controller estilo proyecto anterior (param ?url=controlador/accion/id)
 * - Define constante RUTA siempre con slash final
 * - Redirección a login si no autenticado (excepto rutas públicas)
 * - Soporta early dispatch (placeholder) si luego agregas exportaciones
 * - Incluye navbar del ejemplo anterior
 */

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
define('RUTA', $scheme . '://' . $host . $basePath);

// Helper URL unificado
if(!function_exists('url')){
    function url(string $ctrl,string $accion='index', array $params=[]): string {
        $path = strtolower($ctrl);
        if($accion !== 'index') $path .= '/'. $accion;
        $q = '';
        if($params){
            $pairs=[];
            foreach($params as $k=>$v){ $pairs[] = urlencode($k).'='.urlencode($v); }
            $q='?'.implode('&',$pairs);
        }
        return RUTA . $path . $q;
    }
}

if (!session_id()) session_start();
if (!ob_get_level()) ob_start();

/**
 * Clase mínima para resolver rutas a controladores (similar a tu Contenido original)
 * Ajusta si tus controladores están en otra carpeta.
 */
class Contenido {
    public function obtenerContenido(string $pagina): string {
        $base = __DIR__ . '/controllers/'; // usa carpeta real en minúsculas
        $candidatos = [
            $base . ucfirst($pagina) . 'Controller.php',
            $base . strtolower($pagina) . 'Controller.php',
            $base . ucfirst($pagina) . '.php'
        ];
        foreach ($candidatos as $c) {
            if (file_exists($c)) return $c;
        }
        return ''; // no encontrado
    }
}

$contenido = new Contenido();

// ----- Autenticación básica (ahora pacientes NO inician sesión) -----
$urlActual = $_GET['url'] ?? '';
if (!isset($_SESSION['usuario'])) {
    $publica = false;
    // Prefijos / rutas públicas permitidas
    $rutasPublicas = ['','auth/login','public','public/'];
    foreach ($rutasPublicas as $rp) {
        if ($urlActual === $rp || str_starts_with($urlActual, $rp)) {
            $publica = true; break;
        }
    }
    if (!$publica) {
        header('Location: ' . RUTA . 'auth/login');
        exit;
    }
}

// ----- Early dispatch (descargas / acciones que no requieren layout) OPCIONAL -----
if (isset($_GET['url'])) {
    $parts = explode('/', $_GET['url']);
    $paginaEarly = $parts[0] ?? '';
    $accionEarly = $parts[1] ?? 'index';

    $rawEndpoints = [
        // Endpoints que deben devolver JSON o respuesta sin envolver en layout
        'psicologo' => ['slots','scanProcesar','scanConsultar','scanConfirmar'],
        'ticket'    => ['qr'],
        // ejemplo: 'estadisticas' => ['exportar_pdf','exportar_excel']
    ];

    if (isset($rawEndpoints[$paginaEarly]) && in_array($accionEarly, $rawEndpoints[$paginaEarly], true)) {
        $file = $contenido->obtenerContenido($paginaEarly);
        if ($file) {
            require_once $file;
            $class1 = ucfirst($paginaEarly) . 'Controller';
            $class2 = strtolower($paginaEarly) . 'controller';
            $clase = class_exists($class1) ? $class1 : (class_exists($class2) ? $class2 : null);
            if ($clase && method_exists($clase, $accionEarly)) {
                $ctrl = new $clase();
                if (isset($parts[2])) {
                    $ctrl->{$accionEarly}($parts[2]);
                } else {
                    $ctrl->{$accionEarly}();
                }
                exit;
            }
        }
    }
}

// ---- Bridge para estilo antiguo ?c=Admin&a=citas ----
if(empty($_GET['url']) && (isset($_GET['c']) || isset($_GET['a']))){
    $c = strtolower($_GET['c'] ?? 'public');
    $a = $_GET['a'] ?? 'index';
    $_GET['url'] = $c . '/' . $a;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <title>Plataforma Citas Psicología</title>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary-color:#4e8098; --secondary-color:#f4a261; --accent-color:#2a9d8f; }
        body { background:#f8f9fa; }
        .navbar { background:var(--primary-color)!important; }
        .navbar-brand,.nav-link { color:#fff!important; }
        .nav-link:hover { color:var(--secondary-color)!important; }
        .btn-primary { background:var(--accent-color); border-color:var(--accent-color); }
        .btn-primary:hover { background:#248277; border-color:#248277; }
    </style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= RUTA; ?>">
                <i class="fas fa-brain me-2"></i><span>Psicología</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['usuario'])):
                        $rol = $_SESSION['usuario']['rol'] ?? '';
                        if ($rol === 'psicologo'):
                            $seg = explode('/', $urlActual);
                            $seg0 = strtolower($seg[0] ?? '');
                            $seg1 = strtolower($seg[1] ?? 'index');
                            if(!function_exists('isAct')){
                                function isAct($seg0,$seg1,$c,$a='index'){ return $seg0===$c && $seg1===$a; }
                            }
                    ?>
    <li class="nav-item"><a class="nav-link <?= isAct($seg0,$seg1,'psicologo','dashboard')?'active fw-semibold':'' ?>" href="<?= url('psicologo','dashboard') ?>"><i class="fas fa-chart-pie me-1"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link <?= isAct($seg0,$seg1,'psicologo','citas')?'active fw-semibold':'' ?>" href="<?= url('psicologo','citas') ?>"><i class="fas fa-list me-1"></i>Mis Citas</a></li>
    <li class="nav-item"><a class="nav-link <?= isAct($seg0,$seg1,'psicologo','scan')?'active fw-semibold':'' ?>" href="<?= url('psicologo','scan') ?>"><i class="fas fa-qrcode me-1"></i>Escanear</a></li>
    <li class="nav-item"><a class="nav-link <?= ($seg0==='ticket')?'active fw-semibold':'' ?>" href="<?= url('ticket') ?>"><i class="fas fa-ticket me-1"></i>Tickets</a></li>
                    <?php elseif ($rol === 'admin'): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('admin','dashboard') ?>">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('admin','pacientes') ?>">Pacientes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('admin','psicologos') ?>">Psicólogos</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('admin','citas') ?>">Citas</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('admin','pagos') ?>">Pagos</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('admin','solicitudes') ?>">Solicitudes</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= url('admin','horarios') ?>">Horarios</a></li>
    <?php endif; ?>
    <li class="nav-item">
        <span class="nav-link">Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?> (<?= htmlspecialchars($rol) ?>)</span>
    </li>
    <li class="nav-item"><a class="nav-link" href="<?= RUTA ?>auth/logout"><i class="fas fa-sign-out-alt me-1"></i>Salir</a></li>
<?php else: ?>
    <li class="nav-item"><a class="nav-link" href="<?= RUTA ?>auth/login"><i class="fas fa-sign-in-alt me-1"></i>Iniciar sesión</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= RUTA ?>public/disponibilidad">Psicólogos</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= RUTA ?>public/portal">Portal Paciente</a></li>
<?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container mt-4">
<?php
if (isset($_GET['url'])) {
    $datos  = explode('/', $_GET['url']);
    $pagina = strtolower($datos[0] ?: 'inicio');
    $accion = $datos[1] ?? 'index';

    $file = $contenido->obtenerContenido($pagina);
    if ($file) {
        require_once $file;
        $class1 = ucfirst($pagina) . 'Controller';
        $class2 = strtolower($pagina) . 'controller';
        $clase = class_exists($class1) ? $class1 : (class_exists($class2) ? $class2 : null);
        if ($clase) {
            $ctrl = new $clase();
            if (method_exists($ctrl, $accion)) {
                (isset($datos[2])) ? $ctrl->{$accion}($datos[2]) : $ctrl->{$accion}();
            } else {
                http_response_code(404);
                echo '<div class="alert alert-danger">Acción no encontrada</div>';
            }
        } else {
            http_response_code(404);
            echo '<div class="alert alert-warning">Controlador no encontrado</div>';
        }
    } else {
        http_response_code(404);
        echo '<div class="alert alert-warning">Página no encontrada</div>';
    }
} else {
    // Página inicial por defecto
    echo '<div class="p-5 bg-white rounded shadow-sm">
            <h1 class="h4 mb-3">Bienvenido</h1>
            <p class="mb-0">Usa el menú para navegar.</p>
          </div>';
}
?>
</main>

<footer class="text-center py-4 small text-muted">
    &copy; <?= date('Y'); ?> Plataforma Psicología
</footer>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html>
<?php
function usuarioRol(): string {
    return $_SESSION['usuario']['rol'] ?? '';
}
function requiereRol(array $roles): void {
    if (!isset($_SESSION['usuario']) || !in_array(usuarioRol(), $roles, true)) {
        http_response_code(403);
        echo '<div class="alert alert-danger">Acceso denegado</div>';
        exit;
    }
}
