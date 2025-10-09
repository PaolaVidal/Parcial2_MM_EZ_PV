<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Especialidad.php';

class EspecialidadController extends BaseController
{
    // Usa safeRedirect heredado de BaseController

    /**
     * Listar todas las especialidades
     */
    public function index(): void
    {
        $this->requireAdmin();

        $especialidadModel = new Especialidad();
        $especialidades = $especialidadModel->listarTodas();

        // Agregar conteo de psicólogos a cada especialidad
        foreach ($especialidades as &$esp) {
            $esp['count_psicologos'] = $especialidadModel->contarPsicologos((int) $esp['id']);
        }

        $error = $_SESSION['error_especialidad'] ?? '';
        $success = $_SESSION['success_especialidad'] ?? '';
        unset($_SESSION['error_especialidad'], $_SESSION['success_especialidad']);
        // Mantener diseño original (solo contenido; layout global ya lo envuelve desde index.php)
        $this->render('admin/especialidades', [
            'especialidades' => $especialidades,
            'error' => $error,
            'success' => $success
        ]);
    }

    /**
     * Crear especialidad
     */
    public function crear(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_especialidad'] = 'Método no permitido.';
            $this->safeRedirect(RUTA . 'index.php?url=especialidad');
            return;
        }

        try {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($nombre)) {
                throw new Exception('El nombre es requerido');
            }

            $especialidadModel = new Especialidad();
            $especialidadModel->crear([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => 'activo'
            ]);

            $_SESSION['success_especialidad'] = 'Especialidad creada exitosamente';
        } catch (Exception $e) {
            // Duplicado (clave única)
            if (stripos($e->getMessage(), '1062') !== false || str_contains($e->getMessage(), 'Duplicate entry')) {
                $_SESSION['error_especialidad'] = 'Ya existe una especialidad con ese nombre.';
            } else {
                $_SESSION['error_especialidad'] = $e->getMessage();
            }
        }
        $this->safeRedirect(RUTA . 'index.php?url=especialidad');
    }

    /**
     * Actualizar especialidad
     */
    public function actualizar(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_especialidad'] = 'Método no permitido.';
            $this->safeRedirect(RUTA . 'index.php?url=especialidad');
            return;
        }

        try {
            $id = (int) ($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $estado = $_POST['estado'] ?? 'activo';

            if (!$id || empty($nombre)) {
                throw new Exception('Datos incompletos');
            }

            $especialidadModel = new Especialidad();
            $especialidadModel->actualizar($id, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => $estado
            ]);

            $_SESSION['success_especialidad'] = 'Especialidad actualizada exitosamente';
        } catch (Exception $e) {
            if (stripos($e->getMessage(), '1062') !== false || str_contains($e->getMessage(), 'Duplicate entry')) {
                $_SESSION['error_especialidad'] = 'El nombre ya está en uso por otra especialidad.';
            } else {
                $_SESSION['error_especialidad'] = $e->getMessage();
            }
        }
        $this->safeRedirect(RUTA . 'index.php?url=especialidad');
    }

    /**
     * Cambiar estado
     */
    public function cambiarEstado(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_especialidad'] = 'Método no permitido.';
            $this->safeRedirect(RUTA . 'index.php?url=especialidad');
            return;
        }

        try {
            $id = (int) ($_POST['id'] ?? 0);
            $estado = $_POST['estado'] ?? 'activo';

            if (!$id) {
                throw new Exception('ID no válido');
            }

            $especialidadModel = new Especialidad();
            $especialidadModel->cambiarEstado($id, $estado);

            $_SESSION['success_especialidad'] = 'Estado cambiado exitosamente';
        } catch (Exception $e) {
            $_SESSION['error_especialidad'] = $e->getMessage();
        }
        $this->safeRedirect(RUTA . 'index.php?url=especialidad');
    }

    /**
     * Eliminar especialidad
     */
    public function eliminar(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_especialidad'] = 'Método no permitido.';
            $this->safeRedirect(RUTA . 'index.php?url=especialidad');
            return;
        }

        try {
            $id = (int) ($_POST['id'] ?? 0);

            if (!$id) {
                throw new Exception('ID no válido');
            }

            $especialidadModel = new Especialidad();

            // Verificar si tiene psicólogos asignados
            if ($especialidadModel->tienePsicologos($id)) {
                throw new Exception('No se puede eliminar una especialidad con psicólogos asignados. Primero desactívela.');
            }

            $especialidadModel->eliminar($id);

            $_SESSION['success_especialidad'] = 'Especialidad eliminada exitosamente';
        } catch (Exception $e) {
            $_SESSION['error_especialidad'] = $e->getMessage();
        }
        $this->safeRedirect(RUTA . 'index.php?url=especialidad');
    }
}
