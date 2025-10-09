<?php
/**
 * Helper para generar gráficas usando GD (librería nativa de PHP)
 * Compatible con DomPDF para incluir en PDFs
 */
class ChartHelper
{

    /**
     * Genera un gráfico de barras
     * @return string Base64 data URL de la imagen PNG
     */
    public static function generarBarChart(array $data, array $labels, string $titulo = '', int $width = 600, int $height = 400): string
    {
        // Crear imagen
        $img = imagecreatetruecolor($width, $height);

        // Colores
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $negro = imagecolorallocate($img, 0, 0, 0);
        $azul = imagecolorallocate($img, 13, 110, 253);
        $gris = imagecolorallocate($img, 200, 200, 200);
        $grisClaro = imagecolorallocate($img, 240, 240, 240);

        // Fondo blanco
        imagefill($img, 0, 0, $blanco);

        // Márgenes
        $margenIzq = 60;
        $margenDer = 20;
        $margenArr = 60;
        $margenAbj = 80;

        $anchoGrafico = $width - $margenIzq - $margenDer;
        $altoGrafico = $height - $margenArr - $margenAbj;

        // Título
        if ($titulo) {
            $fuente = 5;
            imagestring($img, $fuente, ($width - strlen($titulo) * imagefontwidth($fuente)) / 2, 20, $titulo, $negro);
        }

        // Validar que haya datos ANTES de calcular max() (PHP 8 lanza ValueError en max([]))
        $numBarras = count($data);
        if ($numBarras === 0) {
            $msg = 'Sin datos para mostrar';
            imagestring($img, 3, ($width - strlen($msg) * imagefontwidth(3)) / 2, $height / 2, $msg, $negro);
            ob_start();
            imagepng($img);
            $contenido = ob_get_clean();
            imagedestroy($img);
            return 'data:image/png;base64,' . base64_encode($contenido);
        }

        // Calcular valores máximos de forma segura
        $maxValor = max($data) ?: 1;
        $escalaY = $altoGrafico / $maxValor;

        // Dibujar ejes
        imageline($img, $margenIzq, $margenArr, $margenIzq, $height - $margenAbj, $negro);
        imageline($img, $margenIzq, $height - $margenAbj, $width - $margenDer, $height - $margenAbj, $negro);

        // Dibujar líneas de grid horizontales
        $numLineas = 5;
        for ($i = 0; $i <= $numLineas; $i++) {
            $y = $margenArr + ($altoGrafico * $i / $numLineas);
            imageline($img, $margenIzq, $y, $width - $margenDer, $y, $grisClaro);

            // Etiqueta del eje Y
            $valor = $maxValor - ($maxValor * $i / $numLineas);
            imagestring($img, 3, 10, $y - 7, number_format($valor, 0), $negro);
        }

        // Dibujar barras
        $anchoBarra = ($anchoGrafico / $numBarras) * 0.7;
        $espacioBarra = ($anchoGrafico / $numBarras) * 0.3;

        foreach ($data as $i => $valor) {
            $x = $margenIzq + ($i * ($anchoGrafico / $numBarras)) + ($espacioBarra / 2);
            $altoBarra = $valor * $escalaY;
            $y = $height - $margenAbj - $altoBarra;

            // Dibujar barra
            imagefilledrectangle($img, $x, $y, $x + $anchoBarra, $height - $margenAbj, $azul);

            // Valor encima de la barra
            $valorStr = number_format($valor, 0);
            $anchoTexto = strlen($valorStr) * imagefontwidth(3);
            imagestring($img, 3, $x + ($anchoBarra - $anchoTexto) / 2, $y - 15, $valorStr, $negro);

            // Etiqueta en el eje X
            if (isset($labels[$i])) {
                $label = $labels[$i];
                $anchoLabel = strlen($label) * imagefontwidth(3);
                imagestring($img, 3, $x + ($anchoBarra - $anchoLabel) / 2, $height - $margenAbj + 10, $label, $negro);
            }
        }

        // Convertir a base64
        ob_start();
        imagepng($img);
        $contenido = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($contenido);
    }

    /**
     * Genera un gráfico de líneas
     * @return string Base64 data URL de la imagen PNG
     */
    public static function generarLineChart(array $data, array $labels, string $titulo = '', int $width = 600, int $height = 400): string
    {
        // Crear imagen
        $img = imagecreatetruecolor($width, $height);

        // Colores
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $negro = imagecolorallocate($img, 0, 0, 0);
        $verde = imagecolorallocate($img, 25, 135, 84);
        $verdeClaro = imagecolorallocate($img, 180, 220, 200); // Verde claro sin transparencia
        $grisClaro = imagecolorallocate($img, 240, 240, 240);

        // Fondo blanco
        imagefill($img, 0, 0, $blanco);

        // Márgenes
        $margenIzq = 80;
        $margenDer = 20;
        $margenArr = 60;
        $margenAbj = 80;

        $anchoGrafico = $width - $margenIzq - $margenDer;
        $altoGrafico = $height - $margenArr - $margenAbj;

        // Título
        if ($titulo) {
            imagestring($img, 5, ($width - strlen($titulo) * imagefontwidth(5)) / 2, 20, $titulo, $negro);
        }

        // Preparar conteo antes de usar max() para evitar ValueError si array vacío
        $numPuntos = count($data);
        if ($numPuntos === 0) {
            $msg = 'Sin datos';
            imagestring($img, 3, ($width - strlen($msg) * imagefontwidth(3)) / 2, $height / 2, $msg, $negro);
            ob_start();
            imagepng($img);
            $contenido = ob_get_clean();
            imagedestroy($img);
            return 'data:image/png;base64,' . base64_encode($contenido);
        }
        // Calcular valores (ya sabemos que hay >=1 punto)
        $maxValor = max($data) ?: 1;
        $escalaY = $altoGrafico / $maxValor;

        // Validar que haya al menos 2 puntos para líneas significativas
        if ($numPuntos < 2) {
            $msg = 'Datos insuficientes (minimo 2 puntos)';
            imagestring($img, 3, ($width - strlen($msg) * imagefontwidth(3)) / 2, $height / 2, $msg, $negro);
            ob_start();
            imagepng($img);
            $contenido = ob_get_clean();
            imagedestroy($img);
            return 'data:image/png;base64,' . base64_encode($contenido);
        }

        // Dibujar ejes
        imageline($img, $margenIzq, $margenArr, $margenIzq, $height - $margenAbj, $negro);
        imageline($img, $margenIzq, $height - $margenAbj, $width - $margenDer, $height - $margenAbj, $negro);

        // Grid horizontal
        for ($i = 0; $i <= 5; $i++) {
            $y = $margenArr + ($altoGrafico * $i / 5);
            imageline($img, $margenIzq, $y, $width - $margenDer, $y, $grisClaro);
            $valor = $maxValor - ($maxValor * $i / 5);
            imagestring($img, 3, 10, $y - 7, '$' . number_format($valor, 0), $negro);
        }

        // Dibujar línea y área
        $puntos = [];
        $puntos[] = $margenIzq;
        $puntos[] = $height - $margenAbj;

        for ($i = 0; $i < $numPuntos; $i++) {
            $x = $margenIzq + ($i * $anchoGrafico / ($numPuntos - 1));
            $y = $height - $margenAbj - ($data[$i] * $escalaY);
            $puntos[] = $x;
            $puntos[] = $y;
        }

        $puntos[] = $margenIzq + $anchoGrafico;
        $puntos[] = $height - $margenAbj;

        // Área rellena (polígono)
        imagefilledpolygon($img, $puntos, count($puntos) / 2, $verdeClaro);

        // Línea principal
        for ($i = 0; $i < $numPuntos - 1; $i++) {
            $x1 = $margenIzq + ($i * $anchoGrafico / ($numPuntos - 1));
            $y1 = $height - $margenAbj - ($data[$i] * $escalaY);
            $x2 = $margenIzq + (($i + 1) * $anchoGrafico / ($numPuntos - 1));
            $y2 = $height - $margenAbj - ($data[$i + 1] * $escalaY);

            imageline($img, $x1, $y1, $x2, $y2, $verde);
            imagesetthickness($img, 3);
        }
        imagesetthickness($img, 1);

        // Puntos
        for ($i = 0; $i < $numPuntos; $i++) {
            $x = $margenIzq + ($i * $anchoGrafico / ($numPuntos - 1));
            $y = $height - $margenAbj - ($data[$i] * $escalaY);
            imagefilledellipse($img, $x, $y, 8, 8, $verde);
            imageellipse($img, $x, $y, 8, 8, $blanco);

            // Etiqueta
            if (isset($labels[$i])) {
                $label = $labels[$i];
                $anchoLabel = strlen($label) * imagefontwidth(3);
                imagestring($img, 3, $x - ($anchoLabel / 2), $height - $margenAbj + 10, $label, $negro);
            }
        }

        // Convertir a base64
        ob_start();
        imagepng($img);
        $contenido = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($contenido);
    }

    /**
     * Genera un gráfico de dona/pie
     * @return string Base64 data URL de la imagen PNG
     */
    public static function generarPieChart(array $data, array $labels, string $titulo = '', int $width = 500, int $height = 400): string
    {
        // Crear imagen
        $img = imagecreatetruecolor($width, $height);

        // Colores
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $negro = imagecolorallocate($img, 0, 0, 0);

        // Colores para las porciones
        $colores = [
            imagecolorallocate($img, 255, 193, 7),  // Amarillo (pendiente)
            imagecolorallocate($img, 25, 135, 84),   // Verde (realizada)
            imagecolorallocate($img, 220, 53, 69)    // Rojo (cancelada)
        ];

        // Fondo blanco
        imagefill($img, 0, 0, $blanco);

        // Título
        if ($titulo) {
            imagestring($img, 5, ($width - strlen($titulo) * imagefontwidth(5)) / 2, 20, $titulo, $negro);
        }

        // Calcular total
        $total = array_sum($data);
        if ($total == 0) {
            $msg = 'Sin datos para mostrar';
            imagestring($img, 3, ($width - strlen($msg) * imagefontwidth(3)) / 2, $height / 2, $msg, $negro);
            ob_start();
            imagepng($img);
            $contenido = ob_get_clean();
            imagedestroy($img);
            return 'data:image/png;base64,' . base64_encode($contenido);
        }

        // Centro del círculo
        $centroX = $width / 2 - 60;
        $centroY = $height / 2 + 20;
        $radio = min($width, $height) / 3;

        // Dibujar gráfico de pie
        $anguloInicio = 0;
        foreach ($data as $i => $valor) {
            $angulo = ($valor / $total) * 360;
            $color = $colores[$i % count($colores)];

            imagefilledarc(
                $img,
                $centroX,
                $centroY,
                $radio * 2,
                $radio * 2,
                $anguloInicio,
                $anguloInicio + $angulo,
                $color,
                IMG_ARC_PIE
            );

            $anguloInicio += $angulo;
        }

        // Dibujar leyenda
        $leyendaX = $centroX + $radio + 40;
        $leyendaY = $centroY - ($radio / 2);

        foreach ($data as $i => $valor) {
            $color = $colores[$i % count($colores)];
            $porcentaje = round(($valor / $total) * 100, 1);

            // Cuadrado de color
            imagefilledrectangle(
                $img,
                $leyendaX,
                $leyendaY + ($i * 30),
                $leyendaX + 15,
                $leyendaY + ($i * 30) + 15,
                $color
            );

            // Texto
            $texto = $labels[$i] . ': ' . $valor . ' (' . $porcentaje . '%)';
            imagestring($img, 3, $leyendaX + 25, $leyendaY + ($i * 30) + 2, $texto, $negro);
        }

        // Convertir a base64
        ob_start();
        imagepng($img);
        $contenido = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($contenido);
    }
}
