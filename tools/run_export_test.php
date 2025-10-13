<?php
// Quick runner to call PsicologoController::exportarEstadisticasExcel from CLI for testing
chdir(__DIR__ . '/../');
require_once __DIR__ . '/../Controllers/PsicologoController.php';
// Minimal session mock
if (session_status() === PHP_SESSION_NONE) session_start();
// Mock a logged in psicologo user and psicologo_id
$_SESSION['usuario'] = ['id' => 1, 'rol' => 'psicologo'];
// If controller expects psicologo_id in session, ensure it's set via DB lookup may fail; set to 1 to test generation
$_SESSION['psicologo_id'] = 1;
$ctrl = new PsicologoController();
try {
    $ctrl->exportarEstadisticasExcel();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
