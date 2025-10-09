<?php
/** Modelo PagoExtra */
require_once 'BaseModel.php';

class PagoExtra extends BaseModel {
    public function agregar($idPago, $descripcion, $monto){
        try {
            $stmt = $this->db->prepare("INSERT INTO PagoExtras (id_pago, descripcion, monto) VALUES (?,?,?)");
            return $stmt->execute([$idPago, $descripcion, $monto]);
        } catch (PDOException $e) {
            error_log('Warning PagoExtra->agregar: ' . $e->getMessage());
            return false;
        }
    }

    public function listarPorPago($idPago){
        try {
            $stmt = $this->db->prepare("SELECT * FROM PagoExtras WHERE id_pago=?");
            $stmt->execute([$idPago]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Warning PagoExtra->listarPorPago: ' . $e->getMessage());
            return [];
        }
    }
}
