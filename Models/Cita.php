<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Cita extends BaseModel {

    /** Genera un token seguro para QR */
    private function generarToken(): string {
        return bin2hex(random_bytes(16)); // 32 hex chars
    }

    /**
     * Crear cita desde psicólogo con generación automática de token y QR.
     * $data requiere: id_paciente, id_psicologo, fecha_hora (Y-m-d H:i), motivo_consulta
     */
    public function crearPorPsicologo(array $data): int {
        if(empty($data['id_paciente'])||empty($data['id_psicologo'])||empty($data['fecha_hora'])){
            throw new InvalidArgumentException('Datos incompletos para crear cita');
        }
        // Normalizar fecha a formato aceptable
        $fh = date('Y-m-d H:i:s', strtotime($data['fecha_hora']));
        $token = $this->generarToken();
        // Generar QR (guardamos la imagen, campo qr_code lo usamos como token o ruta? Usaremos token para búsqueda)
        try {
            $fileName = 'cita_'.$token.'.png';
            $rutaPublica = QRHelper::generarQR($token,'cita',$fileName); // crea archivo reproducible
        } catch(Throwable $e){
            // fallback: sin imagen
            $rutaPublica = '';
        }
        $citaId = $this->crear([
            'id_paciente'     => (int)$data['id_paciente'],
            'id_psicologo'    => (int)$data['id_psicologo'],
            'fecha_hora'      => $fh,
            'motivo_consulta' => $data['motivo_consulta'] ?? '',
            'qr_code'         => $token // almacenamos token para validación por escaneo
        ]);
        // Podemos registrar la ruta imagen en otra tabla o dejar para futuro. Si queremos asociar, se podría crear campo adicional.
        return $citaId;
    }

    /** Confirmar cita por token QR, validando que pertenezca al psicólogo y esté pendiente */
    public function confirmarPorQR(string $token, int $idPsicologo): array {
        $token = trim($token);
        if($token==='') return ['ok'=>false,'msg'=>'Token vacío'];
        $cita = $this->obtenerPorQr($token);
        if(!$cita) return ['ok'=>false,'msg'=>'QR no encontrado'];
        if((int)$cita['id_psicologo'] !== $idPsicologo) return ['ok'=>false,'msg'=>'No pertenece a este psicólogo'];
        if($cita['estado_cita']==='realizada') return ['ok'=>true,'msg'=>'Ya estaba marcada realizada','cita'=>$cita];
        if($cita['estado_cita']!=='pendiente') return ['ok'=>false,'msg'=>'Estado no permite confirmación'];
        $this->marcarRealizada((int)$cita['id']);
        $cita['estado_cita']='realizada';
        return ['ok'=>true,'msg'=>'Cita confirmada','cita'=>$cita];
    }

    /** Listar citas de un psicólogo (pendientes futuras + últimas realizadas) */
    public function listarPsicologo(int $idPsico, int $limitRealizadas = 10): array {
        $pend = $this->db->prepare("SELECT * FROM Cita WHERE id_psicologo=? AND estado='activo' AND estado_cita='pendiente' ORDER BY fecha_hora ASC");
        $pend->execute([$idPsico]);
        
        // Realizadas: NO filtrar por estado para mostrar historial (ya tienen estado='inactivo')
        $real = $this->db->prepare("SELECT * FROM Cita WHERE id_psicologo=? AND estado_cita='realizada' ORDER BY fecha_hora DESC LIMIT ?");
        $real->bindValue(1,$idPsico, PDO::PARAM_INT);
        $real->bindValue(2,$limitRealizadas, PDO::PARAM_INT);
        $real->execute();
        
        // Agregar canceladas (últimas 20) - incluye inactivas para mostrar historial
        $canc = $this->db->prepare("SELECT * FROM Cita WHERE id_psicologo=? AND estado_cita='cancelada' ORDER BY fecha_hora DESC LIMIT 20");
        $canc->execute([$idPsico]);
        
        return [
            'pendientes' => $pend->fetchAll(),
            'realizadas' => $real->fetchAll(),
            'canceladas' => $canc->fetchAll()
        ];
    }

    public function crear(array $data): int {
        $sql = "INSERT INTO Cita (id_paciente,id_psicologo,fecha_hora,estado_cita,motivo_consulta,qr_code,estado)
                VALUES (?,?,?,'pendiente',?,?,'activo')";
        $st = $this->db->prepare($sql);
        $st->execute([
            $data['id_paciente'],
            $data['id_psicologo'],
            $data['fecha_hora'],
            $data['motivo_consulta'],
            $data['qr_code']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listarPaciente(int $idPaciente): array {
        $sql = "SELECT c.*, 
                       u.nombre as psicologo_nombre,
                       p.especialidad as psicologo_especialidad
                FROM Cita c
                LEFT JOIN Psicologo p ON c.id_psicologo = p.id
                LEFT JOIN Usuario u ON p.id_usuario = u.id
                WHERE c.id_paciente=? AND c.estado='activo' 
                ORDER BY c.fecha_hora DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$idPaciente]);
        return $st->fetchAll();
    }

    public function listarPsicologoPendientes(int $idPsico): array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE id_psicologo=? AND estado_cita='pendiente' AND estado='activo' ORDER BY fecha_hora ASC");
        $st->execute([$idPsico]);
        return $st->fetchAll();
    }

    public function obtener(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE id=?");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function obtenerPorQr(string $token): ?array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE qr_code=? LIMIT 1");
        $st->execute([$token]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function marcarRealizada(int $id): bool {
        // Marcar como realizada Y liberar el horario (estado='inactivo')
        $st = $this->db->prepare("UPDATE Cita SET estado_cita='realizada', estado='inactivo' WHERE id=?");
        return $st->execute([$id]);
    }

    public function cancelar(int $id, string $motivo=''): bool {
        $st = $this->db->prepare("UPDATE Cita SET estado_cita='cancelada', motivo_consulta=CONCAT(motivo_consulta, '\n[CANCELADA] ', ?) WHERE id=?");
        return $st->execute([$motivo,$id]);
    }

    public function reprogramar(int $id, string $nuevaFecha): bool {
        $st = $this->db->prepare("UPDATE Cita SET fecha_hora=?, estado_cita='pendiente' WHERE id=? AND estado_cita<>'realizada'");
        return $st->execute([$nuevaFecha,$id]);
    }

    public function reasignarPsicologo(int $id, int $nuevoPsico): bool {
        $st = $this->db->prepare("UPDATE Cita SET id_psicologo=? WHERE id=?");
        return $st->execute([$nuevoPsico,$id]);
    }

    /** Reasignar cita a nuevo psicólogo con nueva fecha/hora */
    public function reasignar(int $id, int $nuevoPsico, string $nuevaFechaHora): bool {
        $st = $this->db->prepare("UPDATE Cita SET id_psicologo=?, fecha_hora=?, estado_cita='pendiente' WHERE id=?");
        return $st->execute([$nuevoPsico, $nuevaFechaHora, $id]);
    }

    /** Obtener todas las citas con datos completos (JOIN con Paciente y Usuario del psicólogo) */
    public function todas(): array {
        $sql = "SELECT c.id, c.id_paciente, c.id_psicologo, c.fecha_hora, c.estado_cita, c.motivo_consulta, c.qr_code,
                       p.nombre as paciente_nombre, p.dui as paciente_dui,
                       u.nombre as psicologo_nombre
                FROM Cita c
                LEFT JOIN Paciente p ON c.id_paciente = p.id
                LEFT JOIN Psicologo ps ON c.id_psicologo = ps.id
                LEFT JOIN Usuario u ON ps.id_usuario = u.id
                WHERE c.estado='activo'
                ORDER BY c.fecha_hora DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function estadisticasEstado(): array {
        $sql = "SELECT estado_cita estado, COUNT(*) total FROM Cita GROUP BY estado_cita";
        return $this->db->query($sql)->fetchAll();
    }

    public function citasPorRango(string $inicio, string $fin): array {
        $st = $this->db->prepare("SELECT * FROM Cita WHERE fecha_hora BETWEEN ? AND ? ORDER BY fecha_hora");
        $st->execute([$inicio,$fin]);
        return $st->fetchAll();
    }

    /**
     * Retorna próximos slots libres (horarios) para un psicólogo a partir de hoy.
     * Ajusta $horasBase según tu lógica real.
     */
    public function proximosSlots(int $idPsicologo, int $limite = 5): array {
        $horasBase = ['08:00','09:00','10:00','11:00','14:00','15:00','16:00'];
        $slots = [];
        $fecha = new DateTime(); // hoy
        while(count($slots) < $limite && $fecha->diff(new DateTime('+15 days'))->days >= 0){
            $f = $fecha->format('Y-m-d');
            $st = $this->db->prepare("SELECT TIME(fecha_hora) h FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora)=?");
            $st->execute([$idPsicologo,$f]);
            $ocupadas = array_column($st->fetchAll(PDO::FETCH_ASSOC),'h');
            foreach($horasBase as $h){
                if(!in_array($h,$ocupadas,true)){
                    $slots[] = $f.' '.$h;
                    if(count($slots) >= $limite) break 2;
                }
            }
            $fecha->modify('+1 day');
        }
        return $slots;
    }

    public function cuposDia(int $idPsicologo, string $fecha): int {
        $horasBase = 7; // coincide con horasBase count
        $st = $this->db->prepare("SELECT COUNT(*) c FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora)=?");
        $st->execute([$idPsicologo,$fecha]);
        $usadas = (int)$st->fetchColumn();
        return max(0, $horasBase - $usadas);
    }

    public function cuposSemana(int $idPsicologo, string $desde): int {
        $ini = new DateTime($desde);
        $fin = clone $ini; $fin->modify('+6 day');
        $horasBase = 7 * 7; // 7 horas * 7 días
        $st = $this->db->prepare("SELECT COUNT(*) c FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ?");
        $st->execute([$idPsicologo,$ini->format('Y-m-d'),$fin->format('Y-m-d')]);
        $usadas = (int)$st->fetchColumn();
        return max(0, $horasBase - $usadas);
    }
}
