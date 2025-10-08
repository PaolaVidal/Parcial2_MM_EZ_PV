<?php
require_once __DIR__ . '/BaseModel.php';

class SolicitudCambio extends BaseModel {

    public function crear(int $idPaciente, string $campo, string $valor): int {
        $st = $this->db->prepare(
            "INSERT INTO SolicitudCambio (id_paciente,campo,valor_nuevo) VALUES (?,?,?)"
        );
        $st->execute([$idPaciente,$campo,$valor]);
        return (int)$this->db->lastInsertId();
    }

    public function listarPendientes(): array {
        $sql = "SELECT sc.*, p.dui
                FROM SolicitudCambio sc
                JOIN Paciente p ON p.id = sc.id_paciente
                WHERE sc.estado='pendiente'
                ORDER BY sc.fecha DESC";
        return $this->db->query($sql)->fetchAll() ?: [];
    }

    public function obtener(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM SolicitudCambio WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function actualizarEstado(int $id, string $estado): bool {
        $estadoValido = in_array($estado, ['pendiente','aprobado','rechazado'], true);
        if(!$estadoValido) return false;
        $st = $this->db->prepare("UPDATE SolicitudCambio SET estado=? WHERE id=?");
        return $st->execute([$estado,$id]);
    }
    
    public function listarPorPaciente(int $idPaciente): array {
        $sql = "SELECT * FROM SolicitudCambio 
                WHERE id_paciente = ? 
                ORDER BY fecha DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPaciente]);
        return $st->fetchAll() ?: [];
    }
}
