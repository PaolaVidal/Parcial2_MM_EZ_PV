<?php
function usuarioRol(): string
{
    return $_SESSION['usuario']['rol'] ?? '';
}

function requiereRol(array $roles): void
{
    if (!isset($_SESSION['usuario']) || !in_array(usuarioRol(), $roles, true)) {
        http_response_code(403);
        echo '<div class="alert alert-danger">Acceso denegado</div>';
        exit;
    }
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
define('RUTA', $scheme . '://' . $host . $basePath);
// Activar URLs "bonitas" solo si el servidor tiene reglas de rewrite configuradas
if (!defined('USE_PRETTY_URLS')) {
    define('USE_PRETTY_URLS', false); // cambiar a true si agregas .htaccess/mod_rewrite
}

// Helper URL unificado
if (!function_exists('url')) {
    function url(string $ctrl, string $accion = 'index', array $params = []): string
    {
        $path = strtolower($ctrl);
        if ($accion !== 'index') {
            $path .= '/' . $accion;
        }
        $queryPairs = [];
        if (!USE_PRETTY_URLS) {
            // Estilo index.php?url=controlador/accion
            $base = RUTA . 'index.php?url=' . $path;
            foreach ($params as $k => $v) {
                $queryPairs[] = urlencode($k) . '=' . urlencode($v);
            }
            return $base . ($queryPairs ? '&' . implode('&', $queryPairs) : '');
        } else {
            // Estilo /controlador/accion opcional
            foreach ($params as $k => $v) {
                $queryPairs[] = urlencode($k) . '=' . urlencode($v);
            }
            return RUTA . $path . ($queryPairs ? '?' . implode('&', $queryPairs) : '');
        }
    }
}

if (!session_id())
    session_start();
if (!ob_get_level())
    ob_start();

/**
 * Clase mínima para resolver rutas a controladores (similar a tu Contenido original)
 * Ajusta si tus controladores están en otra carpeta.
 */
class Contenido
{
    public function obtenerContenido(string $pagina): string
    {
        $base = __DIR__ . '/controllers/'; // usa carpeta real en minúsculas
        $candidatos = [
            $base . ucfirst($pagina) . 'Controller.php',
            $base . strtolower($pagina) . 'Controller.php',
            $base . ucfirst($pagina) . '.php'
        ];
        foreach ($candidatos as $c) {
            if (file_exists($c))
                return $c;
        }
        return ''; // no encontrado
    }
}

$contenido = new Contenido();

// ----- Autenticación básica (ahora pacientes NO inician sesión) -----
$rawUrl = $_GET['url'] ?? '';
// Normalizar url: eliminar prefijos accidentales que no sean alfanuméricos (p. ej. '-' introducido por JS)
$sanUrl = preg_replace('/^[^A-Za-z0-9]+/', '', $rawUrl);
// Si la URL normalizada está vacía o es solo '/', no establecer $_GET['url'] para que
// el flujo por defecto muestre el portal público en la rama sin 'url'.
if ($sanUrl === '' || $sanUrl === '/') {
    unset($_GET['url']);
    $urlActual = '';
} else {
    $_GET['url'] = $sanUrl;
    $urlActual = $sanUrl;
}
if (!isset($_SESSION['usuario'])) {
    $publica = false;
    // Prefijos / rutas públicas permitidas
    $rutasPublicas = ['', 'auth/login', 'public', 'public/'];
    foreach ($rutasPublicas as $rp) {
        if ($urlActual === $rp || str_starts_with($urlActual, $rp)) {
            $publica = true;
            break;
        }
    }

    // Permitir acceso a tickets si el paciente tiene sesión pública
    if (!$publica && isset($_SESSION['paciente_id']) && str_starts_with($urlActual, 'ticket/')) {
        $publica = true;
    }

    if (!$publica) {
        // Usar salida mínima antes de redirigir para evitar mezclar layouts
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Location: ' . RUTA . 'auth/login');
        exit;
    }
}

// ----- Early dispatch (safe) --------------------------------------------------
// For some endpoints (exports, AJAX) we must execute the controller action before
// printing the main layout so the response is pure (JSON / PDF) and not mixed with HTML.
if (isset($_GET['url'])) {
    $parts = explode('/', $_GET['url']);
    $paginaEarly = $parts[0] ?? '';
    $accionEarly = $parts[1] ?? 'index';

    // Small whitelist of controller actions that should be callable without layout
    $rawEndpoints = [
        // psicologo: slots (AJAX) and estadisticas (export PDF/Excel)
        'psicologo' => ['slots', 'scanProcesar', 'scanConsultar', 'scanConfirmar', 'guardarEvaluacion', 'estadisticas'],
        // ticket endpoints that return files
        'ticket' => ['qr', 'pdf', 'consultarPago'],
        // cita: pdf generation
        'cita' => ['pdf'],
        // admin: include estadisticas export and administrative AJAX actions
        'admin' => ['procesarSolicitud', 'citas', 'estadisticas'],
        // public: panel can export historial/graficas
        'public' => ['panel', 'salir'],
    ];

    if (isset($rawEndpoints[$paginaEarly]) && in_array($accionEarly, $rawEndpoints[$paginaEarly], true)) {
        // Determine whether this request is an export or ajax call
        $isExport = isset($_GET['export']);
        $isAjax = isset($_GET['ajax']);

        // Don't dispatch estadisticas or public panel as raw unless export is present
        if (($accionEarly === 'estadisticas' || ($paginaEarly === 'public' && $accionEarly === 'panel')) && !$isExport) {
            // allow normal layout flow for non-export requests (rendered inside index.php layout)
        // Exception: admin/citas should only be dispatched as raw when called by AJAX (e.g. ajax=slots/list)
        } elseif ($paginaEarly === 'admin' && $accionEarly === 'citas' && !$isAjax) {
            // Let the normal layout flow continue for full page requests
        } else {
            $file = $contenido->obtenerContenido($paginaEarly);
            if ($file) {
                // Capture any accidental output when requiring the controller
                ob_start();
                require_once $file;
                $requireOutput = ob_get_clean();
                if ($requireOutput !== '') {
                    error_log('Early-dispatch require output for ' . $file . ': ' . substr($requireOutput, 0, 1000));
                }

                // Ensure all output buffers are cleared before the controller emits its response
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }

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
                    // Controller already emitted the response (JSON/PDF) — stop the front controller
                    exit;
                }
            }
        }
    }
}

// ---- Bridge para estilo antiguo ?c=Admin&a=citas ----
if (empty($_GET['url']) && (isset($_GET['c']) || isset($_GET['a']))) {
    $c = strtolower($_GET['c'] ?? 'public');
    $a = $_GET['a'] ?? 'index';
    $_GET['url'] = $c . '/' . $a;
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Plataforma Citas Psicología</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e8098;
            --secondary-color: #f4a261;
            --accent-color: #2a9d8f;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        .navbar {
            background: var(--primary-color) !important;
        }

        .navbar-brand,
        .nav-link {
            color: #fff !important;
        }

        .nav-link:hover {
            color: var(--secondary-color) !important;
        }

        .btn-primary {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background: #248277;
            border-color: #248277;
        }

        .nav-link.btn {
            border-radius: 20px;
        }

        footer a {
            transition: opacity 0.3s;
        }

        footer a:hover {
            opacity: 1 !important;
            text-decoration: underline !important;
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <?php
                // Logo redirige al dashboard según el rol
                $homeUrl = RUTA;
                if (isset($_SESSION['usuario'])) {
                    $rolHeader = $_SESSION['usuario']['rol'] ?? '';
                    if ($rolHeader === 'admin') {
                        $homeUrl = url('admin', 'dashboard');
                    } elseif ($rolHeader === 'psicologo') {
                        $homeUrl = url('psicologo', 'dashboard');
                    } elseif ($rolHeader === 'paciente') {
                        $homeUrl = url('public', 'panel');
                    }
                }
                ?>
                <a class="navbar-brand d-flex align-items-center" href="<?= $homeUrl; ?>">
                    <i class="fas fa-brain me-2"></i><span>Psicología</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                    aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
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
                                if (!function_exists('isAct')) {
                                    function isAct($seg0, $seg1, $c, $a = 'index')
                                    {
                                        return $seg0 === $c && $seg1 === $a;
                                    }
                                }
                                ?>
                                <li class="nav-item"><a
                                        class="nav-link <?= isAct($seg0, $seg1, 'psicologo', 'estadisticas') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('psicologo', 'estadisticas') ?>"><i
                                            class="fas fa-chart-bar me-1"></i>Estadísticas</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= isAct($seg0, $seg1, 'psicologo', 'consultarPaciente') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('psicologo', 'consultarPaciente') ?>"><i
                                            class="fas fa-search me-1"></i>Consultas Pacientes</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= isAct($seg0, $seg1, 'psicologo', 'citas') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('psicologo', 'citas') ?>"><i class="fas fa-list me-1"></i>Citas</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($seg0 === 'ticket') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('ticket') ?>"><i class="fas fa-ticket me-1"></i>Tickets</a></li>
                            <?php elseif ($rol === 'admin'):
                                $seg = explode('/', $urlActual);
                                $seg0 = strtolower($seg[0] ?? '');
                                $seg1 = strtolower($seg[1] ?? 'index');
                                ?>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'estadisticas') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'estadisticas') ?>"><i
                                            class="fas fa-chart-bar me-1"></i>Estadísticas</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'pacientes') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'pacientes') ?>"><i class="fas fa-users me-1"></i>Pacientes</a>
                                </li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'psicologos') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'psicologos') ?>"><i
                                            class="fas fa-user-md me-1"></i>Psicólogos</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'especialidad') ? 'active fw-semibold' : '' ?>"
                                        href="<?= RUTA ?>index.php?url=especialidad"><i
                                            class="fas fa-graduation-cap me-1"></i>Especialidades</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'citas') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'citas') ?>"><i class="fas fa-calendar-check me-1"></i>Citas</a>
                                </li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'tickets') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'tickets') ?>"><i class="fas fa-ticket me-1"></i>Tickets</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'solicitudes') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'solicitudes') ?>"><i
                                            class="fas fa-inbox me-1"></i>Solicitudes</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($seg0 === 'admin' && $seg1 === 'horarios') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('admin', 'horarios') ?>"><i class="fas fa-clock me-1"></i>Horarios</a>
                                </li>
                            <?php elseif ($rol === 'paciente'): ?>
                                <li class="nav-item"><a
                                        class="nav-link <?= ($urlActual === 'public/panel') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('public', 'panel') ?>"><i class="fas fa-home me-1"></i>Panel</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= str_starts_with($urlActual, 'public/citas') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('public', 'citas') ?>"><i class="fas fa-calendar-alt me-1"></i>Citas</a>
                                </li>
                                <li class="nav-item"><a
                                        class="nav-link <?= str_starts_with($urlActual, 'public/pagos') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('public', 'pagos') ?>"><i
                                            class="fas fa-file-invoice-dollar me-1"></i>Pagos</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= str_starts_with($urlActual, 'public/disponibilidad') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('public', 'disponibilidad') ?>"><i
                                            class="fas fa-user-md me-1"></i>Psicólogos</a></li>
                                <li class="nav-item"><a
                                        class="nav-link <?= str_starts_with($urlActual, 'public/solicitud') ? 'active fw-semibold' : '' ?>"
                                        href="<?= url('public', 'solicitud') ?>"><i class="fas fa-edit me-1"></i>Mis Datos</a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <span class="nav-link">Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?>
                                    (<?= htmlspecialchars($rol) ?>)</span>
                            </li>
                            <li class="nav-item">
                                <?php if ($rol === 'paciente'): ?>
                                    <a class="nav-link" href="<?= url('public', 'salir') ?>"><i
                                            class="fas fa-sign-out-alt me-1"></i>Salir</a>
                                <?php else: ?>
                                    <a class="nav-link" href="<?= RUTA ?>auth/logout"><i
                                            class="fas fa-sign-out-alt me-1"></i>Salir</a>
                                <?php endif; ?>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a
                                    class="nav-link <?= ($urlActual === 'public/disponibilidad' || str_starts_with($urlActual, 'public/disponibilidad')) ? 'active fw-semibold' : '' ?>"
                                    href="<?= RUTA ?>public/disponibilidad">
                                    <i class="fas fa-user-md me-1"></i>Nuestros Psicólogos
                                </a></li>
                            <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2 px-3"
                                    href="<?= RUTA ?>public/acceso">
                                    <i class="fas fa-user-circle me-1"></i>Portal Paciente
                                </a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-4">
        <?php
        if (isset($_GET['url'])) {
            $datos = explode('/', $_GET['url']);
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
            // Página inicial por defecto: Portal del Paciente
            require_once __DIR__ . '/controllers/PublicController.php';
            $publicCtrl = new PublicController();
            $publicCtrl->portal();
        }
        ?>
    </main>

    <footer class="bg-light border-top mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-8">
                    <p class="text-muted mb-2 small">
                        <i class="fas fa-heart text-danger"></i>
                        &copy; <?= date('Y'); ?> Plataforma de Psicología - Cuidando tu salud mental
                    </p>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-shield-alt"></i> Tus datos están protegidos y son confidenciales
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="small text-muted">
                        <a href="<?= RUTA ?>auth/login" class="text-decoration-none text-muted" style="opacity: 0.5;">
                            <i class="fas fa-lock"></i> Acceso Personal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        crossorigin="anonymous"></script>
    <script src="<?= RUTA ?>public/js/responsive-tables.js"></script>
</body>

</html>
