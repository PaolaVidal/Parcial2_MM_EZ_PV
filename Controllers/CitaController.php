<?php
/** Controlador de Citas (router: /cita/accion/id) */
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class CitaController {

    // Ajusta la ruta base de vistas si tu carpeta se llama diferente (views, vistas, etc.)
    private string $viewsPath;

    public function __construct() {
        $this->viewsPath = __DIR__ . '/../views/'; // cambia a ../vistas/ si corresponde
    }

    private function render(string $vista, array $data = []): void {
        $file = $this->viewsPath . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista no encontrada: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }

    public function index(): void {
        $model = new Cita();
        $citas = $model->listar();
        $this->render('citas/listado', ['citas' => $citas]);
    }

    public function crear(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $idPaciente   = (int)($_POST['id_paciente']   ?? 0);
            $idPsicologo  = (int)($_POST['id_psicologo']  ?? 0);
            $fechaHora    = trim($_POST['fecha_hora']     ?? '');
            $motivo       = trim($_POST['motivo_consulta']?? '');

            if ($idPaciente <= 0 || $idPsicologo <= 0 || $fechaHora === '' || $motivo === '') {
                echo '<div class="alert alert-danger">Completa todos los campos obligatorios.</div>';
                $this->render('citas/crear');
                return;
            }

            $citaModel = new Cita();

            // QR temporal
            $qrTemp = QRHelper::generarQR('Creando cita...');

            $id = $citaModel->crear([
                'id_paciente'     => $idPaciente,
                'id_psicologo'    => $idPsicologo,
                'fecha_hora'      => $fechaHora,
                'motivo_consulta' => $motivo,
                'qr_code'         => $qrTemp
            ]);

            if (!$id) {
                echo '<div class="alert alert-danger">Error al crear la cita.</div>';
                $this->render('citas/crear');
                return;
            }

            // URL definitiva (router nuevo)
            $urlDetalle = RUTA . 'cita/ver/' . $id;
            // Generar QR definitivo (puedes ajustar el contenido)
            $qrDef = QRHelper::generarQR('CITA:' . $id . ' URL:' . $urlDetalle, 'cita_' . $id);

            // Actualizar QR en DB (ideal: método en modelo; aquí directo si tu modelo expone ->db)
            try {
                $citaModel->db->prepare("UPDATE Cita SET qr_code=? WHERE id=?")->execute([$qrDef, $id]);
            } catch (Throwable $e) {
                // No detiene el flujo, pero avisa
                error_log('Error actualizando QR cita '.$id.': '.$e->getMessage());
            }

            // Crear pago relacionado
            $pagoModel = new Pago();
            $pagoModel->crearParaCita($id);

            header('Location: ' . RUTA . 'cita');
            exit;
        }

        $this->render('citas/crear');
    }

    public function ver($id): void {
        $id = (int)$id;
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID inválido.</div>';
            return;
        }
        $model = new Cita();
        $cita = $model->obtener($id);
        if (!$cita) {
            echo '<div class="alert alert-warning">Cita no encontrada.</div>';
            return;
        }
        $this->render('citas/ver', ['cita' => $cita]);
    }

    // Si necesitaras eliminar, editar, etc., agrega métodos similares:
    // public function eliminar($id) { ... }
}
