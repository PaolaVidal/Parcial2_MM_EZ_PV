<?php
require_once __DIR__ . '/BaseModel.php';

class Evaluacion extends BaseModel {
    protected $table = 'Evaluacion';

    /**
     * Obtener todas las evaluaciones de una cita
     */
    public function obtenerPorCita(int $idCita): array {
        $st = $this->pdo()->prepare(
            "SELECT * FROM {$this->table} 
             WHERE id_cita = ? AND estado = 'activo' 
             ORDER BY id ASC"
        );
        $st->execute([$idCita]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear una nueva evaluación
     */
    public function crear(int $idCita, int $estadoEmocional, string $comentarios): ?int {
        // Validar que estado emocional esté en rango 1-10
        if ($estadoEmocional < 1 || $estadoEmocional > 10) {
            throw new InvalidArgumentException('El estado emocional debe estar entre 1 y 10');
        }

        try {
            $st = $this->pdo()->prepare(
                "INSERT INTO {$this->table} (id_cita, estado_emocional, comentarios, estado) 
                 VALUES (?, ?, ?, 'activo')"
            );
            $st->execute([$idCita, $estadoEmocional, $comentarios]);
            return (int)$this->pdo()->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando evaluación: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener una evaluación por ID
     */
    public function obtener(int $id): ?array {
        $st = $this->pdo()->prepare(
            "SELECT * FROM {$this->table} WHERE id = ? AND estado = 'activo'"
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Actualizar una evaluación
     */
    public function actualizar(int $id, int $estadoEmocional, string $comentarios): bool {
        if ($estadoEmocional < 1 || $estadoEmocional > 10) {
            throw new InvalidArgumentException('El estado emocional debe estar entre 1 y 10');
        }

        try {
            $st = $this->pdo()->prepare(
                "UPDATE {$this->table} 
                 SET estado_emocional = ?, comentarios = ? 
                 WHERE id = ? AND estado = 'activo'"
            );
            $st->execute([$estadoEmocional, $comentarios, $id]);
            return $st->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error actualizando evaluación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar (soft delete) una evaluación
     */
    public function eliminar(int $id): bool {
        try {
            $st = $this->pdo()->prepare(
                "UPDATE {$this->table} SET estado = 'inactivo' WHERE id = ?"
            );
            $st->execute([$id]);
            return $st->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error eliminando evaluación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar evaluaciones de una cita
     */
    public function contarPorCita(int $idCita): int {
        $st = $this->pdo()->prepare(
            "SELECT COUNT(*) FROM {$this->table} 
             WHERE id_cita = ? AND estado = 'activo'"
        );
        $st->execute([$idCita]);
        return (int)$st->fetchColumn();
    }

    /**
     * Obtener estadísticas de evaluaciones de un psicólogo
     */
    public function estadisticasPorPsicologo(int $idPsicologo): array {
        $st = $this->pdo()->prepare(
            "SELECT 
                COUNT(*) as total_evaluaciones,
                AVG(e.estado_emocional) as promedio_estado,
                MIN(e.estado_emocional) as min_estado,
                MAX(e.estado_emocional) as max_estado
             FROM {$this->table} e
             JOIN Cita c ON c.id = e.id_cita
             WHERE c.id_psicologo = ? AND e.estado = 'activo'"
        );
        $st->execute([$idPsicologo]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [
            'total_evaluaciones' => 0,
            'promedio_estado' => 0,
            'min_estado' => 0,
            'max_estado' => 0
        ];
    }
}
