<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Psicologo.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';

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

        if($_SERVER['REQUEST_METHOD']==='POST'){
            $op = $_POST['op'] ?? '';
            $id = (int)($_POST['id'] ?? 0);
            try {
                switch($op){
                    case 'cancelar':
                        $motivo = trim($_POST['motivo'] ?? '');
                        if($id && $motivo){
                            $citaModel->cancelar($id,$motivo);
                        }
                        break;
                    case 'reprogramar':
                        $fh = trim($_POST['fecha_hora'] ?? '');
                        if($id && $fh){
                            $citaModel->reprogramar($id,$fh);
                        }
                        break;
                    case 'reasignar':
                        $ps = (int)($_POST['id_psicologo'] ?? 0);
                        if($id && $ps){
                            if(method_exists($citaModel,'reasignarPsicologo')){
                                $citaModel->reasignarPsicologo($id,$ps);
                            } elseif(method_exists($citaModel,'reasignar')){
                                $citaModel->reasignar($id,$ps);
                            } else {
                                throw new Exception('No existe método de reasignación en Cita');
                            }
                        }
                        break;
                }
            } catch(Exception $e){
                $_SESSION['flash_error'] = $e->getMessage();
            }
            header('Location: '.url('admin','citas'));
            exit;
        }

        if(method_exists($citaModel,'citasPorRango')){
            $citas = $citaModel->citasPorRango(date('Y-m-01'),'2999-12-31');
        } elseif(method_exists($citaModel,'todas')){
            $citas = $citaModel->todas();
        } else {
            $citas = [];
        }
        $psicologos = method_exists($psModel,'listarActivos')
            ? $psModel->listarActivos()
            : (method_exists($psModel,'listarTodos') ? $psModel->listarTodos() : []);

        $this->render('citas',[ 'citas'=>$citas,'psicologos'=>$psicologos ]);
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

    /* =============== Solicitudes de Cambio =============== */
    public function solicitudes(): void {
        $this->requireAdmin();

        // Carga del modelo
        $fileCambio = __DIR__ . '/../models/SolicitudCambio.php';
        $fileAlias  = __DIR__ . '/../models/Solicitud.php';
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