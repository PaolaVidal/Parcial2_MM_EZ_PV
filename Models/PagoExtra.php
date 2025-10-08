<?php
/** Modelo PagoExtra */
require_once 'BaseModel.php';

class PagoExtra extends BaseModel {
    public function agregar($idPago, $descripcion, $monto){
        $stmt = $this->db->prepare("INSERT INTO PagoExtras (id_pago, descripcion, monto) VALUES (?,?,?)");
        $stmt->execute([$idPago, $descripcion, $monto]);
    }

    public function listarPorPago($idPago){
        $stmt = $this->db->prepare("SELECT * FROM PagoExtras WHERE id_pago=?");
        $stmt->execute([$idPago]);
        return $stmt->fetchAll();
    }
}
