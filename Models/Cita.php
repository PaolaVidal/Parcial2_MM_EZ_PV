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

    public function cancelar(int $id, string $motivo=''): bool {
        $st = $this->db->prepare("UPDATE Cita SET estado_cita='cancelada', motivo_consulta=CONCAT(motivo_consulta, '\n[CANCELADA] ', ?) WHERE id=?");
        return $st->execute([$motivo,$id]);
    }

    public function reprogramar(int $id, string $nuevaFecha): bool {
        $st = $this->db->prepare("UPDATE Cita SET fecha_hora=?, estado_cita='pendiente' WHERE id=? AND estado_cita<>'realizada'");
        return $st->execute([$nuevaFecha,$id]);
    }

    public function reasignarPsicologo(int $id, int $nuevoPsico): bool {
        $st = $this->db->prepare("UPDATE Cita SET id_psicologo=? WHERE id=?");
        return $st->execute([$nuevoPsico,$id]);
    }

    public function estadisticasEstado(): array {
        $sql = "SELECT estado_cita estado, COUNT(*) total FROM Cita GROUP BY estado_cita";
        return $this->db->query($sql)->fetchAll();
    }

    public function citasPorRango(string $inicio, string $fin): array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE fecha_hora BETWEEN ? AND ? ORDER BY fecha_hora");
        $st->execute([$inicio,$fin]);
        return $st->fetchAll();
    }
}
