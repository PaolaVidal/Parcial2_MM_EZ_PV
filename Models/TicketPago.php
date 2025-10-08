<?php
/** Modelo TicketPago */
require_once __DIR__ . '/BaseModel.php';

class TicketPago extends BaseModel {

    /** Obtener ticket por ID */
    public function obtener(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM Ticket_Pago WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function obtenerPorPago(int $idPago): ?array {
        $st = $this->db->prepare("SELECT * FROM Ticket_Pago WHERE id_pago=? LIMIT 1");
        $st->execute([$idPago]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function crear(array $data): int {
        $st = $this->db->prepare(
            "INSERT INTO Ticket_Pago (id_pago,codigo,numero_ticket,qr_code,estado) VALUES (?,?,?,?, 'activo')"
        );
        $st->execute([
            $data['id_pago'],
            $data['codigo'],
            $data['numero_ticket'],
            $data['qr_code']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Listar tickets asociados a un psicólogo (join Pago->Cita->Paciente) */
    public function listarPorPsicologo(int $idPsicologo): array {
        $sql = "SELECT t.*, p.monto_total, p.estado_pago, c.fecha_hora, c.id as id_cita,
                       pac.id as id_paciente, COALESCE(pac.nombre, CONCAT('Paciente #', pac.id)) nombre_paciente
                FROM Ticket_Pago t
                JOIN Pago p ON p.id = t.id_pago
                JOIN Cita c ON c.id = p.id_cita
                LEFT JOIN Paciente pac ON pac.id = c.id_paciente
                WHERE c.id_psicologo = ? AND t.estado='activo'
                ORDER BY t.fecha_emision DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPsicologo]);
        return $st->fetchAll();
    }

    /** Listar TODOS los tickets del sistema (para admin) con datos completos */
    public function listarTodos(): array {
        $sql = "SELECT t.*, p.monto_total, p.estado_pago, c.fecha_hora, c.id as id_cita, c.id_psicologo,
                       pac.id as id_paciente, COALESCE(pac.nombre, CONCAT('Paciente #', pac.id)) nombre_paciente,
                       u.nombre as psicologo_nombre
                FROM Ticket_Pago t
                JOIN Pago p ON p.id = t.id_pago
                JOIN Cita c ON c.id = p.id_cita
                LEFT JOIN Paciente pac ON pac.id = c.id_paciente
                LEFT JOIN Psicologo ps ON ps.id = c.id_psicologo
                LEFT JOIN Usuario u ON u.id = ps.id_usuario
                WHERE t.estado='activo'
                ORDER BY t.fecha_emision DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
