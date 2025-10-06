<?php
require_once __DIR__ . '/BaseModel.php';

class HorarioPsicologo extends BaseModel {
    public function listarPorPsicologo(int $idPsico): array {
        $st = $this->db->prepare("SELECT * FROM Horario_Psicologo WHERE id_psicologo=? ORDER BY FIELD(dia_semana,'lunes','martes','miércoles','jueves','viernes','sábado','domingo'), hora_inicio");
        $st->execute([$idPsico]);
        return $st->fetchAll();
    }

    public function listarTodos(): array {
        $sql = "SELECT h.*, p.id_usuario, u.nombre as nombre_psicologo FROM Horario_Psicologo h JOIN Psicologo p ON p.id=h.id_psicologo JOIN Usuario u ON u.id=p.id_usuario ORDER BY p.id, FIELD(dia_semana,'lunes','martes','miércoles','jueves','viernes','sábado','domingo'), hora_inicio";
        return $this->db->query($sql)->fetchAll();
    }

    public function crear(int $idPsico, string $dia, string $inicio, string $fin): int {
        if(!preg_match('/^(lunes|martes|miércoles|jueves|viernes|sábado|domingo)$/u',$dia)) throw new InvalidArgumentException('Día inválido');
        if(!$this->validarHora($inicio) || !$this->validarHora($fin)) throw new InvalidArgumentException('Formato hora inválido');
        if($fin <= $inicio) throw new InvalidArgumentException('Fin debe ser mayor que inicio');
        // Chequear solapamiento
        $st = $this->db->prepare("SELECT COUNT(*) FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana=? AND NOT( ? >= hora_fin OR ? <= hora_inicio )");
        $st->execute([$idPsico,$dia,$inicio,$fin]);
        if((int)$st->fetchColumn()>0) throw new RuntimeException('Se traslapa con otro horario');
        $ins = $this->db->prepare("INSERT INTO Horario_Psicologo (id_psicologo,dia_semana,hora_inicio,hora_fin) VALUES (?,?,?,?)");
        $ins->execute([$idPsico,$dia,$inicio,$fin]);
        return (int)$this->db->lastInsertId();
    }

    public function eliminar(int $id): bool {
        $st = $this->db->prepare("DELETE FROM Horario_Psicologo WHERE id_horario_psicologo=?");
        return $st->execute([$id]);
    }

    private function validarHora(string $h): bool { return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$h) === 1; }
}
