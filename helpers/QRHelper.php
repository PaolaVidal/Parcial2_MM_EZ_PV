<?php
/**
 * Helper para generar códigos QR utilizando la librería phpqrcode
 * Se espera que el archivo vendor/phpqrcode/qrlib.php exista.
 */
class QRHelper {
    public static function generarQR($texto, $nombreArchivoPrefix = 'qr'){        
        $rutaLib = __DIR__ . '/../vendor/phpqrcode/qrlib.php';
        if(!file_exists($rutaLib)) {
            throw new Exception('Librería phpqrcode no encontrada. Asegúrate de descargar qrlib.php.');
        }
        require_once $rutaLib;

        $dir = __DIR__ . '/../public/qrcodes/';
        if(!is_dir($dir)) { mkdir($dir, 0777, true); }

        $nombre = $nombreArchivoPrefix . '_' . time() . '_' . rand(100,999) . '.png';
        $rutaFisica = $dir . $nombre;           // Ruta en disco
        $rutaPublica = 'qrcodes/' . $nombre;    // Ruta relativa accesible desde el navegador

        // Generar QR (nivel de corrección L, tamaño 4)
        QRcode::png($texto, $rutaFisica, QR_ECLEVEL_L, 4);

        return $rutaPublica; // Guardamos esta ruta en la BD
    }
}
