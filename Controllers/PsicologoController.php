<?php
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class PsicologoController {

    private function requirePsicologo(): void {
        if(!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'psicologo'){
            header('Location: index.php?controller=Auth&action=login');
            exit;
        }
    }

    private function render(string $vista, array $data = []): void {
        // Sin header/footer porque el index raíz ya monta layout y navbar
        $file = __DIR__ . '/../Views/' . $vista . '.php';
        if(!file_exists($file)) { echo '<div class="alert alert-danger">Vista no encontrada: '.htmlspecialchars($vista).'</div>'; return; }
        extract($data);
        require $file;
    }

    public function dashboard(): void {
        $this->requirePsicologo();
        $idPsico = (int)$_SESSION['usuario']['id'];
        $citaM = new Cita();
        $pagoM = new Pago();
        $slots = $citaM->proximosSlots($idPsico,5);
        $hoy = date('Y-m-d');
        $pendHoy = $this->countEstadoDia($idPsico,'pendiente',$hoy);
        $realHoy = $this->countEstadoDia($idPsico,'realizada',$hoy);
        $ingresos = $this->ingresosPsicologo($idPsico);
        $this->render('psicologo/dashboard',[ 'slots'=>$slots,'pendHoy'=>$pendHoy,'realHoy'=>$realHoy,'ingresos'=>$ingresos ]);
    }

    private function countEstadoDia(int $idPsico,string $estado,string $fecha): int {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND estado_cita=? AND DATE(fecha_hora)=?");
        $st->execute([$idPsico,$estado,$fecha]);
        return (int)$st->fetchColumn();
    }

    private function ingresosPsicologo(int $idPsico): float {
        $db = (new Pago())->pdo();
        $st = $db->prepare("SELECT COALESCE(SUM(p.monto_total),0) FROM Pago p JOIN Cita c ON c.id=p.id_cita WHERE c.id_psicologo=? AND p.estado_pago='pagado'");
        $st->execute([$idPsico]);
        return (float)$st->fetchColumn();
    }

    public function citas(): void {
        $this->requirePsicologo();
        // Respuesta AJAX de slots (fallback si /slots directo falla en hosting)
        if(isset($_GET['ajax']) && $_GET['ajax']==='slots'){
            $this->slots();
            return;
        }
        $idPsico = (int)$_SESSION['usuario']['id'];
        $citaM = new Cita();
        $pacM = new Paciente();
        $list = $citaM->listarPsicologo($idPsico);
        $pacientes = $pacM->listarTodos();
        $this->render('psicologo/citas',[ 'data'=>$list,'pacientes'=>$pacientes ]);
    }

    public function crear(): void {
        $this->requirePsicologo();
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){ header('Location: index.php?controller=Psicologo&action=citas'); return; }
        $idPsico = (int)$_SESSION['usuario']['id'];
        $idPaciente = (int)($_POST['id_paciente'] ?? 0);
        $fecha = trim($_POST['fecha_hora'] ?? '');
        $motivo = trim($_POST['motivo_consulta'] ?? '');
        if(strlen($motivo) > 255) $motivo = substr($motivo,0,255);
        if(!$idPaciente || !$fecha){ header('Location: index.php?controller=Psicologo&action=citas&err=datos'); return; }
        // Validar formato fecha: Y-m-d H:i:s o Y-m-d H:i
        $fh = DateTime::createFromFormat('Y-m-d H:i:s',$fecha) ?: DateTime::createFromFormat('Y-m-d H:i',$fecha);
        if(!$fh){ header('Location: index.php?controller=Psicologo&action=citas&err=formato'); return; }
        // Normalizar a segundos :00
        $fh->setTime((int)$fh->format('H'), (int)$fh->format('i'), 0);
        // Validar minutos 00 o 30
        $min = (int)$fh->format('i');
        if($min !== 0 && $min !== 30){ header('Location: index.php?controller=Psicologo&action=citas&err=minutos'); return; }
        // No permitir pasado
        $now = new DateTime();
        if($fh <= $now){ header('Location: index.php?controller=Psicologo&action=citas&err=pasado'); return; }
        // Validar contra horarios configurados en Horario_Psicologo
        require_once __DIR__ . '/../models/HorarioPsicologo.php';
        $diaSemanaMap = ['Mon'=>'lunes','Tue'=>'martes','Wed'=>'miércoles','Thu'=>'jueves','Fri'=>'viernes','Sat'=>'sábado','Sun'=>'domingo'];
        $diaBD = $diaSemanaMap[$fh->format('D')] ?? 'lunes';
    $dbChk2 = (new Cita())->pdo();
        $stH = $dbChk2->prepare("SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana=?");
        $stH->execute([$idPsico,$diaBD]);
        $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);
        $enRango = false;
        foreach($bloques as $b){
            if($fh->format('H:i:s') >= $b['hora_inicio'] && $fh->format('H:i:s') < $b['hora_fin']){ $enRango = true; break; }
        }
        if(!$enRango){ header('Location: index.php?controller=Psicologo&action=citas&err=fuera_horario'); return; }
        $fecha = $fh->format('Y-m-d H:i:s');
        // Evitar choque con una existente misma fecha_hora para ese psicólogo
    $dbChk = (new Cita())->pdo();
        $stChk = $dbChk->prepare('SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND fecha_hora=? AND estado=\'activo\'' );
        $stChk->execute([$idPsico,$fecha]);
    if((int)$stChk->fetchColumn() > 0){ header('Location: index.php?controller=Psicologo&action=citas&err=ocupado'); return; }
        // Verificar paciente existe
        $pacM = new Paciente();
        $pac = $pacM->getById($idPaciente);
        if(!$pac){ header('Location: index.php?controller=Psicologo&action=citas&err=paciente'); return; }
        try {
            $citaM = new Cita();
            // Primero crear sin QR (es id basado)
            $id = $citaM->crear([
                'id_paciente'=>$idPaciente,
                'id_psicologo'=>$idPsico,
                'fecha_hora'=>$fecha,
                'motivo_consulta'=>$motivo,
                'qr_code'=>''
            ]);
            // Generar QR usando solo el id
            try { QRHelper::generarQR('CITA:'.$id,'cita','cita_id_'.$id.'.png'); } catch(Throwable $e){}
            // Actualizar campo qr_code con el propio id para búsquedas simples
            $pdo = (new Cita())->pdo();
            $pdo->prepare("UPDATE Cita SET qr_code=? WHERE id=?")->execute([$id,$id]);
            header('Location: index.php?controller=Psicologo&action=citas&ok=1');
            return;
        } catch(Throwable $e){
            header('Location: index.php?controller=Psicologo&action=citas&err=ex');
            return;
        }
    }

    public function scan(): void {
        $this->requirePsicologo();
        $this->render('psicologo/scan');
    }

    public function scanProcesar(): void {
        $this->requirePsicologo();
        $raw = trim($_POST['token'] ?? '');
        $idPsico = (int)$_SESSION['usuario']['id'];
        $citaM = new Cita();
        // Aceptar formatos: ID (numérico) o CITA:ID
        if(stripos($raw,'CITA:')===0){ $raw = substr($raw,5); }
        if(!ctype_digit($raw)){
            $res=['ok'=>false,'msg'=>'Formato inválido. Use ID o CITA:ID'];
        } else {
            $id = (int)$raw;
            $cita = $citaM->obtener($id);
            if(!$cita){ $res=['ok'=>false,'msg'=>'ID no encontrado']; }
            elseif((int)$cita['id_psicologo']!==$idPsico){ $res=['ok'=>false,'msg'=>'No pertenece a este psicólogo']; }
            else {
                if($cita['estado_cita']==='pendiente') $citaM->marcarRealizada($id);
                $cita['estado_cita']='realizada';
                $res=['ok'=>true,'msg'=>'Cita confirmada','cita'=>$cita];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    /** Devuelve slots disponibles (intervalo 30 o 60) para una fecha dada */
    public function slots(): void {
        $this->requirePsicologo();
        header('Content-Type: application/json');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $interval = (int)($_GET['interval'] ?? 30);
        if(!in_array($interval,[30,60],true)) $interval=30;
        $idPsico = (int)$_SESSION['usuario']['id'];
        // Validar formato fecha
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)){
            echo json_encode(['error'=>'formato_fecha']); return; }
        $hoy = new DateTime('today');
        $fReq = DateTime::createFromFormat('Y-m-d',$fecha);
        if($fReq < $hoy){ echo json_encode(['error'=>'fecha_pasada']); return; }
        // Obtener horarios configurados para ese día de semana
    require_once __DIR__ . '/../models/HorarioPsicologo.php';
    $diaSemanaMap = ['Mon'=>'lunes','Tue'=>'martes','Wed'=>'miércoles','Thu'=>'jueves','Fri'=>'viernes','Sat'=>'sábado','Sun'=>'domingo'];
    $diaBD = $diaSemanaMap[$fReq->format('D')] ?? 'lunes';
    $pdo = (new Cita())->pdo();
    $stH = $pdo->prepare("SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana=? ORDER BY hora_inicio");
    $stH->execute([$idPsico,$diaBD]);
    $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);
    if(!$bloques){ echo json_encode(['fecha'=>$fecha,'interval'=>$interval,'slots'=>[],'dia'=>$diaBD]); return; }
    $stC = $pdo->prepare("SELECT fecha_hora FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora)=? AND estado='activo'");
    $stC->execute([$idPsico,$fecha]);
    $ocupadas = array_map(fn($r)=>substr($r['fecha_hora'],11,5), $stC->fetchAll(PDO::FETCH_ASSOC));
        $slots=[]; $now = new DateTime(); $isToday = $fecha === $now->format('Y-m-d');
        foreach($bloques as $b){
            $ini = new DateTime($fecha.' '.$b['hora_inicio']);
            $fin = new DateTime($fecha.' '.$b['hora_fin']);
            while($ini < $fin){
                $h = $ini->format('H:i');
                if($ini < $fin && !in_array($h,$ocupadas,true) && (!$isToday || $ini > $now)){
                    $slots[] = $h;
                }
                $ini->modify('+'.$interval.' minutes');
            }
        }
        sort($slots);
        echo json_encode(['fecha'=>$fecha,'interval'=>$interval,'slots'=>$slots,'dia'=>$diaBD]);
    }

    public function pagar(): void {
        $this->requirePsicologo();
        $idCita = (int)($_POST['id_cita'] ?? 0);
        if(!$idCita){ header('Location: index.php?controller=Psicologo&action=citas&err=cita'); return; }
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);
        if(!$cita){ header('Location: index.php?controller=Psicologo&action=citas&err=nf'); return; }
        $idPsico = (int)$_SESSION['usuario']['id'];
        if((int)$cita['id_psicologo'] !== $idPsico){ header('Location: index.php?controller=Psicologo&action=citas&err=own'); return; }
        if($cita['estado_cita'] !== 'realizada'){ header('Location: index.php?controller=Psicologo&action=citas&err=estado'); return; }
        $pagoM = new Pago();
        $idPago = $pagoM->registrarPagoCita($idCita, 50.0);
        // Ticket (simple)
        $ticketM = new TicketPago();
        $ex = $ticketM->obtenerPorPago($idPago);
        if(!$ex){
            $codigo = strtoupper(substr(hash('sha256','pago'.$idPago.microtime()),0,10));
            $numero = $idPago; // simple correlativo
            try {
                $qrRuta = QRHelper::generarQR('PAGO:'.$idPago,'ticket','ticket_'.$idPago.'.png');
            } catch(Throwable $e){ $qrRuta=''; }
            $ticketM->crear([
                'id_pago'=>$idPago,
                'codigo'=>$codigo,
                'numero_ticket'=>$numero,
                'qr_code'=>$qrRuta
            ]);
        }
        header('Location: index.php?controller=Psicologo&action=citas&ok=pagado');
    }
}
