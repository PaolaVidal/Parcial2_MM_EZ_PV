<?php
/** Controlador base para cargar vistas */
require_once __DIR__ . '/../helpers/UrlHelper.php';

class BaseController {
    
    protected function view($ruta, $data = []){
        extract($data); // variables disponibles en la vista
        $rutaArchivo = __DIR__ . '/../Views/' . $ruta . '.php';
        if(file_exists($rutaArchivo)){
            include __DIR__ . '/../Views/layout/header.php';
            include $rutaArchivo;
            include __DIR__ . '/../Views/layout/footer.php';
        } else {
            echo "Vista no encontrada: $ruta";
        }
    }
    
    // Alias para compatibilidad
    protected function render($ruta, $data = []){
        extract($data);
        $rutaArchivo = __DIR__ . '/../Views/' . $ruta . '.php';
        if(file_exists($rutaArchivo)){
            include $rutaArchivo;
        } else {
            echo "Vista no encontrada: $ruta";
        }
    }
    
    protected function requireAdmin(): void {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado. Se requiere rol de administrador.</div>';
            exit;
        }
    }
    
    protected function requirePsicologo(): void {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'psicologo') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado. Se requiere rol de psicólogo.</div>';
            exit;
        }
    }
}
