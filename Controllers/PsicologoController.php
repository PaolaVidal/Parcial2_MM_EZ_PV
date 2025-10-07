<?php
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/TicketPago.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class PsicologoController {

    /** Obtiene el id real del psicólogo (tabla Psicologo.id) a partir del usuario logueado.
     *  Cachea sólo si encuentra un id válido (>0). */
    private function currentPsicologoId(): int {
        $this->requirePsicologo();
        if(isset($_SESSION['psicologo_id']) && (int)$_SESSION['psicologo_id'] > 0){
            return (int)$_SESSION['psicologo_id'];
        }
        $idUsuario = (int)$_SESSION['usuario']['id'];
        $modelo = new Psicologo();
        $row = $modelo->obtenerPorUsuario($idUsuario);
        if($row){
            $_SESSION['psicologo_id'] = (int)$row['id'];
            return (int)$row['id'];
        }
        // Si no existe registro en Psicologo, devolver 0 (esto causará error en slots/crear) y dejar pista
        $_SESSION['diag_psicologo_id'] = 'No hay fila en Psicologo para id_usuario='.$idUsuario;
        return 0;
    }

    private function requirePsicologo(): void {
        if(!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'psicologo'){
            header('Location: index.php?url=auth/login');
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
    $idPsico = $this->currentPsicologoId();
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
    $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        $pacM = new Paciente();
    $list = $citaM->listarPsicologo($idPsico);
    // Unificar y ordenar por fecha_hora ASC manteniendo estado_cita original
    $todas = array_merge($list['pendientes'],$list['realizadas']);
    usort($todas, function($a,$b){ return strcmp($a['fecha_hora'],$b['fecha_hora']); });
    // Repartir de nuevo en pendientes/realizadas ya ordenadas si se necesita en otras partes
    $list['pendientes'] = array_values(array_filter($todas, fn($c)=>$c['estado_cita']==='pendiente'));
    $list['realizadas'] = array_values(array_filter($todas, fn($c)=>$c['estado_cita']==='realizada'));
    $pacientes = $pacM->listarTodos();
    $this->render('psicologo/citas',[ 'data'=>$list,'pacientes'=>$pacientes ]);
    }

    public function crear(): void {
        $this->requirePsicologo();
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){ header('Location: index.php?url=psicologo/citas'); return; }
    $idPsico = $this->currentPsicologoId();
    if($idPsico <= 0){ header('Location: index.php?url=psicologo/citas&err=psico'); return; }
        $idPaciente = (int)($_POST['id_paciente'] ?? 0);
        $fecha = trim($_POST['fecha_hora'] ?? '');
        $motivo = trim($_POST['motivo_consulta'] ?? '');
        if(strlen($motivo) > 255) $motivo = substr($motivo,0,255);
    if(!$idPaciente || !$fecha){ header('Location: index.php?url=psicologo/citas&err=datos'); return; }
        // Validar formato fecha: Y-m-d H:i:s o Y-m-d H:i
        $fh = DateTime::createFromFormat('Y-m-d H:i:s',$fecha) ?: DateTime::createFromFormat('Y-m-d H:i',$fecha);
    if(!$fh){ header('Location: index.php?url=psicologo/citas&err=formato'); return; }
        // Normalizar a segundos :00
        $fh->setTime((int)$fh->format('H'), (int)$fh->format('i'), 0);
        // Validar minutos 00 o 30
        $min = (int)$fh->format('i');
    if($min !== 0 && $min !== 30){ header('Location: index.php?url=psicologo/citas&err=minutos'); return; }
        // No permitir pasado
        $now = new DateTime();
    if($fh <= $now){ header('Location: index.php?url=psicologo/citas&err=pasado'); return; }
        // Validar contra horarios configurados en Horario_Psicologo
        require_once __DIR__ . '/../models/HorarioPsicologo.php';
        $diaSemanaMap = ['Mon'=>'lunes','Tue'=>'martes','Wed'=>'miércoles','Thu'=>'jueves','Fri'=>'viernes','Sat'=>'sábado','Sun'=>'domingo'];
        $diaBD = $diaSemanaMap[$fh->format('D')] ?? 'lunes';
        // Variantes sin acento para compatibilidad con datos existentes (miercoles/sabado)
        $variants = [$diaBD];
        if($diaBD === 'miércoles') $variants[] = 'miercoles';
        if($diaBD === 'sábado') $variants[] = 'sabado';
        $placeholders = implode(',', array_fill(0,count($variants),'?'));
        $dbChk2 = (new Cita())->pdo();
        $sqlHor = "SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana IN ($placeholders)";
        $stH = $dbChk2->prepare($sqlHor);
        $params = array_merge([$idPsico], $variants);
        $stH->execute($params);
        $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);
        $enRango = false;
        foreach($bloques as $b){
            if($fh->format('H:i:s') >= $b['hora_inicio'] && $fh->format('H:i:s') < $b['hora_fin']){ $enRango = true; break; }
        }
        if(!$enRango){ header('Location: index.php?url=psicologo/citas&err=fuera_horario'); return; }
        $fecha = $fh->format('Y-m-d H:i:s');
        // Evitar choque con una existente misma fecha_hora para ese psicólogo
        $dbChk = (new Cita())->pdo();
        $stChk = $dbChk->prepare('SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND fecha_hora=? AND estado=\'activo\'' );
        $stChk->execute([$idPsico,$fecha]);
        if((int)$stChk->fetchColumn() > 0){ header('Location: index.php?url=psicologo/citas&err=ocupado'); return; }
        // Verificar paciente existe
        $pacM = new Paciente();
        $pac = $pacM->getById($idPaciente);
    if(!$pac){ header('Location: index.php?url=psicologo/citas&err=paciente'); return; }
        try {
            $citaM = new Cita();
            // Placeholder único para no violar UNIQUE(qr_code) (evitamos '')
            $placeholder = 'PEND_' . bin2hex(random_bytes(6));
            $id = $citaM->crear([
                'id_paciente'=>$idPaciente,
                'id_psicologo'=>$idPsico,
                'fecha_hora'=>$fecha,
                'motivo_consulta'=>$motivo,
                'qr_code'=>$placeholder
            ]);
            // Generar QR usando el id y guardar la RUTA final (qrcodes/cita_id_ID.png)
            $final = null;
            try {
                // Pasamos nombre sin .png porque el helper lo añade si falta
                $final = QRHelper::generarQR('CITA:'.$id,'cita','cita_id_'.$id);
            } catch(Throwable $e){ /* si falla dejamos placeholder */ }
            $pdo = (new Cita())->pdo();
            // Si QR ok guardamos ruta, si no al menos guardamos el id para mantener único
            $valorQR = $final ?: (string)$id;
            $pdo->prepare("UPDATE Cita SET qr_code=? WHERE id=?")->execute([$valorQR,$id]);
            header('Location: index.php?url=psicologo/citas&ok=1');
            return;
        } catch(Throwable $e){
            // Guardar mensaje detallado temporal (no en producción)
            $_SESSION['crear_cita_error'] = $e->getMessage();
            header('Location: index.php?url=psicologo/citas&err=ex');
            return;
        }
    }

    public function scan(): void {
        $this->requirePsicologo();
        $this->render('psicologo/scan');
    }

    public function scanProcesar(): void {
        $this->requirePsicologo();
        $rawInput = trim($_POST['token'] ?? '');
    $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        // Nueva política: solo aceptar formato QR oficial CITA:<id>
        if(stripos($rawInput,'CITA:')!==0){
            $res=['ok'=>false,'msg'=>'Formato inválido. Escanee un QR válido (CITA:<id>).'];
        } else {
            $num = substr($rawInput,5);
            if(!ctype_digit($num)){
                $res=['ok'=>false,'msg'=>'ID inválido en el QR'];
            } else {
                $id = (int)$num;
                $cita = $citaM->obtener($id);
                if(!$cita){ $res=['ok'=>false,'msg'=>'Cita no encontrada']; }
                elseif((int)$cita['id_psicologo']!==$idPsico){ $res=['ok'=>false,'msg'=>'No pertenece a este psicólogo']; }
                else {
                    if($cita['estado_cita']==='pendiente') $citaM->marcarRealizada($id);
                    $cita['estado_cita']='realizada';
                    $res=['ok'=>true,'msg'=>'Cita confirmada','cita'=>$cita];
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    // Nuevo: solo consulta (no confirma)
    public function scanConsultar(): void {
        $this->requirePsicologo();
        $rawInput = trim($_POST['token'] ?? '');
    $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        if(stripos($rawInput,'CITA:')!==0){
            $res=['ok'=>false,'msg'=>'Formato inválido. Use CITA:<id>'];
        } else {
            $num = substr($rawInput,5);
            if(!ctype_digit($num)){
                $res=['ok'=>false,'msg'=>'ID inválido'];
            } else {
                $id = (int)$num;
                $cita = $citaM->obtener($id);
                if(!$cita){ $res=['ok'=>false,'msg'=>'Cita no encontrada']; }
                elseif((int)$cita['id_psicologo']!==$idPsico){ $res=['ok'=>false,'msg'=>'No pertenece a este psicólogo']; }
                else { $res=['ok'=>true,'cita'=>$cita]; }
            }
        }
        header('Content-Type: application/json'); echo json_encode($res);
    }

    // Nuevo: confirmar asistencia explícita
    public function scanConfirmar(): void {
        $this->requirePsicologo();
    $idPsico = $this->currentPsicologoId();
        $id = (int)($_POST['id'] ?? 0);
        $citaM = new Cita();
        $cita = $citaM->obtener($id);
        if(!$id || !$cita){ $res=['ok'=>false,'msg'=>'Cita no encontrada']; }
        elseif((int)$cita['id_psicologo']!==$idPsico){ $res=['ok'=>false,'msg'=>'No pertenece a este psicólogo']; }
        elseif($cita['estado_cita']==='realizada'){ $res=['ok'=>true,'msg'=>'Ya estaba realizada','cita'=>$cita]; }
        else {
            $citaM->marcarRealizada($id);
            $cita['estado_cita']='realizada';
            $res=['ok'=>true,'msg'=>'Cita confirmada','cita'=>$cita];
        }
        header('Content-Type: application/json'); echo json_encode($res);
    }

    /** Devuelve slots disponibles (intervalo 30 o 60) para una fecha dada */
    public function slots(): void {
        $this->requirePsicologo();
        header('Content-Type: application/json');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $interval = (int)($_GET['interval'] ?? 30);
        if(!in_array($interval,[30,60],true)) $interval=30;
    $idPsico = $this->currentPsicologoId();
    if($idPsico <= 0){ echo json_encode(['error'=>'psicologo_no_mapeado','diag'=>($_SESSION['diag_psicologo_id']??'')]); return; }
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
        $ocupadasReg = $stC->fetchAll(PDO::FETCH_ASSOC);
        $ocupadas = [];
        foreach($ocupadasReg as $r){
            $hm = substr($r['fecha_hora'],11,5);
            $ocupadas[$hm]=true; // marca exacto
        }
        $slots=[]; $now = new DateTime(); $isToday = $fecha === $now->format('Y-m-d');
        foreach($bloques as $b){
            $ini = new DateTime($fecha.' '.$b['hora_inicio']);
            $fin = new DateTime($fecha.' '.$b['hora_fin']);
            while($ini < $fin){
                $h = $ini->format('H:i');
                if($ini < $fin && !$this->slotOcupado($h,$interval,$ocupadas) && (!$isToday || $ini > $now)){
                    $slots[] = $h;
                }
                $ini->modify('+'.$interval.' minutes');
            }
        }
        sort($slots);
        echo json_encode(['fecha'=>$fecha,'interval'=>$interval,'slots'=>$slots,'dia'=>$diaBD]);
    }

    private function slotOcupado(string $h, int $interval, array $ocupadas): bool {
        if(isset($ocupadas[$h])) return true; // misma hora exacta ya ocupada
        if($interval===60){
            // si hay intervalo de 60, considerar que cita ocupada a mitad o inicio bloquea toda la hora
            // Revisar también h+30
            [$H,$M] = explode(':',$h);
            $h30 = sprintf('%02d:%02d',(int)$H, (int)$M+30>=60?( (int)$H+1)%24 : (int)$M+30);
            if(isset($ocupadas[$h30])) return true;
        }
        return false;
    }

    public function pagar(): void {
        $this->requirePsicologo();
        $idCita = (int)($_POST['id_cita'] ?? 0);
    if(!$idCita){ header('Location: index.php?url=psicologo/citas&err=cita'); return; }
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);
    if(!$cita){ header('Location: index.php?url=psicologo/citas&err=nf'); return; }
    $idPsico = $this->currentPsicologoId();
    if($idPsico <= 0){ header('Location: index.php?url=psicologo/citas&err=psico'); return; }
    if((int)$cita['id_psicologo'] !== $idPsico){ header('Location: index.php?url=psicologo/citas&err=own'); return; }
    if($cita['estado_cita'] !== 'realizada'){ header('Location: index.php?url=psicologo/citas&err=estado'); return; }
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
        header('Location: index.php?url=psicologo/citas&ok=pagado');
    }
}
