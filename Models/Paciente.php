<?php
/** Modelo Paciente */
require_once 'BaseModel.php';

class Paciente extends BaseModel {
    /**
     * Crea un paciente asociado a un usuario (FK id_usuario).
     * Se asume que la tabla Paciente ahora tiene columna id_usuario INT NOT NULL.
     */
    public function crear($data){
        $sql = "INSERT INTO Paciente (id_usuario, fecha_nacimiento, genero, direccion, telefono, historial_clinico, estado) VALUES (?,?,?,?,?, ?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['id_usuario'], $data['fecha_nacimiento'], $data['genero'], $data['direccion'], $data['telefono'], $data['historial_clinico'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function listar(){
        return $this->db->query("SELECT p.*, u.nombre, u.email FROM Paciente p JOIN Usuario u ON u.id = p.id_usuario WHERE p.estado='activo'")->fetchAll();
    }

    public function obtenerPorUsuario($idUsuario){
        $stmt = $this->db->prepare("SELECT * FROM Paciente WHERE id_usuario=? LIMIT 1");
        $stmt->execute([$idUsuario]);
        return $stmt->fetch();
    }
}
