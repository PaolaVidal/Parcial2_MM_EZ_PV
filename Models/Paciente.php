<?php
/** Modelo Paciente */
require_once __DIR__ . '/BaseModel.php';

class Paciente extends BaseModel {
    /**
     * Crea un paciente asociado a un usuario (FK id_usuario).
     * Se asume que la tabla Paciente ahora tiene columna id_usuario INT NOT NULL.
     */
    public function crear($data){
        $sql = "INSERT INTO Paciente (id_usuario, fecha_nacimiento, genero, direccion, telefono, historial_clinico, estado) VALUES (?,?,?,?,?, ?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['id_usuario'], $data['fecha_nacimiento'], $data['genero'], $data['direccion'], $data['telefono'], $data['historial_clinico'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function listar(){
        return $this->db->query("SELECT p.*, u.nombre, u.email FROM Paciente p JOIN Usuario u ON u.id = p.id_usuario WHERE p.estado='activo'")->fetchAll();
    }

    public function obtenerPorUsuario(int $idUsuario): ?array {
        $st = $this->db->prepare("SELECT * FROM Paciente WHERE id_usuario=? LIMIT 1");
        $st->execute([$idUsuario]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function crearPorUsuario(int $idUsuario, array $data): int {
        $st = $this->db->prepare(
            "INSERT INTO Paciente
             (id_usuario, fecha_nacimiento, genero, correo, direccion, telefono, historial_clinico, estado)
             VALUES (?,?,?,?,?,?,?, 'activo')"
        );
        $st->execute([
            $idUsuario,
            $data['fecha_nacimiento'],
            $data['genero'],
            $data['correo'],
            $data['direccion'],
            $data['telefono'],
            $data['historial_clinico']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getByDui(string $dui): ?array {
        $st = $this->db->prepare("SELECT * FROM Paciente WHERE dui=? AND estado='activo' LIMIT 1");
        $st->execute([$dui]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function getByCodigo(string $codigo): ?array {
        $st = $this->db->prepare("SELECT * FROM Paciente WHERE codigo_acceso=? AND estado='activo' LIMIT 1");
        $st->execute([$codigo]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function regenerarCodigo(int $id): string {
        $nuevo = strtoupper(bin2hex(random_bytes(8)));
        $st = $this->db->prepare("UPDATE Paciente SET codigo_acceso=? WHERE id=?");
        $st->execute([$nuevo,$id]);
        return $nuevo;
    }

    public function updateCampoPermitido(int $id, string $campo, string $valor): bool {
        $permitidos = ['telefono','direccion','historial_clinico','genero','correo'];
        if(!in_array($campo,$permitidos,true)) return false;
        $sql = "UPDATE Paciente SET $campo=? WHERE id=? LIMIT 1";
        $st = $this->db->prepare($sql);
        return $st->execute([$valor,$id]);
    }
}
