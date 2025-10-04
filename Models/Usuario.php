<?php
/** Modelo Usuario */
require_once 'BaseModel.php';

class Usuario extends BaseModel {
    public function crear($data){
        $sql = "INSERT INTO Usuario (nombre, email, passwordd, rol, estado) VALUES (?,?,?,?,?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nombre'], $data['email'], password_hash($data['passwordd'], PASSWORD_BCRYPT), $data['rol'] ?? 'paciente', 'activo'
        ]);
        return $this->db->lastInsertId();
    }

    public function obtenerPorEmail($email){
        $stmt = $this->db->prepare("SELECT * FROM Usuario WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Autentica usuario por email y contraseña plana.
     * Retorna array usuario o false si credenciales inválidas.
     */
    public function autenticar($email, $password){
        $usuario = $this->obtenerPorEmail($email);
        if($usuario && password_verify($password, $usuario['passwordd'])){
            return $usuario;
        }
        return false;
    }
}
