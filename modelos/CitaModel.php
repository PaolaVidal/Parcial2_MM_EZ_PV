<?php
require_once 'config/conexion.php';
require_once 'clases/Cita.php';

class CitaModel {
    private $cn;

    public function __construct() {
        $this->cn = new Conexion();
    }

    public function listar() {
        $sql = "SELECT * FROM cita";
        $results = $this->cn->consulta($sql);

        $citas = [];
        foreach ($results as $row) {
            $citas[] = new Cita($row['id'], $row['id_paciente'], $row['id_psicologo'], $row['fecha_hora'], $row['estado_cita'], $row['motivo_consulta'], $row['estado']);
        }
        return $citas;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM cita WHERE id = ?";
        $results = $this->cn->consulta($sql, [$id]);
        if (!empty($results)) {
            $row = $results[0];
            return new Cita($row['id'], $row['id_paciente'], $row['id_psicologo'], $row['fecha_hora'], $row['estado_cita'], $row['motivo_consulta'], $row['estado']);
        }
        return null;
    }

    public function crear($id_paciente, $id_psicologo, $fecha_hora, $estado_cita, $motivo_consulta, $estado) {
        $cita = new Cita(null, $id_paciente, $id_psicologo, $fecha_hora, $estado_cita, $motivo_consulta, $estado);
        return $this->insert($cita);
    }

    public function actualizar($id, $id_paciente, $id_psicologo, $fecha_hora, $estado_cita, $motivo_consulta, $estado) {
        $cita = new Cita($id, $id_paciente, $id_psicologo, $fecha_hora, $estado_cita, $motivo_consulta, $estado);
        return $this->update($cita);
    }

    public function eliminar($id) {
        $cita = new Cita($id);
        return $this->delete($cita);
    }

    private function insert($citaObj) {
        $sql = "INSERT INTO cita (id_paciente, id_psicologo, fecha_hora, estado_cita, motivo_consulta, estado) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->cn->ejecutar($sql, [$citaObj->getIdPaciente(), $citaObj->getIdPsicologo(), $citaObj->getFechaHora(), $citaObj->getEstadoCita(), $citaObj->getMotivoConsulta(), $citaObj->getEstado()]);
    }

    private function update($citaObj) {
        $sql = "UPDATE cita SET id_paciente = ?, id_psicologo = ?, fecha_hora = ?, estado_cita = ?, motivo_consulta = ?, estado = ? WHERE id = ?";
        return $this->cn->ejecutar($sql, [$citaObj->getIdPaciente(), $citaObj->getIdPsicologo(), $citaObj->getFechaHora(), $citaObj->getEstadoCita(), $citaObj->getMotivoConsulta(), $citaObj->getEstado(), $citaObj->getId()]);
    }

    private function delete($citaObj) {
        $sql = "DELETE FROM cita WHERE id = ?";
        return $this->cn->ejecutar($sql, [$citaObj->getId()]);
    }
}
?>
