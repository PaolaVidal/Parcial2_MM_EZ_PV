<?php
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Psicologo.php';
require_once __DIR__ . '/../Models/Cita.php';
require_once __DIR__ . '/../Models/Pago.php';
require_once __DIR__ . '/../Models/HorarioPsicologo.php';
require_once __DIR__ . '/../Models/SolicitudCambio.php';

class AdminController {
    private string $viewsPath;

    public function __construct(){
        $this->viewsPath = __DIR__ . '/../Views/admin/';
    }

    private function requireAdmin(){
        if(!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol']??'')!=='admin'){
            http_response_code(403); echo '<div class="alert alert-danger">Acceso denegado</div>'; exit;
        }
    }

    private function render(string $vista, array $data=[]){
        $file = $this->viewsPath.$vista.'.php';
        if(!file_exists($file)){ echo '<div class="alert alert-danger">Vista admin faltante: '.htmlspecialchars($vista).'</div>'; return; }
        extract($data); require $file;
    }

    public function dashboard(): void {
        $this->requireAdmin();
        $usuariosCounts = (new Usuario())->conteoActivosInactivos();
        $citaStats = (new Cita())->estadisticasEstado();
        $this->render('dashboard',[ 'usuariosCounts'=>$usuariosCounts, 'citaStats'=>$citaStats ]);
    }

    /* ================= Usuarios ================= */
    public function usuarios(): void {
        $this->requireAdmin();
        $usuarioModel = new Usuario();
        $msg='';
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $accion = $_POST['accion'] ?? '';
            if($accion==='crear'){
                $nombre=trim($_POST['nombre']??'');
                $email=trim($_POST['email']??'');
                $rol=$_POST['rol']??'paciente';
                $pass=$_POST['password']??'';
                if($nombre && $email && $pass){
                    $usuarioModel->crear(['nombre'=>$nombre,'email'=>$email,'password'=>$pass,'rol'=>$rol]);
                } else { $msg='Datos incompletos'; }
            }
            if($accion==='estado'){
                $id=(int)($_POST['id']??0); $estado=$_POST['estado']??''; $usuarioModel->cambiarEstado($id,$estado);
            }
            if($accion==='reset'){ $id=(int)($_POST['id']??0); $usuarioModel->resetPassword($id,'Temp1234'); }
        }
        $usuarios = $usuarioModel->listarTodos();
        $this->render('usuarios',[ 'usuarios'=>$usuarios,'msg'=>$msg ]);
    }

    /* ================ Psicologos ================ */
    public function psicologos(): void {
        $this->requireAdmin();
        $psModel  = new Psicologo();
        $usrModel = new Usuario();
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $accion = $_POST['accion'] ?? '';
            try {
                if($accion==='crear'){
                    $idU = $usrModel->crear([
                        'nombre'=>trim($_POST['nombre']),
                        'email'=>trim($_POST['email']),
                        'password'=>$_POST['password'],
                        'rol'=>'psicologo'
                    ]);
                    $psModel->crear($idU,[
                        'especialidad'=>trim($_POST['especialidad']??''),
                        'experiencia'=>trim($_POST['experiencia']??''),
                        'horario'=>trim($_POST['horario']??'')
                    ]);
                } elseif($accion==='editar'){
                    $idPs = (int)$_POST['id'];
                    $idU  = (int)$_POST['id_usuario'];
                    $usrModel->actualizar($idU,[
                        'nombre'=>trim($_POST['nombre']),
                        'email'=>trim($_POST['email'])
                    ]);
                    if(($np=trim($_POST['new_password']??''))!==''){
                        $usrModel->actualizarPassword($idU,$np);
                    }
                    $psModel->actualizar($idPs,[
                        'especialidad'=>trim($_POST['especialidad']??''),
                        'experiencia'=>trim($_POST['experiencia']??''),
                        'horario'=>trim($_POST['horario']??'')
                    ]);
                } elseif($accion==='estado'){
                    $usrModel->cambiarEstado((int)$_POST['id_usuario'], $_POST['estado']);
                }
            } catch(Exception $e){
                $_SESSION['flash_error']=$e->getMessage();
            }
            header('Location: '.url('admin','psicologos')); exit;
        }
        $psicologos = $psModel->listarTodos();
        $masSolic = method_exists($psModel,'masSolicitados')?$psModel->masSolicitados():[];
        $this->render('psicologos',[
            'psicologos'=>$psicologos,
            'masSolic'=>$masSolic,
            'error'=>$_SESSION['flash_error']??''
        ]);
        unset($_SESSION['flash_error']);
    }

    public function pacientes(): void {
        $this->requireAdmin();
        $pacModel = new Paciente();
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $accion = $_POST['accion'] ?? '';
            // Normaliza teléfono y DUI
            $tel = isset($_POST['telefono']) ? preg_replace('/\D/','', $_POST['telefono']) : '';
            if(strlen($tel)===8) $tel = substr($tel,0,4).'-'.substr($tel,4);
            $dui = isset($_POST['dui']) ? preg_replace('/\D/','', $_POST['dui']) : '';
            if(strlen($dui)===7) $dui = substr($dui,0,6).'-'.substr($dui,6);
            $fecha = $_POST['fecha_nacimiento'] ?? '';
            if($fecha){
                $hoy = date('Y-m-d');
                if($fecha > $hoy) $fecha = $hoy;
                if($fecha < '1900-01-01') $fecha = '1900-01-01';
            }
            $dataBase = [
                'nombre'=>trim($_POST['nombre'] ?? ''),
                'email'=>trim($_POST['email'] ?? ''),
                'telefono'=>$tel,
                'direccion'=>trim($_POST['direccion'] ?? ''),
                'dui'=>$dui,
                'fecha_nacimiento'=>$fecha,
                'genero'=>$_POST['genero'] ?? '',
                'historial_clinico'=>trim($_POST['historial_clinico'] ?? '')
            ];
            if($accion==='crear'){
                $pacModel->crear($dataBase);
            } elseif($accion==='editar'){
                $pacModel->actualizar((int)$_POST['id'],$dataBase);
            } elseif($accion==='estado'){
                $pacModel->cambiarEstado((int)$_POST['id'], $_POST['estado']);
            } elseif($accion==='regen_code'){
                $pacModel->regenerarCodigoAcceso((int)$_POST['id']);
            }
            header('Location: '.url('admin','pacientes')); exit;
        }
        $lista = $pacModel->listarTodos();
        $this->render('pacientes',['pacientes'=>$lista]);
    }

    /* ================== Citas =================== */
    public function citas(): void {
        $this->requireAdmin();
        $citaModel = new Cita();
        $psModel   = new Psicologo();
        // JSON fetch (AJAX list)
        if(isset($_GET['ajax']) && $_GET['ajax']==='list'){
            header('Content-Type: application/json');
            echo json_encode(['citas'=>$this->filtrarCitasAdmin($citaModel)]);
            return;
        }
        // AJAX slots para un psicólogo destino en fecha dada (para reasignar)
        if(isset($_GET['ajax']) && $_GET['ajax']==='slots'){
            ob_clean(); // Limpiar cualquier salida previa
            header('Content-Type: application/json');
            try {
                $idPs = (int)($_GET['ps'] ?? 0);
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $interval = 30; // fijo
                $resp = ['ps'=>$idPs,'fecha'=>$fecha,'slots'=>[]];
                
                if(!$idPs || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)){ 
                    echo json_encode($resp); 
                    exit;
                }
                
                // Obtener bloques horario (similar a PsicologoController::slots)
                $diaMap = ['Mon'=>'lunes','Tue'=>'martes','Wed'=>'miércoles','Thu'=>'jueves','Fri'=>'viernes','Sat'=>'sábado','Sun'=>'domingo'];
                $dt = DateTime::createFromFormat('Y-m-d',$fecha);
                if(!$dt){ 
                    echo json_encode($resp); 
                    exit;
                }
                
                $diaBD = $diaMap[$dt->format('D')] ?? 'lunes';
                
                // Variantes sin acento para compatibilidad (miercoles, sabado)
                $variants = [$diaBD];
                if($diaBD==='miércoles') $variants[]='miercoles';
                if($diaBD==='sábado') $variants[]='sabado';
                
                $placeholders = implode(',', array_fill(0,count($variants),'?'));
                $pdo = $citaModel->pdo();
                
                $stH = $pdo->prepare("SELECT hora_inicio,hora_fin FROM Horario_Psicologo WHERE id_psicologo=? AND dia_semana IN ($placeholders) ORDER BY hora_inicio");
                $stH->execute(array_merge([$idPs],$variants));
                $bloques = $stH->fetchAll(PDO::FETCH_ASSOC);
                
                if(!$bloques){ 
                    $resp['message'] = 'Sin horarios configurados para este psicólogo en '.$diaBD;
                    echo json_encode($resp); 
                    exit;
                }
                
                $stC = $pdo->prepare("SELECT fecha_hora FROM Cita WHERE id_psicologo=? AND DATE(fecha_hora)=? AND estado='activo'");
                $stC->execute([$idPs,$fecha]);
                $ocup = [];
                foreach($stC->fetchAll(PDO::FETCH_ASSOC) as $r){ 
                    $ocup[substr($r['fecha_hora'],11,5)]=true; 
                }
                
                $slots=[]; 
                $now=new DateTime(); 
                $isToday=$fecha===$now->format('Y-m-d');
                
                foreach($bloques as $b){
                    $ini = new DateTime($fecha.' '.$b['hora_inicio']);
                    $fin = new DateTime($fecha.' '.$b['hora_fin']);
                    while($ini < $fin){
                        $h=$ini->format('H:i');
                        if(!isset($ocup[$h]) && (!$isToday || $ini > $now)) {
                            $slots[]=$h;
                        }
                        $ini->modify('+'.$interval.' minutes');
                    }
                }
                
                sort($slots); 
                $resp['slots']=$slots; 
                $resp['dia']=$diaBD;
                $resp['bloques_count'] = count($bloques);
                $resp['ocupadas_count'] = count($ocup);
                
                echo json_encode($resp);
                exit;
                
            } catch(Exception $e) {
                echo json_encode(['error'=>true,'message'=>$e->getMessage(),'slots'=>[]]);
                exit;
            }
        }

        if($_SERVER['REQUEST_METHOD']==='POST'){
            $op = $_POST['op'] ?? '';
            $id = (int)($_POST['id'] ?? 0);
            try {
                switch($op){
                    case 'cancelar':
                        $motivo = trim($_POST['motivo'] ?? '');
                        if($id && $motivo){ $citaModel->cancelar($id,$motivo); }
                        break;
                    case 'reprogramar':
                        $fh = trim($_POST['fecha_hora'] ?? '');
                        if($id && $fh){
                            // Mantener formato y validar minutos 00/30
                            $dt = DateTime::createFromFormat('Y-m-d\TH:i',$fh) ?: DateTime::createFromFormat('Y-m-d H:i',$fh);
                            if(!$dt) throw new Exception('Formato fecha/hora inválido');
                            $m = (int)$dt->format('i'); if($m!==0 && $m!==30) throw new Exception('Minutos deben ser 00 ó 30');
                            $citaModel->reprogramar($id,$dt->format('Y-m-d H:i:00'));
                        }
                        break;
                    case 'reasignar':
                        $ps = (int)($_POST['id_psicologo'] ?? 0);
                        $fhSel = trim($_POST['fecha_hora'] ?? ''); // nuevo slot seleccionado
                        if($id && $ps && $fhSel){
                            $cita = $citaModel->obtener($id);
                            if(!$cita) throw new Exception('Cita no encontrada');
                            if($cita['estado_cita']==='realizada') throw new Exception('No se puede reasignar una cita realizada');
                            $dt = DateTime::createFromFormat('Y-m-d H:i:s',$fhSel) ?: DateTime::createFromFormat('Y-m-d H:i',$fhSel);
                            if(!$dt) throw new Exception('Fecha/hora nueva inválida');
                            $m=(int)$dt->format('i'); if($m!==0 && $m!==30) throw new Exception('Minutos deben ser 00 o 30');
                            $fhFmt = $dt->format('Y-m-d H:i:00');
                            if(!$this->psicologoDisponible($citaModel,$ps,$fhFmt,0)) throw new Exception('Destino ocupado en ese horario');
                            // Actualizar psicólogo y fecha/hora
                            $pdo = $citaModel->pdo();
                            $st = $pdo->prepare("UPDATE Cita SET id_psicologo=?, fecha_hora=?, estado_cita='pendiente' WHERE id=?");
                            $st->execute([$ps,$fhFmt,$id]);
                        }
                        break;
                }
            } catch(Exception $e){ $_SESSION['flash_error'] = $e->getMessage(); }
            header('Location: '.url('admin','citas'));
            exit;
        }

        $psicologos = method_exists($psModel,'listarActivos')
            ? $psModel->listarActivos()
            : (method_exists($psModel,'listarTodos') ? $psModel->listarTodos() : []);

        $citas = $this->filtrarCitasAdmin($citaModel);
        $this->render('citas',[ 'citas'=>$citas,'psicologos'=>$psicologos ]);
    }

    // Aplica filtros GET: estado, fecha, texto (id, motivo), psicologo
    private function filtrarCitasAdmin(Cita $citaModel): array {
        if(method_exists($citaModel,'citasPorRango')){
            $base = $citaModel->citasPorRango(date('Y-m-01'),'2999-12-31');
        } elseif(method_exists($citaModel,'todas')){
            $base = $citaModel->todas();
        } else { $base = []; }
        $estado = $_GET['estado'] ?? '';
        $fecha  = $_GET['fecha'] ?? '';
        $texto  = strtolower(trim($_GET['texto'] ?? ''));
        $ps     = (int)($_GET['ps'] ?? 0);
        return array_values(array_filter($base,function($c) use($estado,$fecha,$texto,$ps){
            if($estado && $c['estado_cita']!==$estado) return false;
            if($fecha && substr($c['fecha_hora'],0,10)!==$fecha) return false;
            if($ps && (int)$c['id_psicologo']!==$ps) return false;
            if($texto){
                $hay = false;
                if(strpos((string)$c['id'],$texto)!==false) $hay=true;
                elseif(isset($c['motivo_consulta']) && strpos(strtolower($c['motivo_consulta']),$texto)!==false) $hay=true;
                elseif(strpos((string)$c['id_paciente'],$texto)!==false) $hay=true;
                if(!$hay) return false;
            }
            return true;
        }));
    }

    private function psicologoDisponible(Cita $citaModel,int $idPs,string $fechaHora,int $excluirId=0): bool {
        // Checar si ya existe cita en ese horario exacto
        $pdo = $citaModel->pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM Cita WHERE id_psicologo=? AND fecha_hora=? AND id<>? AND estado='activo'");
        $st->execute([$idPs,$fechaHora,$excluirId]);
        return (int)$st->fetchColumn()===0;
    }

    /* ================== Pagos =================== */
    public function pagos(): void {
        $this->requireAdmin();
        $pagoModel = new Pago();
        $pendientes = $pagoModel->listarPendientes();
        $ingresosMes = $pagoModel->ingresosPorMes((int)date('Y'));
        $ingPorPsico = $pagoModel->ingresosPorPsicologo();
        $this->render('pagos',[ 'pendientes'=>$pendientes,'ingresosMes'=>$ingresosMes,'ingPorPsico'=>$ingPorPsico ]);
    }

    /* ================== Tickets =================== */
    public function tickets(): void {
        $this->requireAdmin();
        require_once __DIR__ . '/../Models/TicketPago.php';
        $ticketModel = new TicketPago();
        $tickets = $ticketModel->listarTodos();
        $this->render('tickets',[ 'tickets'=>$tickets ]);
    }

    /* ================== Horarios Psicólogos =================== */
    public function horarios(): void {
        $this->requireAdmin();
        require_once __DIR__ . '/../Models/HorarioPsicologo.php';
        $psM = new Psicologo();
        $hM  = new HorarioPsicologo();
        $msg=''; $err='';
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $accion = $_POST['accion'] ?? '';
            try {
                if($accion==='crear'){
                    $idPs = (int)($_POST['id_psicologo']??0);
                    $dia  = $_POST['dia_semana'] ?? '';
                    $ini  = $_POST['hora_inicio'] ?? '';
                    $fin  = $_POST['hora_fin'] ?? '';
                    $hM->crear($idPs,$dia,$ini,$fin);
                    $msg='Horario agregado';
                } elseif($accion==='eliminar'){
                    $idH = (int)($_POST['id_horario']??0);
                    if($idH){ $hM->eliminar($idH); $msg='Horario eliminado'; }
                }
            } catch(Throwable $e){ $err=$e->getMessage(); }
            header('Location: '.url('admin','horarios').($err?'&err='.urlencode($err):'&ok=1')); exit;
        }
        $psicologos = $psM->listarTodos();
        $idSel = (int)($_GET['ps'] ?? 0);
        $horarios = $idSel ? $hM->listarPorPsicologo($idSel) : [];
        $this->render('horarios',[ 'psicologos'=>$psicologos,'horarios'=>$horarios,'idSel'=>$idSel ]);
    }

    /* =============== Solicitudes de Cambio =============== */
    public function solicitudes(): void {
        $this->requireAdmin();

        // Carga del modelo
        $fileCambio = __DIR__ . '/../Models/SolicitudCambio.php';
        $fileAlias  = __DIR__ . '/../Models/Solicitud.php';
        if(file_exists($fileCambio)) require_once $fileCambio;
        if(file_exists($fileAlias))  require_once $fileAlias; // por si ya existe

        $modelClass = null;
        if(class_exists('SolicitudCambio')) {
            $modelClass = 'SolicitudCambio';
        } elseif(class_exists('Solicitud')) {
            $modelClass = 'Solicitud';
        }

        if(!$modelClass){
            echo '<div class="alert alert-danger">Modelo de solicitudes no encontrado.</div>';
            return;
        }

        $model = new $modelClass();

        // Métodos posibles
        if(method_exists($model,'pendientes')) {
            $pendientes = $model->pendientes();
        } elseif(method_exists($model,'listarPendientes')) {
            $pendientes = $model->listarPendientes();
        } else {
            $pendientes = [];
        }

        $this->render('solicitudes',[ 'pendientes'=>$pendientes ]);
    }

    /* ============ Endpoints JSON para Charts ============ */
    public function jsonUsuariosActivos(): void { $this->requireAdmin(); header('Content-Type: application/json'); echo json_encode((new Usuario())->conteoActivosInactivos()); }
    public function jsonCitasEstados(): void { $this->requireAdmin(); header('Content-Type: application/json'); echo json_encode((new Cita())->estadisticasEstado()); }
    public function jsonIngresosMes(): void { $this->requireAdmin(); header('Content-Type: application/json'); echo json_encode((new Pago())->ingresosPorMes((int)date('Y'))); }
    public function jsonIngresosPsicologo(): void { $this->requireAdmin(); header('Content-Type: application/json'); echo json_encode((new Pago())->ingresosPorPsicologo()); }
}