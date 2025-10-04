<?php
/** Modelo TicketPago */
require_once 'BaseModel.php';

class TicketPago extends BaseModel {
    public function crear($data){
        $sql = "INSERT INTO Ticket_Pago (id_pago, codigo, numero_ticket, qr_code, estado) VALUES (?,?,?,?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['id_pago'], $data['codigo'], $data['numero_ticket'], $data['qr_code']
        ]);
        return $this->db->lastInsertId();
    }

    public function obtenerPorPago($idPago){
        $stmt = $this->db->prepare("SELECT * FROM Ticket_Pago WHERE id_pago=? LIMIT 1");
        $stmt->execute([$idPago]);
        return $stmt->fetch();
    }

    public function obtener($id){
        $stmt = $this->db->prepare("SELECT * FROM Ticket_Pago WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
