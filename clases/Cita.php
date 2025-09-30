<?php

class Cita {
    private $id;
    private $id_paciente;
    private $id_psicologo;
    private $fecha_hora;
    private $estado_cita;
    private $motivo_consulta;
    private $estado;

    public function __construct($id = null, $id_paciente = null, $id_psicologo = null, $fecha_hora = null, $estado_cita = '', $motivo_consulta = '', $estado = 'activo') {
        $this->id = $id;
        $this->id_paciente = $id_paciente;
        $this->id_psicologo = $id_psicologo;
        $this->fecha_hora = $fecha_hora;
        $this->estado_cita = $estado_cita;
        $this->motivo_consulta = $motivo_consulta;
        $this->estado = $estado;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdPaciente() { return $this->id_paciente; }
    public function setIdPaciente($id_paciente) { $this->id_paciente = $id_paciente; }

    public function getIdPsicologo() { return $this->id_psicologo; }
    public function setIdPsicologo($id_psicologo) { $this->id_psicologo = $id_psicologo; }

    public function getFechaHora() { return $this->fecha_hora; }
    public function setFechaHora($fecha_hora) { $this->fecha_hora = $fecha_hora; }

    public function getEstadoCita() { return $this->estado_cita; }
    public function setEstadoCita($estado_cita) { $this->estado_cita = $estado_cita; }

    public function getMotivoConsulta() { return $this->motivo_consulta; }
    public function setMotivoConsulta($motivo_consulta) { $this->motivo_consulta = $motivo_consulta; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }
}

?>
