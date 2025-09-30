<?php

class TicketPago {
    private $id;
    private $id_pago;
    private $codigo;
    private $fecha_emision;
    private $numero_ticket;
    private $estado;

    public function __construct($id = null, $id_pago = null, $codigo = '', $fecha_emision = null, $numero_ticket = '', $estado = '') {
        $this->id = $id;
        $this->id_pago = $id_pago;
        $this->codigo = $codigo;
        $this->fecha_emision = $fecha_emision;
        $this->numero_ticket = $numero_ticket;
        $this->estado = $estado;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdPago() { return $this->id_pago; }
    public function setIdPago($id_pago) { $this->id_pago = $id_pago; }

    public function getCodigo() { return $this->codigo; }
    public function setCodigo($codigo) { $this->codigo = $codigo; }

    public function getFechaEmision() { return $this->fecha_emision; }
    public function setFechaEmision($fecha_emision) { $this->fecha_emision = $fecha_emision; }

    public function getNumeroTicket() { return $this->numero_ticket; }
    public function setNumeroTicket($numero_ticket) { $this->numero_ticket = $numero_ticket; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }
}

?>
