<?php
/** Modelo Cita */
require_once 'BaseModel.php';

class Cita extends BaseModel {
    /** Crea cita y retorna ID */
    public function crear($data){
        $sql = "INSERT INTO Cita (id_paciente, id_psicologo, fecha_hora, motivo_consulta, estado_cita, qr_code, estado) VALUES (?,?,?,?, 'pendiente', ?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['id_paciente'], $data['id_psicologo'], $data['fecha_hora'], $data['motivo_consulta'], $data['qr_code'] // ruta QR generada
        ]);
        return $this->db->lastInsertId();
    }

    public function listar(){
        $sql = "SELECT c.*, u.nombre AS paciente_nombre, u.email AS paciente_email, ps.id AS psicologo_id
                FROM Cita c
                JOIN Paciente p ON p.id = c.id_paciente
                JOIN Usuario u ON u.id = p.id_usuario
                JOIN Psicologo ps ON ps.id = c.id_psicologo
                WHERE c.estado='activo'";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtener($id){
        $stmt = $this->db->prepare("SELECT * FROM Cita WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
