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
        $psModel = new Psicologo();
        $usrModel = new Usuario();
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $accion = $_POST['accion'] ?? '';
            if($accion==='crear'){
                $nombre=trim($_POST['nombre']??'');
                $email=trim($_POST['email']??'');
                $pass=$_POST['password']??'';
                $esp=trim($_POST['especialidad']??'');
                if($nombre && $email && $pass){
                    $idU = $usrModel->crear(['nombre'=>$nombre,'email'=>$email,'password'=>$pass,'rol'=>'psicologo']);
                    $psModel->crear($idU,[ 'especialidad'=>$esp ]);
                }
            }
        }
        $lista = $psModel->listarTodos();
        $masSolic = $psModel->masSolicitados();
        $this->render('psicologos',[ 'psicologos'=>$lista,'masSolic'=>$masSolic ]);
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