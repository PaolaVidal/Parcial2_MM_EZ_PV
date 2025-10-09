<?php
/** Modelo Pago */
require_once __DIR__ . '/BaseModel.php';

class Pago extends BaseModel
{

    public function crearParaCita(int $idCita, float $montoBase = 50.00): int
    {
        // Evitar duplicados
        $ex = $this->obtenerPorCita($idCita);
        if ($ex)
            return (int) $ex['id'];
        $st = $this->db->prepare("INSERT INTO Pago (id_cita,monto_base,monto_total,estado_pago,estado) VALUES (?,?,?,?,?)");
        // asegurar valores por defecto
        $st->execute([$idCita, $montoBase, $montoBase, 'pendiente', 'activo']);
        return (int) $this->db->lastInsertId();
    }

    /** Forzar estado pendiente en un pago (útil para asegurar comportamiento) */
    public function marcarPendiente(int $id): bool
    {
        $st = $this->db->prepare("UPDATE Pago SET estado_pago='pendiente' WHERE id=?");
        return $st->execute([$id]);
    }

    public function obtener(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM Pago WHERE id=?");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function obtenerPorCita(int $idCita): ?array
    {
        $st = $this->db->prepare("SELECT * FROM Pago WHERE id_cita=? LIMIT 1");
        $st->execute([$idCita]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function marcarPagado(int $id): bool
    {
        $st = $this->db->prepare("UPDATE Pago SET estado_pago='pagado' WHERE id=?");
        return $st->execute([$id]);
    }

    public function listarPaciente(int $idPaciente): array
    {
        $sql = "SELECT p.*, 
                       c.fecha_hora as cita_fecha,
                       c.motivo_consulta as cita_motivo,
                       t.id as ticket_id,
                       t.numero_ticket,
                       t.qr_code as ticket_qr
                FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                LEFT JOIN Ticket_Pago t ON t.id_pago = p.id
                WHERE c.id_paciente=? AND p.estado='activo'
                ORDER BY p.fecha DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPaciente]);
        return $st->fetchAll();
    }

    public function listarPsicologo(int $idPsico): array
    {
        $sql = "SELECT p.* FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                WHERE c.id_psicologo=? AND p.estado='activo'
                ORDER BY p.fecha DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPsico]);
        return $st->fetchAll();
    }

    public function listarTodos(): array
    {
        return $this->db->query("SELECT * FROM Pago ORDER BY fecha DESC")->fetchAll();
    }

    public function listarPendientes(): array
    {
        return $this->db->query("SELECT * FROM Pago WHERE estado_pago='pendiente' ORDER BY fecha DESC")->fetchAll();
    }

    public function recalcularTotal(int $idPago): bool
    {
        // suma extras
        $st = $this->db->prepare("SELECT monto_base FROM Pago WHERE id=?");
        $st->execute([$idPago]);
        $base = (float) $st->fetchColumn();
        try {
            $st2 = $this->db->prepare("SELECT COALESCE(SUM(monto),0) FROM PagoExtras WHERE id_pago=?");
            $st2->execute([$idPago]);
            $extras = (float) $st2->fetchColumn();
        } catch (PDOException $e) {
            // Tabla PagoExtras ausente o error SQL: asumir 0 extras y loggear para diagnóstico.
            error_log('Warning recalcularTotal: no se pudo obtener extras (asumiendo 0): ' . $e->getMessage());
            $extras = 0.0;
        }
        $total = $base + $extras;
        $up = $this->db->prepare("UPDATE Pago SET monto_total=? WHERE id=?");
        return $up->execute([$total, $idPago]);
    }

    public function ingresosPorMes(int $year): array
    {
        $st = $this->db->prepare("SELECT DATE_FORMAT(fecha,'%m') mes, SUM(monto_total) total
                                   FROM Pago WHERE estado_pago='pagado' AND YEAR(fecha)=?
                                   GROUP BY mes ORDER BY mes");
        $st->execute([$year]);
        return $st->fetchAll();
    }

    public function ingresosPorPsicologo(): array
    {
        $sql = "SELECT c.id_psicologo, SUM(p.monto_total) total
                FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                WHERE p.estado_pago='pagado'
                GROUP BY c.id_psicologo";
        return $this->db->query($sql)->fetchAll();
    }

    /** Ingresos agrupados por especialidad del psicólogo */
    public function ingresosPorEspecialidad(): array
    {
        $sql = "SELECT e.nombre especialidad, SUM(p.monto_total) total
                FROM Pago p
                JOIN Cita c ON c.id = p.id_cita
                JOIN Psicologo ps ON ps.id = c.id_psicologo
                LEFT JOIN Especialidad e ON e.id = ps.id_especialidad
                WHERE p.estado_pago='pagado'
                GROUP BY e.nombre
                ORDER BY total DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Crea pago (si no existe) y lo marca como pagado inmediatamente */
    public function registrarPagoCita(int $idCita, float $montoBase = 50.0): int
    {
        $pago = $this->obtenerPorCita($idCita);
        if (!$pago) {
            $id = $this->crearParaCita($idCita, $montoBase);
            $this->marcarPagado($id);
            return $id;
        }
        if ($pago['estado_pago'] !== 'pagado') {
            $this->marcarPagado((int) $pago['id']);
        }
        return (int) $pago['id'];
    }
}
