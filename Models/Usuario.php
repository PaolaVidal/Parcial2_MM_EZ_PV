<?php
/** Modelo Usuario */
require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel
{

    private string $tabla = 'Usuario';
    private string $colPass = 'passwordd';

    public function emailExiste(string $email): bool
    {
        $st = $this->db->prepare("SELECT id FROM {$this->tabla} WHERE email=? LIMIT 1");
        $st->execute([$email]);
        return (bool) $st->fetchColumn();
    }

    public function crear(array $data): int
    {
        // Validar rol permitido según ENUM actual
        if (!in_array($data['rol'], ['admin', 'psicologo'], true)) {
            throw new Exception('Rol no permitido por el ENUM de la tabla Usuario');
        }
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $sql = "INSERT INTO {$this->tabla} (nombre,email,{$this->colPass},rol,estado)
                VALUES(:n,:e,:p,:r,'activo')";
        $st = $this->db->prepare($sql);
        if (
            !$st->execute([
                ':n' => $data['nombre'],
                ':e' => $data['email'],
                ':p' => $hash,
                ':r' => $data['rol']
            ])
        ) {
            $err = $st->errorInfo();
            throw new Exception('No se pudo crear usuario: ' . $err[2]);
        }
        return (int) $this->db->lastInsertId();
    }

    /**
     * Autentica solo usuarios ACTIVOS. Si el email existe pero está inactivo retorna null igualmente.
     * Esto evita revelar si una cuenta está desactivada.
     */
    public function autenticar(string $email, string $password): ?array
    {
        $st = $this->db->prepare("SELECT * FROM {$this->tabla} WHERE email=? LIMIT 1");
        $st->execute([$email]);
        $usuario = $st->fetch(PDO::FETCH_ASSOC);
        if (!$usuario)
            return null; // no existe
        if (($usuario['estado'] ?? '') !== 'activo')
            return null; // inactivo
        if (password_verify($password, $usuario[$this->colPass])) {
            unset($usuario[$this->colPass]);
            return $usuario;
        }
        return null; // password incorrecto
    }

    /** Obtiene estado actual de un usuario por id */
    public function obtenerEstado(int $id): ?string
    {
        $st = $this->db->prepare("SELECT estado FROM {$this->tabla} WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetchColumn();
        return $r !== false ? (string) $r : null;
    }

    public function listarTodos(?string $rol = null): array
    {
        if ($rol) {
            $st = $this->db->prepare("SELECT id,nombre,email,rol,estado FROM {$this->tabla} WHERE rol=? ORDER BY nombre");
            $st->execute([$rol]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->db->query("SELECT id,nombre,email,rol,estado FROM {$this->tabla} ORDER BY nombre")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $st = $this->db->prepare("SELECT id,nombre,email,rol,estado FROM {$this->tabla} WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function actualizar(int $id, array $data): bool
    {
        $campos = [];
        $params = [':id' => $id];
        if (array_key_exists('nombre', $data)) {
            $campos[] = 'nombre=:nombre';
            $params[':nombre'] = $data['nombre'];
        }
        if (array_key_exists('email', $data)) {
            $campos[] = 'email=:email';
            $params[':email'] = $data['email'];
        }
        if (!$campos)
            return false;
        $sql = "UPDATE {$this->tabla} SET " . implode(',', $campos) . " WHERE id=:id";
        $st = $this->db->prepare($sql);
        return $st->execute($params);
    }

    public function actualizarPassword(int $id, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st = $this->db->prepare("UPDATE {$this->tabla} SET {$this->colPass} = :p WHERE id = :id");
        return $st->execute([':p' => $hash, ':id' => $id]);
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['activo', 'inactivo'], true))
            return false;
        $st = $this->db->prepare("UPDATE {$this->tabla} SET estado=? WHERE id=? LIMIT 1");
        return $st->execute([$estado, $id]);
    }

    public function resetPassword(int $id, string $nueva): bool
    {
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $st = $this->db->prepare("UPDATE {$this->tabla} SET {$this->colPass}=? WHERE id=? LIMIT 1");
        return $st->execute([$hash, $id]);
    }

    public function conteoActivosInactivos(): array
    {
        $sql = "SELECT estado, COUNT(*) c FROM {$this->tabla} GROUP BY estado";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $out = ['activo' => 0, 'inactivo' => 0];
        foreach ($rows as $r) {
            $out[$r['estado']] = (int) $r['c'];
        }
        return $out;
    }
}
