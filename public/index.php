<?php
/**
 * Front Controller
 * - Inicia sesión
 * - Autoload básico de models, controllers, helpers
 * - Ruteo por parámetros GET: ?controller=Nombre&action=metodo
 */

session_start();

// Autoload sencillo
spl_autoload_register(function($clase){
    $paths = [
        __DIR__ . '/../models/' . $clase . '.php',
        __DIR__ . '/../controllers/' . $clase . '.php',
        __DIR__ . '/../helpers/' . $clase . '.php'
    ];
    foreach($paths as $p){ if(file_exists($p)) { require_once $p; return; } }
});

$controllerName = $_GET['controller'] ?? 'Cita';
$action = $_GET['action'] ?? 'index';

// Rutas públicas permitidas sin login
$publicControllers = ['Auth'];
if(!isset($_SESSION['usuario']) && !in_array($controllerName, $publicControllers)){
    header('Location: index.php?controller=Auth&action=login');
    exit;
}

// Router simple
$className = $controllerName . 'Controller';
$controllerPath = __DIR__ . '/../controllers/' . $className . '.php';

if(!file_exists($controllerPath)){
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}
require_once $controllerPath;

if(!class_exists($className)){
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}

$controller = new $className();

if(!method_exists($controller, $action)){
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}

// Ejecutar acción
$controller->$action();
