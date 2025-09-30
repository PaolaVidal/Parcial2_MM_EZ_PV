<?php
require_once 'config/conexion.php';
require_once 'clases/Evaluacion.php';

class EvaluacionModel {
    private $cn;

    public function __construct() {
        $this->cn = new Conexion();
    }

    public function listar() {
        $sql = "SELECT * FROM evaluacion";
        $results = $this->cn->consulta($sql);

        $evaluaciones = [];
        foreach ($results as $row) {
            $evaluaciones[] = new Evaluacion($row['id'], $row['id_cita'], $row['estado_emocional'], $row['comentarios'], $row['estado']);
        }
        return $evaluaciones;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM evaluacion WHERE id = ?";
        $results = $this->cn->consulta($sql, [$id]);
        if (!empty($results)) {
            $row = $results[0];
            return new Evaluacion($row['id'], $row['id_cita'], $row['estado_emocional'], $row['comentarios'], $row['estado']);
        }
        return null;
    }

    public function crear($id_cita, $estado_emocional, $comentarios, $estado) {
        $evaluacion = new Evaluacion(null, $id_cita, $estado_emocional, $comentarios, $estado);
        return $this->insert($evaluacion);
    }

    public function actualizar($id, $id_cita, $estado_emocional, $comentarios, $estado) {
        $evaluacion = new Evaluacion($id, $id_cita, $estado_emocional, $comentarios, $estado);
        return $this->update($evaluacion);
    }

    public function eliminar($id) {
        $evaluacion = new Evaluacion($id);
        return $this->delete($evaluacion);
    }

    private function insert($evaluacionObj) {
        $sql = "INSERT INTO evaluacion (id_cita, estado_emocional, comentarios, estado) VALUES (?, ?, ?, ?)";
        return $this->cn->ejecutar($sql, [$evaluacionObj->getIdCita(), $evaluacionObj->getEstadoEmocional(), $evaluacionObj->getComentarios(), $evaluacionObj->getEstado()]);
    }

    private function update($evaluacionObj) {
        $sql = "UPDATE evaluacion SET id_cita = ?, estado_emocional = ?, comentarios = ?, estado = ? WHERE id = ?";
        return $this->cn->ejecutar($sql, [$evaluacionObj->getIdCita(), $evaluacionObj->getEstadoEmocional(), $evaluacionObj->getComentarios(), $evaluacionObj->getEstado(), $evaluacionObj->getId()]);
    }

    private function delete($evaluacionObj) {
        $sql = "DELETE FROM evaluacion WHERE id = ?";
        return $this->cn->ejecutar($sql, [$evaluacionObj->getId()]);
    }
}
?>
