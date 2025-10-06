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

    public function listarTodos(): array {
        $sql = "SELECT ps.*, u.nombre, u.email, u.estado AS estado_usuario
                FROM Psicologo ps
                JOIN Usuario u ON u.id = ps.id_usuario
                ORDER BY u.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function crear(int $idUsuario, array $data): int {
        $st = $this->db->prepare("INSERT INTO Psicologo (id_usuario,especialidad,experiencia,horario,estado) VALUES (?,?,?,?, 'activo')");
        $st->execute([
            $idUsuario,
            $data['especialidad'] ?? null,
            $data['experiencia'] ?? null,
            $data['horario'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool {
        $campos=[];$vals=[];
        foreach(['especialidad','experiencia','horario','estado'] as $c){
            if(isset($data[$c])){ $campos[]="$c=?"; $vals[]=$data[$c]; }
        }
        if(empty($campos)) return false;
        $vals[]=$id;
        $sql = 'UPDATE Psicologo SET '.implode(',', $campos).' WHERE id=? LIMIT 1';
        $st = $this->db->prepare($sql);
        return $st->execute($vals);
    }

    public function masSolicitados(int $limit=5): array {
        $st = $this->db->prepare("SELECT c.id_psicologo, COUNT(*) total
                                   FROM Cita c
                                   GROUP BY c.id_psicologo
                                   ORDER BY total DESC
                                   LIMIT ?");
        $st->bindValue(1,$limit,PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function sesionesPorPsicologo(): array {
        $sql = "SELECT p.id_psicologo, COUNT(*) total
                FROM Cita p
                WHERE p.estado_cita='realizada'
                GROUP BY p.id_psicologo";
        return $this->db->query($sql)->fetchAll();
    }
}
