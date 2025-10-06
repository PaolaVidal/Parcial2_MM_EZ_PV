<?php
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Solicitud.php';

class Solicitud {
    private PDO $db;
    public function __construct() {
        if(class_exists('Database')){
            if(method_exists('Database','get'))        $this->db = Database::get();
            elseif(method_exists('Database','getInstance')) $this->db = Database::getInstance();
        }
        if(empty($this->db)){
            // Ajusta DSN si tu proyecto usa otras constantes
            $this->db = new PDO('mysql:host=localhost;dbname=psicologia;charset=utf8','root','');
            $this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }
    }

    // Obtener solicitudes pendientes
    public function pendientes(): array {
        $sql = "SELECT id,id_paciente,dui,campo,valor_nuevo,fecha,estado 
                FROM solicitudes
                WHERE estado='pendiente'
                ORDER BY fecha DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Marcar solicitud con nuevo estado (aprobada / rechazada)
    public function marcar(int $id,string $estado): bool {
        $st = $this->db->prepare("UPDATE solicitudes SET estado=:e, fecha_respuesta=NOW() WHERE id=:id");
        return $st->execute([':e'=>$estado, ':id'=>$id]);
    }

    // (Opcional) crear solicitud (para pruebas)
    public function crear(int $idPaciente,string $dui,string $campo,string $valor): bool {
        $st = $this->db->prepare("INSERT INTO solicitudes(id_paciente,dui,campo,valor_nuevo,fecha,estado)
                                  VALUES(:p,:d,:c,:v,NOW(),'pendiente')");
        return $st->execute([':p'=>$idPaciente,':d'=>$dui,':c'=>$campo,':v'=>$valor]);
    }
}
