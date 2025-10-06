<?php
/**
 * Helper para generar códigos QR utilizando la librería phpqrcode
 * Se espera que el archivo vendor/phpqrcode/qrlib.php exista.
 */
class QRHelper {
    /**
     * Genera un QR y retorna ruta relativa pública.
     * @param string $texto Contenido a codificar (token)
     * @param string $nombreArchivoPrefix Prefijo (ej: 'cita')
     * @param string|null $nombreForzado Nombre de archivo exacto (sin ruta) opcional.
     */
    public static function generarQR(string $texto, string $nombreArchivoPrefix = 'qr', ?string $nombreForzado = null){        
        $rutaLib = __DIR__ . '/../vendor/phpqrcode/qrlib.php';
        if(!file_exists($rutaLib)) {
            throw new Exception('Librería phpqrcode no encontrada. Asegúrate de descargar qrlib.php.');
        }
        require_once $rutaLib;

        $dir = __DIR__ . '/../public/qrcodes/';
        if(!is_dir($dir)) { mkdir($dir, 0777, true); }

        if($nombreForzado){
            // Sanitizar nombre permitido
            $nf = preg_replace('/[^A-Za-z0-9_\-]/','_', $nombreForzado);
            if(!str_ends_with($nf, '.png')) $nf .= '.png';
            $nombre = $nf;
        } else {
            $nombre = $nombreArchivoPrefix . '_' . time() . '_' . rand(100,999) . '.png';
        }

        $rutaFisica = $dir . $nombre;           // Ruta en disco
        $rutaPublica = 'qrcodes/' . $nombre;    // Ruta relativa accesible desde el navegador

        // Generar QR (nivel de corrección L, tamaño 4)
        QRcode::png($texto, $rutaFisica, QR_ECLEVEL_L, 4);

        return $rutaPublica; // Guardamos esta ruta en la BD (o token aparte según diseño)
    }
}
