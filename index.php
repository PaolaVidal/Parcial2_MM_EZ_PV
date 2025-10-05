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

// ----- Autenticación básica (similar a tu lógica anterior) -----
$urlActual = $_GET['url'] ?? '';
if (!isset($_SESSION['usuario'])) {
    $publica = false;
    // Rutas públicas (login, registro y página inicial)
    if ($urlActual === '' || strpos($urlActual, 'auth') === 0 || strpos($urlActual, 'login') === 0) {
        $publica = true;
    }
    if (!$publica) {
        header('Location: ' . RUTA . 'auth/login'); // ajusta a tu controlador real de login
        exit;
    }
}

// ----- Early dispatch (descargas / acciones que no requieren layout) OPCIONAL -----
if (isset($_GET['url'])) {
    $parts = explode('/', $_GET['url']);
    $paginaEarly = $parts[0] ?? '';
    $accionEarly = $parts[1] ?? 'index';

    $rawEndpoints = [
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
                    <?php if (isset($_SESSION['usuario'])): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= RUTA; ?>cita"><i class="fas fa-calendar-alt me-1"></i> Citas</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= RUTA; ?>pago"><i class="fas fa-dollar-sign me-1"></i> Pagos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= RUTA; ?>ticket"><i class="fas fa-ticket me-1"></i> Tickets</a></li>
                        <li class="nav-item">
                            <span class="nav-link">Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?>
                                (<?= htmlspecialchars($_SESSION['usuario']['rol'] ?? '') ?>)</span>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= RUTA; ?>auth/logout"><i class="fas fa-sign-out-alt me-1"></i> Salir</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= RUTA; ?>auth/login"><i class="fas fa-sign-in-alt me-1"></i> Iniciar sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= RUTA; ?>auth/registrar"><i class="fas fa-user-plus me-1"></i> Registrarse</a></li>
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
                if (isset($datos[2])) {
                    $ctrl->{$accion}($datos[2]);
                } else {
                    $ctrl->{$accion}();
                }
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
