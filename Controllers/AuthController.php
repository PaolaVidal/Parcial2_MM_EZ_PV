<?php
/** Controlador de Autenticación (router: /auth/login, /auth/registrar, /auth/logout) */
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Paciente.php';

class AuthController {

    private function render(string $vista, array $data = []): void {
        // Ajuste a carpeta "Views" (mayúscula)
        $file = __DIR__ . '/../Views/' . $vista . '.php';
        if (!file_exists($file)) {
            echo '<div class="alert alert-danger">Vista no encontrada: ' . htmlspecialchars($vista) . '</div>';
            return;
        }
        extract($data);
        require $file;
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? ''; // Asegúrate que la vista usa name="password"
            $usuarioModel = new Usuario();
            $user = $usuarioModel->autenticar($email, $pass);
            if ($user) {
                $_SESSION['usuario'] = [
                    'id'     => $user['id'],
                    'nombre' => $user['nombre'],
                    'rol'    => $user['rol']
                ];
                // Redirigir al dashboard según el rol
                if ($user['rol'] === 'admin') {
                    header('Location: index.php?controller=Admin&action=dashboard');
                } elseif ($user['rol'] === 'psicologo') {
                    header('Location: index.php?controller=Psicologo&action=dashboard');
                } else {
                    header('Location: ' . RUTA);
                }
                exit;
            }
            $this->render('auth/login', ['error' => 'Credenciales inválidas']);
            return;
        }
        $this->render('auth/login');
    }

    public function registrar(): void {
        $error = '';
        $old = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $nombre    = trim($_POST['nombre'] ?? '');
            $email     = trim($_POST['email'] ?? '');
            $password  = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';

            $fechaNacRaw = trim($_POST['fecha_nacimiento'] ?? '');
            $genero      = trim($_POST['genero'] ?? '');
            $telefono    = trim($_POST['telefono'] ?? '');
            $direccion   = trim($_POST['direccion'] ?? '');
            $historial   = trim($_POST['historial_clinico'] ?? '');

            $old = [
                'nombre'=>$nombre,'email'=>$email,'fecha_nacimiento'=>$fechaNacRaw,
                'genero'=>$genero,'telefono'=>$telefono,'direccion'=>$direccion,
                'historial_clinico'=>$historial
            ];

            // Reglas rango fechas
            $minDate = '1900-01-01';
            $maxDate = (new DateTime('-5 years'))->format('Y-m-d'); // edad mínima 5
            $today   = (new DateTime())->format('Y-m-d');

            // Validaciones básicas
            if ($nombre==='' || $email==='' || $password==='' || $password2==='' ||
                $fechaNacRaw==='' || $genero==='' || $telefono==='' || $direccion==='' || $historial==='') {
                $error = 'Todos los campos son obligatorios.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email inválido.';
            } elseif ($password !== $password2) {
                $error = 'Las contraseñas no coinciden.';
            } elseif (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif (!in_array($genero, ['masculino','femenino','otro'], true)) {
                $error = 'Género inválido.';
            } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $telefono)) {
                $error = 'Teléfono inválido (7-20 caracteres, dígitos + - espacio).';
            } else {
                // Validar fecha
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacRaw)) {
                    $error = 'Formato de fecha inválido.';
                } else {
                    $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaNacRaw);
                    // verificar que realmente se parseó y no es falsa
                    if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fechaNacRaw) {
                        $error = 'Fecha de nacimiento inválida.';
                    } else {
                        if ($fechaNacRaw < $minDate) {
                            $error = 'Fecha demasiado antigua.';
                        } elseif ($fechaNacRaw > $maxDate) {
                            $error = 'La fecha de nacimiento no puede ser futura ni implicar menos de 5 años.';
                        } else {
                            // (opcional) edad máxima lógica 120
                            $edad = $fechaObj->diff(new DateTime())->y;
                            if ($edad > 120) {
                                $error = 'Edad no lógica (>120).';
                            }
                        }
                    }
                }
            }

            if ($error === '') {
                $usuarioModel = new Usuario();
                if ($usuarioModel->emailExiste($email)) {
                    $error = 'El email ya está registrado.';
                } else {
                    $idUsuario = $usuarioModel->crear([
                        'nombre'=>$nombre,
                        'email'=>$email,
                        'password'=>$password,
                        'rol'=>'paciente'
                    ]);

                    $pacienteModel = new Paciente();
                    $pacienteModel->crearPorUsuario($idUsuario, [
                        'fecha_nacimiento'  => $fechaNacRaw,
                        'genero'            => $genero,
                        'correo'            => $email,
                        'direccion'         => $direccion,
                        'telefono'          => $telefono,
                        'historial_clinico' => $historial
                    ]);

                    header('Location: ' . RUTA . 'auth/login');
                    exit;
                }
            }
        }
        $this->render('auth/registrar', ['error'=>$error,'old'=>$old]);
    }

    public function logout(): void {
        session_destroy();
        header('Location: ' . RUTA . 'auth/login');
        exit;
    }
}
