<?php
require_once 'config/conexion.php';
require_once 'clases/Psicologo.php';

class PsicologoModel {
    private $cn;

    public function __construct() {
        $this->cn = new Conexion();
    }

    public function listar() {
        $sql = "SELECT * FROM psicologo";
        $results = $this->cn->consulta($sql);

        $psicologos = [];
        foreach ($results as $row) {
            $psicologos[] = new Psicologo($row['id'], $row['id_usuario'], $row['especialidad'], $row['experiencia'], $row['horario'], $row['estado']);
        }
        return $psicologos;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM psicologo WHERE id = ?";
        $results = $this->cn->consulta($sql, [$id]);
        if (!empty($results)) {
            $row = $results[0];
            return new Psicologo($row['id'], $row['id_usuario'], $row['especialidad'], $row['experiencia'], $row['horario'], $row['estado']);
        }
        return null;
    }

    public function crear($id_usuario, $especialidad, $experiencia, $horario, $estado) {
        $psicologo = new Psicologo(null, $id_usuario, $especialidad, $experiencia, $horario, $estado);
        return $this->insert($psicologo);
    }

    public function actualizar($id, $id_usuario, $especialidad, $experiencia, $horario, $estado) {
        $psicologo = new Psicologo($id, $id_usuario, $especialidad, $experiencia, $horario, $estado);
        return $this->update($psicologo);
    }

    public function eliminar($id) {
        $psicologo = new Psicologo($id);
        return $this->delete($psicologo);
    }

    private function insert($psicologoObj) {
        $sql = "INSERT INTO psicologo (id_usuario, especialidad, experiencia, horario, estado) VALUES (?, ?, ?, ?, ?)";
        return $this->cn->ejecutar($sql, [$psicologoObj->getIdUsuario(), $psicologoObj->getEspecialidad(), $psicologoObj->getExperiencia(), $psicologoObj->getHorario(), $psicologoObj->getEstado()]);
    }

    private function update($psicologoObj) {
        $sql = "UPDATE psicologo SET id_usuario = ?, especialidad = ?, experiencia = ?, horario = ?, estado = ? WHERE id = ?";
        return $this->cn->ejecutar($sql, [$psicologoObj->getIdUsuario(), $psicologoObj->getEspecialidad(), $psicologoObj->getExperiencia(), $psicologoObj->getHorario(), $psicologoObj->getEstado(), $psicologoObj->getId()]);
    }

    private function delete($psicologoObj) {
        $sql = "DELETE FROM psicologo WHERE id = ?";
        return $this->cn->ejecutar($sql, [$psicologoObj->getId()]);
    }
}
?>
