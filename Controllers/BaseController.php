<?php
/** Controlador base para cargar vistas */
require_once __DIR__ . '/../helpers/UrlHelper.php';

class BaseController
{

    protected function view($ruta, $data = [])
    {
        extract($data); // variables disponibles en la vista
        $rutaArchivo = __DIR__ . '/../Views/' . $ruta . '.php';
        if (file_exists($rutaArchivo)) {
            include __DIR__ . '/../Views/layout/header.php';
            include $rutaArchivo;
            include __DIR__ . '/../Views/layout/footer.php';
        } else {
            echo "Vista no encontrada: $ruta";
        }
    }

    // Alias para compatibilidad
    protected function render($ruta, $data = [])
    {
        extract($data);
        $rutaArchivo = __DIR__ . '/../Views/' . $ruta . '.php';
        if (file_exists($rutaArchivo)) {
            include $rutaArchivo;
        } else {
            echo "Vista no encontrada: $ruta";
        }
    }

    protected function requireAdmin(): void
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado. Se requiere rol de administrador.</div>';
            exit;
        }
        // Revalidar estado activo
        require_once __DIR__ . '/../Models/Usuario.php';
        $um = new Usuario();
        $estado = $um->obtenerEstado((int) $_SESSION['usuario']['id']);
        if ($estado !== 'activo') {
            session_destroy();
            http_response_code(403);
            echo '<div class="alert alert-danger">Tu cuenta ha sido desactivada. Contacta al administrador.</div>';
            exit;
        }
    }

    protected function requirePsicologo(): void
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'psicologo') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado. Se requiere rol de psicólogo.</div>';
            exit;
        }
        // Revalidar estado activo
        require_once __DIR__ . '/../Models/Usuario.php';
        $um = new Usuario();
        $estado = $um->obtenerEstado((int) $_SESSION['usuario']['id']);
        if ($estado !== 'activo') {
            session_destroy();
            http_response_code(403);
            echo '<div class="alert alert-danger">Tu cuenta ha sido desactivada. Contacta al administrador.</div>';
            exit;
        }
    }

    /**
     * Redirección segura centralizada para evitar "Cannot modify header information".
     * - Limpia todos los buffers
     * - Usa header Location si aún no se enviaron headers
     * - Fallback JS + meta refresh si ya hubo salida
     */
    protected function safeRedirect(string $url): void
    {
        // Limpia buffers previos
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        // Usar redirección con código 303 para PRG (Post/Redirect/Get) seguro
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Location: ' . $url, true, 303);
        }
        // Fallback HTML + JS (aunque se haya enviado header) para navegadores que muestren pantalla en blanco
        $escaped = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"utf-8\"/>";
        echo "<meta http-equiv=\"refresh\" content=\"0;url=$escaped\"/>";
        echo "<title>Redireccionando...</title></head><body style=\"background:#111;color:#eee;font-family:Arial;display:flex;align-items:center;justify-content:center;height:100vh;\">";
        echo "<div>Redireccionando... Si no ocurre automáticamente <a style=\"color:#4ea\" href=\"$escaped\">haz clic aquí</a>.</div>";
        echo "<script>window.location.replace(" . json_encode($url) . ");</script></body></html>";
        exit;
    }
}
