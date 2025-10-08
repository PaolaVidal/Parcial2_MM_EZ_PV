<?php
require_once __DIR__ . '/../Models/Cita.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Pago.php';
require_once __DIR__ . '/../Models/TicketPago.php';
require_once __DIR__ . '/../Models/Psicologo.php';
require_once __DIR__ . '/../Models/Evaluacion.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/../helpers/PDFHelper.php';
require_once __DIR__ . '/../helpers/ExcelHelper.php';

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
        
        // Datos básicos
        $hoy = date('Y-m-d');
        $pendHoy = $this->countEstadoDia($idPsico,'pendiente',$hoy);
        $realHoy = $this->countEstadoDia($idPsico,'realizada',$hoy);
        $cancelHoy = $this->countEstadoDia($idPsico,'cancelada',$hoy);
        $ingresos = $this->ingresosPsicologo($idPsico);
        
        // Estadísticas del mes
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');
        $citasMes = $this->countCitasRango($idPsico, $inicioMes, $finMes);
        $ingresosMes = $this->ingresosPsicologoRango($idPsico, $inicioMes, $finMes);
        
        // Próximos slots y citas pendientes
        $slots = $citaM->proximosSlots($idPsico,5);
        $proximasCitas = $this->proximasCitasPendientes($idPsico, 5);
        
        // Estadísticas por estado (últimos 30 días)
        $hace30 = date('Y-m-d', strtotime('-30 days'));
        $estadisticas = $this->estadisticasUltimos30Dias($idPsico);
        
        // Total de pacientes únicos atendidos
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        
        $this->render('psicologo/dashboard',[
            'pendHoy' => $pendHoy,
            'realHoy' => $realHoy,
            'cancelHoy' => $cancelHoy,
            'ingresos' => $ingresos,
            'citasMes' => $citasMes,
            'ingresosMes' => $ingresosMes,
            'slots' => $slots,
            'proximasCitas' => $proximasCitas,
            'estadisticas' => $estadisticas,
            'totalPacientes' => $totalPacientes
        ]);
    }

    public function estadisticas(): void {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        
        // Datos para gráficos
        $citasPorMes = $this->citasPorMes($idPsico, 12); // Últimos 12 meses
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);
        
        // Estadísticas generales
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);
        
        $this->render('psicologo/estadisticas', [
            'citasPorMes' => $citasPorMes,
            'citasPorEstado' => $citasPorEstado,
            'ingresosPorMes' => $ingresosPorMes,
            'pacientesFrecuentes' => $pacientesFrecuentes,
            'horariosPopulares' => $horariosPopulares,
            'tasaCancelacion' => $tasaCancelacion,
            'promedioIngresoDiario' => $promedioIngresoDiario,
            'totalCitas' => $totalCitas,
            'totalPacientes' => $totalPacientes,
            'ingresoTotal' => $ingresoTotal
        ]);
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
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
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

    // Nuevo: solo consulta (no confirma) - ahora retorna info para "atender"
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
                if(!$cita){ 
                    $res=['ok'=>false,'msg'=>'Cita no encontrada']; 
                } elseif((int)$cita['id_psicologo']!==$idPsico){ 
                    $res=['ok'=>false,'msg'=>'No pertenece a este psicólogo']; 
                } else { 
                    // Obtener nombre del paciente
                    $pacM = new Paciente();
                    $pac = $pacM->getById((int)$cita['id_paciente']);
                    $cita['nombre_paciente'] = $pac['nombre'] ?? 'Desconocido';
                    
                    $res=['ok'=>true,'cita'=>$cita,'msg'=>'Cita encontrada'];
                }
            }
        }
        header('Content-Type: application/json'); 
        echo json_encode($res);
    }

    // Ya no se usa para confirmar desde scanner - solo si fuera necesario en otro contexto
    public function scanConfirmar(): void {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        $id = (int)($_POST['id'] ?? 0);
        $citaM = new Cita();
        $cita = $citaM->obtener($id);
        if(!$id || !$cita){ 
            $res=['ok'=>false,'msg'=>'Cita no encontrada']; 
        } elseif((int)$cita['id_psicologo']!==$idPsico){ 
            $res=['ok'=>false,'msg'=>'No pertenece a este psicólogo']; 
        } elseif($cita['estado_cita']==='realizada'){ 
            $res=['ok'=>true,'msg'=>'Ya estaba realizada','cita'=>$cita]; 
        } else {
            $citaM->marcarRealizada($id);
            $cita['estado_cita']='realizada';
            $res=['ok'=>true,'msg'=>'Cita confirmada','cita'=>$cita];
        }
        header('Content-Type: application/json'); 
        echo json_encode($res);
    }

    // Mostrar vista de atender cita (con evaluaciones)
    public function atenderCita(): void {
        $this->requirePsicologo();
        $idCita = (int)($_GET['id'] ?? 0);
        $idPsico = $this->currentPsicologoId();
        
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);
        
        if(!$cita || (int)$cita['id_psicologo'] !== $idPsico){
            header('Location: ' . RUTA . 'index.php?url=psicologo/citas&err=nf');
            return;
        }
        
        // Obtener paciente
        $pacM = new Paciente();
        $paciente = $pacM->getById((int)$cita['id_paciente']);
        
        // Obtener evaluaciones existentes
        $evalM = new Evaluacion();
        $evaluaciones = $evalM->obtenerPorCita($idCita);
        
        // Determinar si se puede editar (solo si no está realizada o cancelada)
        $puedeEditar = !in_array($cita['estado_cita'], ['realizada', 'cancelada']);
        
        $this->render('psicologo/atender_cita', [
            'cita' => $cita,
            'paciente' => $paciente,
            'evaluaciones' => $evaluaciones,
            'puedeEditar' => $puedeEditar
        ]);
    }

    // Guardar evaluación (AJAX)
    public function guardarEvaluacion(): void {
        $this->requirePsicologo();
        header('Content-Type: application/json');
        
        $idCita = (int)($_POST['id_cita'] ?? 0);
        $estadoEmocional = (int)($_POST['estado_emocional'] ?? 0);
        $comentarios = trim($_POST['comentarios'] ?? '');
        
        $idPsico = $this->currentPsicologoId();
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);
        
        if(!$cita || (int)$cita['id_psicologo'] !== $idPsico){
            echo json_encode(['ok'=>false,'msg'=>'Cita no válida']);
            return;
        }
        
        if(in_array($cita['estado_cita'], ['realizada', 'cancelada'])){
            echo json_encode(['ok'=>false,'msg'=>'No se puede agregar evaluaciones a citas finalizadas']);
            return;
        }
        
        if($estadoEmocional < 1 || $estadoEmocional > 10){
            echo json_encode(['ok'=>false,'msg'=>'Estado emocional debe estar entre 1 y 10']);
            return;
        }
        
        $evalM = new Evaluacion();
        $idEval = $evalM->crear($idCita, $estadoEmocional, $comentarios);
        
        if($idEval){
            $evaluacion = $evalM->obtener($idEval);
            echo json_encode(['ok'=>true,'msg'=>'Evaluación guardada','evaluacion'=>$evaluacion]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'Error al guardar']);
        }
    }

    // Finalizar cita (marcarla como realizada)
    public function finalizarCita(): void {
        $this->requirePsicologo();
        $idCita = (int)($_POST['id_cita'] ?? 0);
        $idPsico = $this->currentPsicologoId();
        
        $citaM = new Cita();
        $cita = $citaM->obtener($idCita);
        
        if(!$cita || (int)$cita['id_psicologo'] !== $idPsico){
            header('Location: ' . RUTA . 'index.php?url=psicologo/citas&err=nf');
            return;
        }
        
        if($cita['estado_cita'] === 'realizada'){
            header('Location: ' . RUTA . 'index.php?url=psicologo/atenderCita&id='.$idCita.'&msg=ya_realizada');
            return;
        }
        
        // Verificar que tenga al menos una evaluación
        $evalM = new Evaluacion();
        $countEval = $evalM->contarPorCita($idCita);
        
        if($countEval === 0){
            header('Location: ' . RUTA . 'index.php?url=psicologo/atenderCita&id='.$idCita.'&err=sin_eval');
            return;
        }
        
        // Marcar como realizada
        if($citaM->marcarRealizada($idCita)){
            header('Location: ' . RUTA . 'index.php?url=psicologo/citas&ok=finalizada');
        } else {
            header('Location: ' . RUTA . 'index.php?url=psicologo/atenderCita&id='.$idCita.'&err=update');
        }
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
    require_once __DIR__ . '/../Models/HorarioPsicologo.php';
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
                $qrRuta = QRHelper::generarQR('PAGO:'.$idPago,'ticket','ticket_'.$idPago);
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

    // ============================================
    // MÉTODOS AUXILIARES PARA ESTADÍSTICAS
    // ============================================

    private function countCitasRango(int $idPsico, string $inicio, string $fin): int {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora) BETWEEN ? AND ? AND estado='activo'");
        $st->execute([$idPsico, $inicio, $fin]);
        return (int)$st->fetchColumn();
    }

    private function ingresosPsicologoRango(int $idPsico, string $inicio, string $fin): float {
        $db = (new Pago())->pdo();
        $st = $db->prepare("SELECT COALESCE(SUM(p.monto_total),0) FROM Pago p JOIN Cita c ON c.id=p.id_cita WHERE c.id_psicologo=? AND p.estado_pago='pagado' AND DATE(c.fecha_hora) BETWEEN ? AND ?");
        $st->execute([$idPsico, $inicio, $fin]);
        return (float)$st->fetchColumn();
    }

    private function proximasCitasPendientes(int $idPsico, int $limit): array {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT c.*, p.nombre as paciente_nombre FROM Cita c LEFT JOIN Paciente p ON p.id=c.id_paciente WHERE c.id_psicologo=? AND c.estado_cita='pendiente' AND DATE(c.fecha_hora) >= CURDATE() AND c.estado='activo' ORDER BY c.fecha_hora ASC LIMIT ?");
        $st->bindValue(1, $idPsico, PDO::PARAM_INT);
        $st->bindValue(2, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function estadisticasUltimos30Dias(int $idPsico): array {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT estado_cita, COUNT(*) as total FROM Cita WHERE id_psicologo=? AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND estado='activo' GROUP BY estado_cita");
        $st->execute([$idPsico]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function contarPacientesUnicos(int $idPsico): int {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(DISTINCT id_paciente) FROM Cita WHERE id_psicologo=? AND estado='activo'");
        $st->execute([$idPsico]);
        return (int)$st->fetchColumn();
    }

    private function citasPorMes(int $idPsico, int $meses): array {
        $db = (new Cita())->pdo();
        $st = $db->prepare("
            SELECT DATE_FORMAT(fecha_hora, '%Y-%m') as mes, COUNT(*) as total 
            FROM Cita 
            WHERE id_psicologo=? AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? MONTH) AND estado='activo'
            GROUP BY mes 
            ORDER BY mes ASC
        ");
        $st->execute([$idPsico, $meses]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function citasPorEstado(int $idPsico): array {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT estado_cita, COUNT(*) as total FROM Cita WHERE id_psicologo=? AND estado='activo' GROUP BY estado_cita");
        $st->execute([$idPsico]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ingresosPorMes(int $idPsico, int $meses): array {
        $db = (new Pago())->pdo();
        $st = $db->prepare("
            SELECT DATE_FORMAT(c.fecha_hora, '%Y-%m') as mes, SUM(p.monto_total) as total 
            FROM Pago p 
            JOIN Cita c ON c.id=p.id_cita 
            WHERE c.id_psicologo=? AND p.estado_pago='pagado' AND c.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY mes 
            ORDER BY mes ASC
        ");
        $st->execute([$idPsico, $meses]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function pacientesMasFrecuentes(int $idPsico, int $limit): array {
        $db = (new Cita())->pdo();
        $st = $db->prepare("
            SELECT p.nombre, p.dui, COUNT(*) as total_citas 
            FROM Cita c 
            JOIN Paciente p ON p.id=c.id_paciente 
            WHERE c.id_psicologo=? AND c.estado='activo'
            GROUP BY c.id_paciente 
            ORDER BY total_citas DESC 
            LIMIT ?
        ");
        $st->bindValue(1, $idPsico, PDO::PARAM_INT);
        $st->bindValue(2, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function horariosPopulares(int $idPsico): array {
        $db = (new Cita())->pdo();
        $st = $db->prepare("
            SELECT HOUR(fecha_hora) as hora, COUNT(*) as total 
            FROM Cita 
            WHERE id_psicologo=? AND estado='activo' AND estado_cita='realizada'
            GROUP BY hora 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $st->execute([$idPsico]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tasaCancelacion(int $idPsico): float {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND estado='activo'");
        $st->execute([$idPsico]);
        $total = (int)$st->fetchColumn();
        
        if($total === 0) return 0.0;
        
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND estado_cita='cancelada' AND estado='activo'");
        $st->execute([$idPsico]);
        $canceladas = (int)$st->fetchColumn();
        
        return ($canceladas / $total) * 100;
    }

    private function promedioIngresoDiario(int $idPsico): float {
        $db = (new Pago())->pdo();
        $st = $db->prepare("
            SELECT DATEDIFF(MAX(c.fecha_hora), MIN(c.fecha_hora)) as dias 
            FROM Pago p 
            JOIN Cita c ON c.id=p.id_cita 
            WHERE c.id_psicologo=? AND p.estado_pago='pagado'
        ");
        $st->execute([$idPsico]);
        $dias = (int)$st->fetchColumn();
        
        if($dias === 0) $dias = 1;
        
        $ingresos = $this->ingresosPsicologo($idPsico);
        return $ingresos / $dias;
    }

    private function countCitasTotal(int $idPsico): int {
        $db = (new Cita())->pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND estado='activo'");
        $st->execute([$idPsico]);
        return (int)$st->fetchColumn();
    }

    public function exportarEstadisticasExcel(): void {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        
        require_once __DIR__ . '/../helpers/ExcelHelper.php';
        
        // Obtener todos los datos
        $citasPorMes = $this->citasPorMes($idPsico, 12);
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);
        
        // Preparar las secciones del reporte
        $sheets = [];
        
        // 1. Resumen General
        $sheets['RESUMEN GENERAL'] = [
            'headers' => ['Métrica', 'Valor'],
            'data' => [
                ['Total de Citas', $totalCitas],
                ['Pacientes Únicos', $totalPacientes],
                ['Ingresos Totales', '$' . number_format($ingresoTotal, 2)],
                ['Promedio Ingreso Diario', '$' . number_format($promedioIngresoDiario, 2)],
                ['Tasa de Cancelación', number_format($tasaCancelacion, 2) . '%']
            ]
        ];
        
        // 2. Citas por Mes
        $dataCitasMes = [];
        foreach($citasPorMes as $row) {
            $dataCitasMes[] = [$row['mes'], $row['total']];
        }
        $sheets['CITAS POR MES (Últimos 12 meses)'] = [
            'headers' => ['Mes', 'Total de Citas'],
            'data' => $dataCitasMes
        ];
        
        // 3. Citas por Estado
        $dataCitasEstado = [];
        foreach($citasPorEstado as $row) {
            $dataCitasEstado[] = [ucfirst($row['estado_cita']), $row['total']];
        }
        $sheets['CITAS POR ESTADO'] = [
            'headers' => ['Estado', 'Total'],
            'data' => $dataCitasEstado
        ];
        
        // 4. Ingresos por Mes
        $dataIngresos = [];
        foreach($ingresosPorMes as $row) {
            $dataIngresos[] = [$row['mes'], '$' . number_format($row['total'], 2)];
        }
        $sheets['INGRESOS POR MES'] = [
            'headers' => ['Mes', 'Ingresos'],
            'data' => $dataIngresos
        ];
        
        // 5. Top 10 Pacientes Frecuentes
        $dataPacientes = [];
        $pos = 1;
        foreach($pacientesFrecuentes as $pac) {
            $nombre = $pac['nombre'] ?: 'Paciente #' . $pac['id_paciente'];
            $dataPacientes[] = [$pos++, $nombre, $pac['total_citas']];
        }
        $sheets['TOP 10 PACIENTES FRECUENTES'] = [
            'headers' => ['#', 'Nombre del Paciente', 'Total de Citas'],
            'data' => $dataPacientes
        ];
        
        // 6. Horarios Más Populares
        if (!empty($horariosPopulares)) {
            $dataHorarios = [];
            foreach($horariosPopulares as $h) {
                $dataHorarios[] = [$h['hora'] . ':00', $h['total']];
            }
            $sheets['HORARIOS MÁS SOLICITADOS'] = [
                'headers' => ['Hora', 'Citas Agendadas'],
                'data' => $dataHorarios
            ];
        }
        
        // Nombre del archivo con fecha
        $filename = 'Estadisticas_Psicologo_' . date('Y-m-d_His');
        
        // Intentar exportar a Excel XLSX, si falla usará CSV automáticamente
        ExcelHelper::exportarMultiplesHojas($sheets, $filename, 'Estadísticas Psicólogo');
    }

    public function exportarEstadisticasPDF(): void {
        $this->requirePsicologo();
        $idPsico = $this->currentPsicologoId();
        
        require_once __DIR__ . '/../helpers/PDFHelper.php';
        
        // Obtener todos los datos
        $citasPorMes = $this->citasPorMes($idPsico, 12);
        $citasPorEstado = $this->citasPorEstado($idPsico);
        $ingresosPorMes = $this->ingresosPorMes($idPsico, 12);
        $pacientesFrecuentes = $this->pacientesMasFrecuentes($idPsico, 10);
        $horariosPopulares = $this->horariosPopulares($idPsico);
        $tasaCancelacion = $this->tasaCancelacion($idPsico);
        $promedioIngresoDiario = $this->promedioIngresoDiario($idPsico);
        $totalCitas = $this->countCitasTotal($idPsico);
        $totalPacientes = $this->contarPacientesUnicos($idPsico);
        $ingresoTotal = $this->ingresosPsicologo($idPsico);
        
        // Obtener nombre del psicólogo
        $psico = new Psicologo();
        $dataPsico = $psico->get($idPsico);
        $nombrePsico = $dataPsico['nombre'] ?? 'Psicólogo';
        
        // Generar HTML para el PDF (HTML 4.01 para evitar parser HTML5)
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; }
                h1 { color: #2c3e50; text-align: center; font-size: 18px; margin-bottom: 5px; }
                h2 { color: #34495e; font-size: 14px; margin-top: 15px; margin-bottom: 8px; border-bottom: 2px solid #3498db; padding-bottom: 3px; }
                .fecha { text-align: center; color: #7f8c8d; font-size: 10px; margin-bottom: 15px; }
                .resumen { background: #ecf0f1; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
                .resumen-item { display: inline-block; width: 48%; margin-bottom: 8px; }
                .resumen-label { font-weight: bold; color: #2c3e50; }
                .resumen-valor { color: #2980b9; font-size: 13px; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
                th { background: #3498db; color: white; padding: 6px; text-align: left; font-size: 10px; }
                td { padding: 5px; border-bottom: 1px solid #ddd; }
                tr:nth-child(even) { background: #f8f9fa; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .badge { padding: 3px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
                .badge-success { background: #27ae60; color: white; }
                .badge-warning { background: #f39c12; color: white; }
                .badge-danger { background: #e74c3c; color: white; }
                .page-break { page-break-after: always; }
            </style>
        </head>
        <body>
            <h1>Reporte de Estadísticas - ' . htmlspecialchars($nombrePsico) . '</h1>
            <div class="fecha">Generado el ' . date('d/m/Y H:i:s') . '</div>
            
            <div class="resumen">
                <h2>Resumen General</h2>
                <div class="resumen-item">
                    <span class="resumen-label">Total de Citas:</span><br>
                    <span class="resumen-valor">' . $totalCitas . '</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Pacientes Únicos:</span><br>
                    <span class="resumen-valor">' . $totalPacientes . '</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Ingresos Totales:</span><br>
                    <span class="resumen-valor">$' . number_format($ingresoTotal, 2) . '</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Promedio Diario:</span><br>
                    <span class="resumen-valor">$' . number_format($promedioIngresoDiario, 2) . '</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Tasa de Cancelación:</span><br>
                    <span class="resumen-valor ' . ($tasaCancelacion > 20 ? 'text-danger' : '') . '">' . number_format($tasaCancelacion, 2) . '%</span>
                </div>
            </div>
            
            <h2>Citas por Mes (Últimos 12 meses)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-center">Total de Citas</th>
                    </tr>
                </thead>
                <tbody>';
                
        foreach($citasPorMes as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['mes']) . '</td>
                <td class="text-center">' . $row['total'] . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <h2>Distribución de Citas por Estado</h2>
            <table>
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>';
                
        foreach($citasPorEstado as $row) {
            $porcentaje = $totalCitas > 0 ? ($row['total'] / $totalCitas) * 100 : 0;
            $badgeClass = 'badge';
            if($row['estado_cita'] === 'realizada') $badgeClass .= ' badge-success';
            elseif($row['estado_cita'] === 'pendiente') $badgeClass .= ' badge-warning';
            elseif($row['estado_cita'] === 'cancelada') $badgeClass .= ' badge-danger';
            
            $html .= '<tr>
                <td><span class="' . $badgeClass . '">' . ucfirst($row['estado_cita']) . '</span></td>
                <td class="text-center">' . $row['total'] . '</td>
                <td class="text-center">' . number_format($porcentaje, 1) . '%</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="page-break"></div>
            
            <h2>Ingresos por Mes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-right">Ingresos</th>
                    </tr>
                </thead>
                <tbody>';
                
        foreach($ingresosPorMes as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['mes']) . '</td>
                <td class="text-right">$' . number_format($row['total'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <h2>Top 10 Pacientes Más Frecuentes</h2>
            <table>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Nombre del Paciente</th>
                        <th class="text-center">Total de Citas</th>
                    </tr>
                </thead>
                <tbody>';
                
        $pos = 1;
        foreach($pacientesFrecuentes as $pac) {
            $nombre = $pac['nombre'] ?: 'Paciente #' . $pac['id_paciente'];
            $html .= '<tr>
                <td class="text-center">' . $pos++ . '</td>
                <td>' . htmlspecialchars($nombre) . '</td>
                <td class="text-center">' . $pac['total_citas'] . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>';
            
        if (!empty($horariosPopulares)) {
            $html .= '
            <h2>Horarios Más Solicitados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th class="text-center">Citas Agendadas</th>
                    </tr>
                </thead>
                <tbody>';
                
            foreach($horariosPopulares as $h) {
                $html .= '<tr>
                    <td>' . $h['hora'] . ':00</td>
                    <td class="text-center">' . $h['total'] . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
            </table>';
        }
        
        $html .= '
        </body>
        </html>';
        
        // Generar PDF (PDFHelper agrega .pdf automáticamente)
        $filename = 'Estadisticas_' . date('Y-m-d_His');
        PDFHelper::generarPDF($html, $filename, true);
    }
}
