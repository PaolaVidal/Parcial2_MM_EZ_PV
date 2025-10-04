<?php
/** Modelo Pago */
require_once 'BaseModel.php';

class Pago extends BaseModel {
    public function crearParaCita($idCita){
        $sql = "INSERT INTO Pago (id_cita, monto_base, monto_total, estado_pago, estado) VALUES (?, 35.00, 35.00, 'pendiente','activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCita]);
        return $this->db->lastInsertId();
    }

    public function listar(){
        $sql = "SELECT pg.*, ct.id_paciente, ct.id_psicologo FROM Pago pg JOIN Cita ct ON ct.id = pg.id_cita WHERE pg.estado='activo'";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtener($id){
        $stmt = $this->db->prepare("SELECT * FROM Pago WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function recalcularTotal($id){
        // Suma de extras
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(monto),0) as extras FROM PagoExtras WHERE id_pago=?");
        $stmt->execute([$id]);
        $extras = (float)$stmt->fetch()['extras'];

        $pago = $this->obtener($id);
        $montoTotal = (float)$pago['monto_base'] + $extras;

        $upd = $this->db->prepare("UPDATE Pago SET monto_total=? WHERE id=?");
        $upd->execute([$montoTotal, $id]);
        return $montoTotal;
    }

    public function marcarPagado($id){
        $stmt = $this->db->prepare("UPDATE Pago SET estado_pago='pagado' WHERE id=?");
        $stmt->execute([$id]);
    }
}
