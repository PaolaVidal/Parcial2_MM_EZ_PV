<?php
/**
 * Script de prueba para ChartHelper
 * Genera gráficas de ejemplo y las muestra en HTML
 */

require_once __DIR__ . '/../helpers/ChartHelper.php';

// Datos de prueba
$mesesData = [45, 52, 48, 61, 58, 63, 55, 49, 54, 60, 57, 62];
$mesesLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

$estadosData = [120, 85, 15];
$estadosLabels = ['Realizadas', 'Pendientes', 'Canceladas'];

$ingresosData = [8500, 9200, 8800, 10500, 9800, 11000, 9500, 8700, 9300, 10200, 9700, 10800];
$ingresosLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

// Generar gráficas
$chartBarras = ChartHelper::generarBarChart($mesesData, $mesesLabels, 'Citas por Mes - 2024', 700, 300);
$chartPie = ChartHelper::generarPieChart($estadosData, $estadosLabels, 'Distribucion de Citas por Estado', 600, 350);
$chartLinea = ChartHelper::generarLineChart($ingresosData, $ingresosLabels, 'Ingresos Mensuales - 2024', 700, 300);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Gráficas - ChartHelper</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2C3E50;
            text-align: center;
        }
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container h2 {
            color: #3498DB;
            margin-top: 0;
        }
        img {
            width: 100%;
            height: auto;
            display: block;
        }
        .info {
            background: #e8f4f8;
            padding: 15px;
            border-left: 4px solid #3498DB;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h1>🎨 Test de ChartHelper - Gráficas con GD</h1>
    
    <div class="info">
        <strong>✓ ChartHelper funcionando correctamente</strong><br>
        Las siguientes gráficas se generaron con la librería GD nativa de PHP (sin dependencias externas).
        Estas imágenes se pueden embeber directamente en PDFs usando DomPDF.
    </div>
    
    <div class="chart-container">
        <h2>📊 Gráfico de Barras - Citas por Mes</h2>
        <img src="<?php echo $chartBarras; ?>" alt="Gráfico de barras">
    </div>
    
    <div class="chart-container">
        <h2>🥧 Gráfico de Pie/Dona - Estados de Citas</h2>
        <img src="<?php echo $chartPie; ?>" alt="Gráfico de pie">
    </div>
    
    <div class="chart-container">
        <h2>📈 Gráfico de Líneas - Ingresos Mensuales</h2>
        <img src="<?php echo $chartLinea; ?>" alt="Gráfico de líneas">
    </div>
    
    <div class="info" style="background: #d4edda; border-color: #28a745;">
        <strong>🚀 Siguiente paso:</strong> Estas gráficas ya están integradas en el exportador de estadísticas.
        Para probar en PDF, ve a Admin > Estadísticas > Exportar PDF.
    </div>
</body>
</html>
