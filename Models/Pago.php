<?php
/** Modelo Pago */
require_once __DIR__ . '/BaseModel.php';

class Pago extends BaseModel {

    public function crearParaCita(int $idCita, float $montoBase = 50.00): int {
        // Evitar duplicados
        $ex = $this->obtenerPorCita($idCita);
        if ($ex) return (int)$ex['id'];
        $st = $this->db->prepare("INSERT INTO Pago (id_cita,monto_base,monto_total,estado_pago,estado) VALUES (?,?,?, 'pendiente','activo')");
        $st->execute([$idCita,$montoBase,$montoBase]);
        return (int)$this->db->lastInsertId();
    }

    public function obtener(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM Pago WHERE id=?");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function obtenerPorCita(int $idCita): ?array {
        $st = $this->db->prepare("SELECT * FROM Pago WHERE id_cita=? LIMIT 1");
        $st->execute([$idCita]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function marcarPagado(int $id): bool {
        $st = $this->db->prepare("UPDATE Pago SET estado_pago='pagado' WHERE id=?");
        return $st->execute([$id]);
    }

    public function listarPaciente(int $idPaciente): array {
        $sql = "SELECT p.* FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                WHERE c.id_paciente=? AND p.estado='activo'
                ORDER BY p.fecha DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPaciente]);
        return $st->fetchAll();
    }

    public function listarPsicologo(int $idPsico): array {
        $sql = "SELECT p.* FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                WHERE c.id_psicologo=? AND p.estado='activo'
                ORDER BY p.fecha DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPsico]);
        return $st->fetchAll();
    }

    public function listarTodos(): array {
        return $this->db->query("SELECT * FROM Pago ORDER BY fecha DESC")->fetchAll();
    }

    public function listarPendientes(): array {
        return $this->db->query("SELECT * FROM Pago WHERE estado_pago='pendiente' ORDER BY fecha DESC")->fetchAll();
    }

    public function recalcularTotal(int $idPago): bool {
        // suma extras
        $st = $this->db->prepare("SELECT monto_base FROM Pago WHERE id=?");
        $st->execute([$idPago]);
        $base = (float)$st->fetchColumn();
        $st2 = $this->db->prepare("SELECT COALESCE(SUM(monto),0) FROM PagoExtras WHERE id_pago=?");
        $st2->execute([$idPago]);
        $extras = (float)$st2->fetchColumn();
        $total = $base + $extras;
        $up = $this->db->prepare("UPDATE Pago SET monto_total=? WHERE id=?");
        return $up->execute([$total,$idPago]);
    }

    public function ingresosPorMes(int $year): array {
        $st = $this->db->prepare("SELECT DATE_FORMAT(fecha,'%m') mes, SUM(monto_total) total
                                   FROM Pago WHERE estado_pago='pagado' AND YEAR(fecha)=?
                                   GROUP BY mes ORDER BY mes");
        $st->execute([$year]);
        return $st->fetchAll();
    }

    public function ingresosPorPsicologo(): array {
        $sql = "SELECT c.id_psicologo, SUM(p.monto_total) total
                FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                WHERE p.estado_pago='pagado'
                GROUP BY c.id_psicologo";
        return $this->db->query($sql)->fetchAll();
    }
}
