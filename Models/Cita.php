<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Cita extends BaseModel {

    public function crear(array $data): int {
        $sql = "INSERT INTO Cita (id_paciente,id_psicologo,fecha_hora,estado_cita,motivo_consulta,qr_code,estado)
                VALUES (?,?,?,'pendiente',?,?,'activo')";
        $st = $this->db->prepare($sql);
        $st->execute([
            $data['id_paciente'],
            $data['id_psicologo'],
            $data['fecha_hora'],
            $data['motivo_consulta'],
            $data['qr_code']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listarPaciente(int $idPaciente): array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE id_paciente=? AND estado='activo' ORDER BY fecha_hora DESC");
        $st->execute([$idPaciente]);
        return $st->fetchAll();
    }

    public function listarPsicologoPendientes(int $idPsico): array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE id_psicologo=? AND estado_cita='pendiente' AND estado='activo' ORDER BY fecha_hora ASC");
        $st->execute([$idPsico]);
        return $st->fetchAll();
    }

    public function obtener(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE id=?");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function obtenerPorQr(string $token): ?array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE qr_code=? LIMIT 1");
        $st->execute([$token]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function marcarRealizada(int $id): bool {
        $st = $this->db->prepare("UPDATE Cita SET estado_cita='realizada' WHERE id=?");
        return $st->execute([$id]);
    }
}
