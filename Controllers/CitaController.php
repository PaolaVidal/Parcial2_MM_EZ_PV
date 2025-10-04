<?php
/** Controlador de Citas */
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../helpers/QRHelper.php';
require_once __DIR__ . '/../helpers/UrlHelper.php';

class CitaController extends BaseController {
    public function index(){
        $model = new Cita();
        $citas = $model->listar();
        $this->view('citas/listado', ['citas'=>$citas]);
    }

    public function crear(){
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $citaModel = new Cita();
            // Generar QR con ID temporal? Primero insert sin QR? -> Estrategia: generar después
            // Insertamos con QR temporal y luego actualizamos: más simple es generar QR con placeholder y luego reemplazar.
            $qrTemp = QRHelper::generarQR('Creando cita...');
            $id = $citaModel->crear([
                'id_paciente' => $_POST['id_paciente'],
                'id_psicologo'=> $_POST['id_psicologo'],
                'fecha_hora'  => $_POST['fecha_hora'],
                'motivo_consulta' => $_POST['motivo_consulta'],
                'qr_code' => $qrTemp
            ]);
            // Re-generar QR definitivo con enlace
            $url = base_url() . 'index.php?controller=Cita&action=ver&id=' . $id;
            $qrDef = QRHelper::generarQR('CITA:' . $id . ' URL:' . $url, 'cita_'.$id);
            // Actualizar ruta QR
            $citaModel->db->prepare("UPDATE Cita SET qr_code=? WHERE id=?")->execute([$qrDef, $id]);

            // Crear Pago relacionado
            $pagoModel = new Pago();
            $pagoModel->crearParaCita($id);

            header('Location: ?controller=Cita&action=index');
            exit;
        }
        $this->view('citas/crear');
    }

    public function ver(){
        $id = $_GET['id'] ?? null;
        if(!$id){ echo 'ID requerido'; return; }
        $model = new Cita();
        $cita = $model->obtener($id);
        $this->view('citas/ver', ['cita'=>$cita]);
    }

    // Método baseUrl eliminado: usar helper base_url()
}
