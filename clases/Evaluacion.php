<?php

class Evaluacion {
    private $id;
    private $id_cita;
    private $estado_emocional;
    private $comentarios;
    private $estado;

    public function __construct($id = null, $id_cita = null, $estado_emocional = 0, $comentarios = '', $estado = '') {
        $this->id = $id;
        $this->id_cita = $id_cita;
        $this->estado_emocional = $estado_emocional;
        $this->comentarios = $comentarios;
        $this->estado = $estado;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdCita() { return $this->id_cita; }
    public function setIdCita($id_cita) { $this->id_cita = $id_cita; }

    public function getEstadoEmocional() { return $this->estado_emocional; }
    public function setEstadoEmocional($estado_emocional) { $this->estado_emocional = $estado_emocional; }

    public function getComentarios() { return $this->comentarios; }
    public function setComentarios($comentarios) { $this->comentarios = $comentarios; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }
}

?>
