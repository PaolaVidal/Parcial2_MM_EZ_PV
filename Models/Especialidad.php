<?php
require_once __DIR__ . '/BaseModel.php';

class Especialidad extends BaseModel {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Listar todas las especialidades
     */
    public function listarTodas(): array {
        $sql = "SELECT * FROM Especialidad ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Listar solo especialidades activas
     */
    public function listarActivas(): array {
        $sql = "SELECT * FROM Especialidad WHERE estado='activo' ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener una especialidad por ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM Especialidad WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Crear una nueva especialidad
     */
    public function crear(array $data): int {
        $sql = "INSERT INTO Especialidad (nombre, descripcion, estado) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['estado'] ?? 'activo'
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Actualizar una especialidad
     */
    public function actualizar(int $id, array $data): bool {
        $sql = "UPDATE Especialidad SET nombre = ?, descripcion = ?, estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['estado'] ?? 'activo',
            $id
        ]);
    }
    
    /**
     * Cambiar estado (activo/inactivo)
     */
    public function cambiarEstado(int $id, string $estado): bool {
        $sql = "UPDATE Especialidad SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }
    
    /**
     * Verificar si una especialidad tiene psicólogos asignados
     */
    public function tienePsicologos(int $id): bool {
        $sql = "SELECT COUNT(*) FROM Psicologo WHERE id_especialidad = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Contar psicólogos por especialidad
     */
    public function contarPsicologos(int $id): int {
        $sql = "SELECT COUNT(*) FROM Psicologo WHERE id_especialidad = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Eliminar especialidad (solo si no tiene psicólogos asignados)
     */
    public function eliminar(int $id): bool {
        if ($this->tienePsicologos($id)) {
            throw new Exception('No se puede eliminar una especialidad con psicólogos asignados');
        }
        $sql = "DELETE FROM Especialidad WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
