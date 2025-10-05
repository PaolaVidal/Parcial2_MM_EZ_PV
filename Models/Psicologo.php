<?php
/** Modelo Psicologo */
require_once __DIR__ . '/BaseModel.php';

class Psicologo extends BaseModel {

    public function obtenerPorUsuario(int $idUsuario): ?array {
        $st = $this->db->prepare("SELECT * FROM Psicologo WHERE id_usuario=? LIMIT 1");
        $st->execute([$idUsuario]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function listarActivos(): array {
        // Une con Usuario para mostrar nombre
        $sql = "SELECT ps.id, u.nombre
                FROM Psicologo ps
                JOIN Usuario u ON u.id = ps.id_usuario
                WHERE ps.estado='activo' AND u.estado='activo'
                ORDER BY u.nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
