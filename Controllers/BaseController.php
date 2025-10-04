<?php
/** Controlador base para cargar vistas */
require_once __DIR__ . '/../helpers/UrlHelper.php';

class BaseController {
    protected function view($ruta, $data = []){
        extract($data); // variables disponibles en la vista
        $rutaArchivo = __DIR__ . '/../views/' . $ruta . '.php';
        if(file_exists($rutaArchivo)){
            include __DIR__ . '/../views/layout/header.php';
            include $rutaArchivo;
            include __DIR__ . '/../views/layout/footer.php';
        } else {
            echo "Vista no encontrada: $ruta";
        }
    }
}
