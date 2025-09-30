<?php

class Pago {
    private $id;
    private $id_cita;
    private $monto;
    private $metodo_pago;
    private $fecha;
    private $estado_pago;
    private $estado;

    public function __construct($id = null, $id_cita = null, $monto = 0.0, $metodo_pago = '', $fecha = null, $estado_pago = '', $estado = '') {
        $this->id = $id;
        $this->id_cita = $id_cita;
        $this->monto = $monto;
        $this->metodo_pago = $metodo_pago;
        $this->fecha = $fecha;
        $this->estado_pago = $estado_pago;
        $this->estado = $estado;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdCita() { return $this->id_cita; }
    public function setIdCita($id_cita) { $this->id_cita = $id_cita; }

    public function getMonto() { return $this->monto; }
    public function setMonto($monto) { $this->monto = $monto; }

    public function getMetodoPago() { return $this->metodo_pago; }
    public function setMetodoPago($metodo_pago) { $this->metodo_pago = $metodo_pago; }

    public function getFecha() { return $this->fecha; }
    public function setFecha($fecha) { $this->fecha = $fecha; }

    public function getEstadoPago() { return $this->estado_pago; }
    public function setEstadoPago($estado_pago) { $this->estado_pago = $estado_pago; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }
}

?>
