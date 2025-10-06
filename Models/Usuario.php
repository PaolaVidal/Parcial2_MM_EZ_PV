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

    /* ========================= *
       Métodos de Administración
       ========================= */

    public function listarTodos(?string $rol=null): array {
        if($rol){
            $st = $this->db->prepare("SELECT id,nombre,email,rol,estado FROM Usuario WHERE rol=? ORDER BY nombre");
            $st->execute([$rol]);
            return $st->fetchAll();
        }
        return $this->db->query("SELECT id,nombre,email,rol,estado FROM Usuario ORDER BY nombre")->fetchAll();
    }

    public function obtener(int $id): ?array {
        $st = $this->db->prepare("SELECT id,nombre,email,rol,estado FROM Usuario WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function actualizar(int $id, array $data): bool {
        $campos = [];$vals=[];
        if(isset($data['nombre'])){ $campos[]='nombre=?'; $vals[]=$data['nombre']; }
        if(isset($data['email'])){ $campos[]='email=?'; $vals[]=$data['email']; }
        if(isset($data['rol'])){ $campos[]='rol=?'; $vals[]=$data['rol']; }
        if(empty($campos)) return false;
        $vals[]=$id;
        $sql = 'UPDATE Usuario SET '.implode(',', $campos).' WHERE id=? LIMIT 1';
        $st = $this->db->prepare($sql);
        return $st->execute($vals);
    }

    public function cambiarEstado(int $id, string $estado): bool {
        if(!in_array($estado,['activo','inactivo'],true)) return false;
        $st = $this->db->prepare("UPDATE Usuario SET estado=? WHERE id=? LIMIT 1");
        return $st->execute([$estado,$id]);
    }

    public function resetPassword(int $id, string $nueva): bool {
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $st = $this->db->prepare("UPDATE Usuario SET passwordd=? WHERE id=? LIMIT 1");
        return $st->execute([$hash,$id]);
    }

    public function conteoActivosInactivos(): array {
        $sql = "SELECT estado, COUNT(*) c FROM Usuario GROUP BY estado";
        $rows = $this->db->query($sql)->fetchAll();
        $out = ['activo'=>0,'inactivo'=>0];
        foreach($rows as $r){ $out[$r['estado']] = (int)$r['c']; }
        return $out;
    }
}
