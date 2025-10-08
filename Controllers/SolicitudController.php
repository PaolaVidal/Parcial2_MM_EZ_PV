<?php
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../models/Paciente.php';

class SolicitudController {
    
    private function render(string $vista, array $data = []): void {
        $file = __DIR__ . '/../Views/admin/' . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista no encontrada: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }
    
    private function requireAdmin(): void {
        if(!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
            http_response_code(403);
            echo '<div class="alert alert-danger">Acceso denegado</div>';
            exit;
        }
    }
    
    public function procesar(int $id = 0): void {
        $this->requireAdmin();
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=Admin&action=solicitudes');
            exit;
        }
        
        $id = $id ?: (int)($_POST['id'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $idPaciente = (int)($_POST['id_paciente'] ?? 0);
        $campo = $_POST['campo_original'] ?? '';
        $valorNuevo = $_POST['valor_nuevo'] ?? '';
        
        if(!$id || !in_array($accion, ['aprobar','rechazar'])) {
            $_SESSION['msg_solicitud'] = 'Acción inválida';
            header('Location: index.php?controller=Admin&action=solicitudes');
            exit;
        }
        
        $solicitudModel = new SolicitudCambio();
        $pacienteModel = new Paciente();
        
        // Cambiar estado de la solicitud
        $nuevoEstado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
        $solicitudModel->actualizarEstado($id, $nuevoEstado);
        
        // Si se aprueba, actualizar el paciente
        if($accion === 'aprobar' && $idPaciente && $campo && $valorNuevo) {
            $pacienteModel->actualizarCampo($idPaciente, $campo, $valorNuevo);
            $_SESSION['msg_solicitud'] = "Solicitud #$id aprobada y datos actualizados";
        } elseif($accion === 'rechazar') {
            $_SESSION['msg_solicitud'] = "Solicitud #$id rechazada";
        }
        
        header('Location: index.php?controller=Admin&action=solicitudes');
        exit;
    }
}
