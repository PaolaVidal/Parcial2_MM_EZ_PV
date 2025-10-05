<?php
/** Modelo Usuario */
require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel {

    public function emailExiste(string $email): bool {
        $st = $this->db->prepare("SELECT id FROM Usuario WHERE email=? LIMIT 1");
        $st->execute([$email]);
        return (bool)$st->fetchColumn();
    }

    public function crear(array $data): int {
        $rol = $data['rol'] ?? 'paciente';
        $st = $this->db->prepare(
            "INSERT INTO Usuario (nombre,email,passwordd,rol,estado) VALUES (?,?,?,?, 'activo')"
        );
        $st->execute([
            $data['nombre'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $rol
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function obtenerPorEmail(string $email): ?array {
        $st = $this->db->prepare("SELECT * FROM Usuario WHERE email=? LIMIT 1");
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function autenticar(string $email, string $password): ?array {
        $usuario = $this->obtenerPorEmail($email);
        if ($usuario && password_verify($password, $usuario['passwordd'])) {
            unset($usuario['passwordd']);
            return $usuario;
        }
        return null;
    }
}
