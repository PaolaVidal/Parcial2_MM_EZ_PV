<?php
/**
 * Script para regenerar QRs de tickets con formato correcto PAGO:ID
 * Ejecutar desde: http://localhost/.../tools/regenerar_qr_tickets.php
 */

// Configuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/../Models/BaseModel.php';
require_once __DIR__ . '/../Models/TicketPago.php';

echo "<h2>Regeneración de QRs de Tickets</h2>";
echo "<p>Este script regenerará todos los QRs de tickets con el formato correcto: <code>PAGO:ID</code></p>";
echo "<hr>";

try {
    // Obtener todos los tickets
    $ticketModel = new TicketPago();
    $pdo = $ticketModel->pdo();
    $stmt = $pdo->query("SELECT id, id_pago, qr_code FROM Ticket_Pago WHERE estado='activo'");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Tickets encontrados: <strong>" . count($tickets) . "</strong></p>";
    echo "<ol>";
    
    $regenerados = 0;
    $errores = 0;
    
    foreach ($tickets as $ticket) {
        $idTicket = (int)$ticket['id'];
        $idPago = (int)$ticket['id_pago'];
        $qrActual = $ticket['qr_code'];
        
        echo "<li>Ticket #$idTicket (Pago #$idPago):<br>";
        echo "&nbsp;&nbsp;QR actual: <code>" . htmlspecialchars($qrActual) . "</code><br>";
        
        try {
            // Generar nuevo QR con formato correcto
            $nombreArchivo = 'ticket_' . $idPago;
            $rutaQR = QRHelper::generarQR('PAGO:' . $idPago, 'ticket', $nombreArchivo);
            
            // Actualizar en BD
            $updateStmt = $pdo->prepare("UPDATE Ticket_Pago SET qr_code = ? WHERE id = ?");
            $updateStmt->execute([$rutaQR, $idTicket]);
            
            echo "&nbsp;&nbsp;<span style='color:green'>✓ Regenerado:</span> <code>$rutaQR</code><br>";
            echo "&nbsp;&nbsp;Contenido QR: <code>PAGO:$idPago</code><br>";
            
            // Verificar que el archivo existe
            $rutaCompleta = __DIR__ . '/../public/' . $rutaQR;
            if (file_exists($rutaCompleta)) {
                echo "&nbsp;&nbsp;Tamaño: " . filesize($rutaCompleta) . " bytes<br>";
            } else {
                echo "&nbsp;&nbsp;<span style='color:orange'>⚠ Advertencia: Archivo no encontrado</span><br>";
            }
            
            $regenerados++;
        } catch (Exception $e) {
            echo "&nbsp;&nbsp;<span style='color:red'>✗ Error:</span> " . htmlspecialchars($e->getMessage()) . "<br>";
            $errores++;
        }
        
        echo "</li>";
    }
    
    echo "</ol>";
    echo "<hr>";
    echo "<h3>Resumen:</h3>";
    echo "<ul>";
    echo "<li><strong style='color:green'>Regenerados exitosamente:</strong> $regenerados</li>";
    echo "<li><strong style='color:red'>Errores:</strong> $errores</li>";
    echo "</ul>";
    
    // Limpiar archivos antiguos con formato incorrecto
    echo "<hr>";
    echo "<h3>Limpieza de archivos antiguos:</h3>";
    $qrDir = __DIR__ . '/../public/qrcodes/';
    $archivos = glob($qrDir . 'ticket_*_png.png');
    
    if (count($archivos) > 0) {
        echo "<p>Archivos con formato incorrecto encontrados: <strong>" . count($archivos) . "</strong></p>";
        echo "<ul>";
        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            if (unlink($archivo)) {
                echo "<li style='color:green'>✓ Eliminado: <code>$nombre</code></li>";
            } else {
                echo "<li style='color:red'>✗ No se pudo eliminar: <code>$nombre</code></li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>✓ No se encontraron archivos con formato incorrecto.</p>";
    }
    
    echo "<hr>";
    echo "<p style='color:green; font-weight:bold'>✓ Proceso completado!</p>";
    echo "<p><a href='../index.php?url=ticket'>Ir a tickets</a> | <a href='javascript:location.reload()'>Recargar</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error general:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    li { margin: 10px 0; line-height: 1.6; }
</style>
