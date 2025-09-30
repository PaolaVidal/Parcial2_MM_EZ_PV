<?php

class Rutas {
    public static $rutas = [
        "autor"     => "controladores/AutorController.php",
        "libro"     => "controladores/LibroController.php",
        "categoria" => "controladores/CategoriaController.php"
    ];

    public static function obtenerRuta($clave) {
        $ruta = self::$rutas[$clave] ?? null;
        return $ruta ?: "vistas/404.php"; 
    }
}

?>
