<?php
/** Modelo TicketPago */
require_once __DIR__ . '/BaseModel.php';

class TicketPago extends BaseModel {

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
}
