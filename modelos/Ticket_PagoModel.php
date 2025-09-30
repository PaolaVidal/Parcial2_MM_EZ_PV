<?php
require_once 'config/conexion.php';
require_once 'clases/TicketPago.php';

class TicketPagoModel {
    private $cn;

    public function __construct() {
        $this->cn = new Conexion();
    }

    public function listar() {
        $sql = "SELECT * FROM ticket_pago";
        $results = $this->cn->consulta($sql);

        $tickets = [];
        foreach ($results as $row) {
            $tickets[] = new TicketPago(
                $row['id'],
                $row['id_pago'],
                $row['codigo_ticket'],
                $row['fecha_emision'],
                $row['estado']
            );
        }
        return $tickets;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM ticket_pago WHERE id = ?";
        $results = $this->cn->consulta($sql, [$id]);
        if (!empty($results)) {
            $row = $results[0];
            return new TicketPago(
                $row['id'],
                $row['id_pago'],
                $row['codigo_ticket'],
                $row['fecha_emision'],
                $row['estado']
            );
        }
        return null;
    }

    public function crear($id_pago, $codigo_ticket, $fecha_emision, $estado) {
        $ticket = new TicketPago(null, $id_pago, $codigo_ticket, $fecha_emision, $estado);
        return $this->insert($ticket);
    }

    public function actualizar($id, $id_pago, $codigo_ticket, $fecha_emision, $estado) {
        $ticket = new TicketPago($id, $id_pago, $codigo_ticket, $fecha_emision, $estado);
        return $this->update($ticket);
    }

    public function eliminar($id) {
        $ticket = new TicketPago($id);
        return $this->delete($ticket);
    }

    private function insert($ticketObj) {
        $sql = "INSERT INTO ticket_pago (id_pago, codigo_ticket, fecha_emision, estado) VALUES (?, ?, ?, ?)";
        return $this->cn->ejecutar($sql, [
            $ticketObj->getIdPago(),
            $ticketObj->getCodigoTicket(),
            $ticketObj->getFechaEmision(),
            $ticketObj->getEstado()
        ]);
    }

    private function update($ticketObj) {
        $sql = "UPDATE ticket_pago SET id_pago = ?, codigo_ticket = ?, fecha_emision = ?, estado = ? WHERE id = ?";
        return $this->cn->ejecutar($sql, [
            $ticketObj->getIdPago(),
            $ticketObj->getCodigoTicket(),
            $ticketObj->getFechaEmision(),
            $ticketObj->getEstado(),
            $ticketObj->getId()
        ]);
    }

    private function delete($ticketObj) {
        $sql = "DELETE FROM ticket_pago WHERE id = ?";
        return $this->cn->ejecutar($sql, [$ticketObj->getId()]);
    }
}
?>
