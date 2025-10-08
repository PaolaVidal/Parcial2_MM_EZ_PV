<?php

/**
 * DEPRECADO: El proyecto ahora usa el front controller en /public/index.php con un router dinámico.
 * Este archivo se mantiene solo por compatibilidad si alguna parte antigua aún lo requiere.
 */
class Rutas {
    /**
     * Rutas legacy actualizadas para apuntar a los controladores reales del nuevo módulo.
     * Puedes seguir accediendo con /?url=cita por compatibilidad si aún usas este archivo,
     * aunque ahora el front controller principal está en public/index.php.
     */
    public static $rutas = [
        'auth'   => 'controllers/AuthController.php',
        'cita'   => 'controllers/CitaController.php',
        'pago'   => 'controllers/PagoController.php',
        'ticket' => 'controllers/TicketController.php'
    ];

    public static function obtenerRuta($clave) {
        $ruta = self::$rutas[strtolower($clave)] ?? null;
        return $ruta ?: 'views/404.php'; 
    }
}

?>
