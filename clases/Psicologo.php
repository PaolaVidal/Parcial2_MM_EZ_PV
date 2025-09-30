<?php

class Psicologo {
    private $id;
    private $id_usuario;
    private $especialidad;
    private $experiencia;
    private $horario;
    private $estado;

    public function __construct($id = null, $id_usuario = null, $especialidad = '', $experiencia = '', $horario = '', $estado = 'activo') {
        $this->id = $id;
        $this->id_usuario = $id_usuario;
        $this->especialidad = $especialidad;
        $this->experiencia = $experiencia;
        $this->horario = $horario;
        $this->estado = $estado;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdUsuario() { return $this->id_usuario; }
    public function setIdUsuario($id_usuario) { $this->id_usuario = $id_usuario; }

    public function getEspecialidad() { return $this->especialidad; }
    public function setEspecialidad($especialidad) { $this->especialidad = $especialidad; }

    public function getExperiencia() { return $this->experiencia; }
    public function setExperiencia($experiencia) { $this->experiencia = $experiencia; }

    public function getHorario() { return $this->horario; }
    public function setHorario($horario) { $this->horario = $horario; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }
}

?>
