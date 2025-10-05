<?php
declare(strict_types=1);
/** Modelo Cita */
require_once __DIR__ . '/BaseModel.php';

class Cita extends BaseModel {

    /** Crea cita y retorna ID (int) */
    public function crear(array $data): int {
        $sql = "INSERT INTO Cita
                (id_paciente, id_psicologo, fecha_hora, motivo_consulta, estado_cita, qr_code, estado)
                VALUES (?,?,?,?, 'pendiente', ?, 'activo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['id_paciente'],
            $data['id_psicologo'],
            $data['fecha_hora'],
            $data['motivo_consulta'],
            $data['qr_code']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Listado activo */
    public function listar(): array {
        $sql = "SELECT c.*,
                       u.nombre AS paciente_nombre,
                       u.email  AS paciente_email,
                       ps.id    AS psicologo_id
                FROM Cita c
                JOIN Paciente  p  ON p.id = c.id_paciente
                JOIN Usuario   u  ON u.id = p.id_usuario
                JOIN Psicologo ps ON ps.id = c.id_psicologo
                WHERE c.estado = 'activo'
                ORDER BY c.fecha_hora DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Obtener por ID */
    public function obtener(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Cita WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Actualizar código QR */
    public function actualizarQr(int $id, string $qrPath): bool {
        $stmt = $this->db->prepare("UPDATE Cita SET qr_code = ? WHERE id = ?");
        return $stmt->execute([$qrPath, $id]);
    }

    /** Cambiar estado_cita (pendiente, confirmada, cancelada, etc.) */
    public function cambiarEstadoCita(int $id, string $estado): bool {
        $stmt = $this->db->prepare("UPDATE Cita SET estado_cita = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }

    /** Soft delete (estado = inactivo) */
    public function desactivar(int $id): bool {
        $stmt = $this->db->prepare("UPDATE Cita SET estado = 'inactivo' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Actualizar datos básicos (sin QR) */
    public function actualizar(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE Cita SET id_paciente=?, id_psicologo=?, fecha_hora=?, motivo_consulta=? WHERE id=?"
        );
        return $stmt->execute([
            $data['id_paciente'],
            $data['id_psicologo'],
            $data['fecha_hora'],
            $data['motivo_consulta'],
            $id
        ]);
    }

    /** Listar por psicólogo */
    public function listarPorPsicologo(int $idPsicologo): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM Cita WHERE id_psicologo = ? AND estado='activo' ORDER BY fecha_hora DESC"
        );
        $stmt->execute([$idPsicologo]);
        return $stmt->fetchAll();
    }
}
