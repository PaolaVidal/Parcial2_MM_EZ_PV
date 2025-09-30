<?php

class Paciente {
    private $id;
    private $fecha_nacimiento;
    private $genero;
    private $correo;
    private $direccion;
    private $telefono;
    private $historial_clinico;
    private $estado;

    public function __construct($id = null, $fecha_nacimiento = null, $genero = '', $correo = '', $direccion = '', $telefono = '', $historial_clinico = '', $estado = 'activo') {
        $this->id = $id;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->genero = $genero;
        $this->correo = $correo;
        $this->direccion = $direccion;
        $this->telefono = $telefono;
        $this->historial_clinico = $historial_clinico;
        $this->estado = $estado;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getFechaNacimiento() { return $this->fecha_nacimiento; }
    public function setFechaNacimiento($fecha_nacimiento) { $this->fecha_nacimiento = $fecha_nacimiento; }

    public function getGenero() { return $this->genero; }
    public function setGenero($genero) { $this->genero = $genero; }

    public function getCorreo() { return $this->correo; }
    public function setCorreo($correo) { $this->correo = $correo; }

    public function getDireccion() { return $this->direccion; }
    public function setDireccion($direccion) { $this->direccion = $direccion; }

    public function getTelefono() { return $this->telefono; }
    public function setTelefono($telefono) { $this->telefono = $telefono; }

    public function getHistorialClinico() { return $this->historial_clinico; }
    public function setHistorialClinico($historial_clinico) { $this->historial_clinico = $historial_clinico; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }
}

?>
