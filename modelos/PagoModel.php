<?php
require_once 'config/conexion.php';
require_once 'clases/Pago.php';

class PagoModel {
    private $cn;

    public function __construct() {
        $this->cn = new Conexion();
    }

    public function listar() {
        $sql = "SELECT * FROM pago";
        $results = $this->cn->consulta($sql);

        $pagos = [];
        foreach ($results as $row) {
            $pagos[] = new Pago($row['id'], $row['id_cita'], $row['monto'], $row['metodo_pago'], $row['fecha'], $row['estado_pago'], $row['estado']);
        }
        return $pagos;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM pago WHERE id = ?";
        $results = $this->cn->consulta($sql, [$id]);
        if (!empty($results)) {
            $row = $results[0];
            return new Pago($row['id'], $row['id_cita'], $row['monto'], $row['metodo_pago'], $row['fecha'], $row['estado_pago'], $row['estado']);
        }
        return null;
    }

    public function crear($id_cita, $monto, $metodo_pago, $fecha, $estado_pago, $estado) {
        $pago = new Pago(null, $id_cita, $monto, $metodo_pago, $fecha, $estado_pago, $estado);
        return $this->insert($pago);
    }

    public function actualizar($id, $id_cita, $monto, $metodo_pago, $fecha, $estado_pago, $estado) {
        $pago = new Pago($id, $id_cita, $monto, $metodo_pago, $fecha, $estado_pago, $estado);
        return $this->update($pago);
    }

    public function eliminar($id) {
        $pago = new Pago($id);
        return $this->delete($pago);
    }

    private function insert($pagoObj) {
        $sql = "INSERT INTO pago (id_cita, monto, metodo_pago, fecha, estado_pago, estado) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->cn->ejecutar($sql, [$pagoObj->getIdCita(), $pagoObj->getMonto(), $pagoObj->getMetodoPago(), $pagoObj->getFecha(), $pagoObj->getEstadoPago(), $pagoObj->getEstado()]);
    }

    private function update($pagoObj) {
        $sql = "UPDATE pago SET id_cita = ?, monto = ?, metodo_pago = ?, fecha = ?, estado_pago = ?, estado = ? WHERE id = ?";
        return $this->cn->ejecutar($sql, [$pagoObj->getIdCita(), $pagoObj->getMonto(), $pagoObj->getMetodoPago(), $pagoObj->getFecha(), $pagoObj->getEstadoPago(), $pagoObj->getEstado(), $pagoObj->getId()]);
    }

    private function delete($pagoObj) {
        $sql = "DELETE FROM pago WHERE id = ?";
        return $this->cn->ejecutar($sql, [$pagoObj->getId()]);
    }
}
?>
