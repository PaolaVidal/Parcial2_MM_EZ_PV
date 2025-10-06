<?php
/** Modelo Paciente */
require_once __DIR__ . '/BaseModel.php';

class Paciente extends BaseModel {

    /* ========= Creación ========= */

    // Alta mínima (usada por AdminController->pacientes)
    public function crear(int $idUsuario, array $data = []): bool {
        $st = $this->db->prepare(
            "INSERT INTO pacientes (id_usuario, telefono, direccion, estado)
             VALUES (:u, :tel, :dir, 'activo')"
        );
        return $st->execute([
            ':u'   => $idUsuario,
            ':tel' => $data['telefono']  ?? '',
            ':dir' => $data['direccion'] ?? ''
        ]);
    }

    // Alta completa opcional (si tus columnas existen)
    public function crearCompleto(int $idUsuario, array $data): int {
        $st = $this->db->prepare(
            "INSERT INTO pacientes
             (id_usuario, fecha_nacimiento, genero, direccion, telefono, historial_clinico, dui, codigo_acceso, estado)
             VALUES (:u,:fn,:g,:dir,:tel,:hist,:dui,:code,'activo')"
        );
        $code = strtoupper(bin2hex(random_bytes(6)));
        $st->execute([
            ':u'    => $idUsuario,
            ':fn'   => $data['fecha_nacimiento'] ?? null,
            ':g'    => $data['genero'] ?? null,
            ':dir'  => $data['direccion'] ?? '',
            ':tel'  => $data['telefono'] ?? '',
            ':hist' => $data['historial_clinico'] ?? null,
            ':dui'  => $data['dui'] ?? null,
            ':code' => $code
        ]);
        return (int)$this->db->lastInsertId();
    }

    /* ========= Lectura ========= */

    public function listarTodos(): array {
        $sql = "SELECT p.*, u.nombre, u.email, u.estado
                FROM pacientes p
                JOIN usuarios u ON u.id = p.id_usuario
                ORDER BY u.nombre";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarActivos(): array {
        $sql = "SELECT p.*, u.nombre, u.email
                FROM pacientes p
                JOIN usuarios u ON u.id = p.id_usuario
                WHERE p.estado='activo' AND u.estado='activo'
                ORDER BY u.nombre";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(int $id): ?array {
        $st = $this->db->prepare(
            "SELECT p.*, u.nombre, u.email, u.estado AS estado_usuario
             FROM pacientes p
             JOIN usuarios u ON u.id = p.id_usuario
             WHERE p.id = :id LIMIT 1"
        );
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function obtenerPorUsuario(int $idUsuario): ?array {
        $st = $this->db->prepare("SELECT * FROM pacientes WHERE id_usuario=:u LIMIT 1");
        $st->execute([':u'=>$idUsuario]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function getByDui(string $dui): ?array {
        $st = $this->db->prepare("SELECT * FROM pacientes WHERE dui=:d AND estado='activo' LIMIT 1");
        $st->execute([':d'=>$dui]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function getByCodigo(string $codigo): ?array {
        $st = $this->db->prepare("SELECT * FROM pacientes WHERE codigo_acceso=:c AND estado='activo' LIMIT 1");
        $st->execute([':c'=>$codigo]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /* ========= Actualización ========= */

    public function actualizar(int $id, array $data): bool {
        $st = $this->db->prepare(
            "UPDATE pacientes
             SET telefono = :tel,
                 direccion = :dir
             WHERE id = :id"
        );
        return $st->execute([
            ':tel'=>$data['telefono'] ?? '',
            ':dir'=>$data['direccion'] ?? '',
            ':id'=>$id
        ]);
    }

    // Campo genérico validado (usado por solicitudes)
    public function actualizarCampo(int $idPaciente, string $campo, string $valor): bool {
        $permitidos = ['telefono','direccion','historial_clinico','genero','dui'];
        if(!in_array($campo,$permitidos,true)) {
            throw new Exception('Campo no permitido');
        }
        $sql = "UPDATE pacientes SET $campo = :v WHERE id = :id";
        $st = $this->db->prepare($sql);
        return $st->execute([':v'=>$valor, ':id'=>$idPaciente]);
    }

    // Alias anterior
    public function updateCampoPermitido(int $id, string $campo, string $valor): bool {
        try {
            return $this->actualizarCampo($id,$campo,$valor);
        } catch(Exception $e){
            return false;
        }
    }

    public function regenerarCodigo(int $id): ?string {
        // Solo si existe columna codigo_acceso
        try {
            $nuevo = strtoupper(bin2hex(random_bytes(8)));
            $st = $this->db->prepare("UPDATE pacientes SET codigo_acceso=:c WHERE id=:id");
            $st->execute([':c'=>$nuevo, ':id'=>$id]);
            return $nuevo;
        } catch(Throwable $e){
            return null;
        }
    }

    /* ========= Eliminación ========= */

    public function eliminar(int $id): bool {
        // Eliminación física; si prefieres lógica cambia a estado='inactivo'
        $st = $this->db->prepare("DELETE FROM pacientes WHERE id=:id");
        return $st->execute([':id'=>$id]);
    }
}
