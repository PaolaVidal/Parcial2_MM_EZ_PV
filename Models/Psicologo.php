<?php
/** Modelo Psicologo */
require_once 'BaseModel.php';

class Psicologo extends BaseModel {
    public function crear($data){
        $sql = "INSERT INTO Psicologo (id_usuario, especialidad, experiencia, horario, estado) VALUES (?,?,?,?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['id_usuario'], $data['especialidad'], $data['experiencia'], $data['horario']
        ]);
        return $this->db->lastInsertId();
    }

    public function listar(){
        return $this->db->query("SELECT p.*, u.nombre as nombre_usuario FROM Psicologo p JOIN Usuario u ON u.id = p.id_usuario WHERE p.estado='activo'")->fetchAll();
    }
}
