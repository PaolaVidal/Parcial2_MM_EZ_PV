<?php
/** Controlador de Autenticación */
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController extends BaseController {
    public function login(){
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $email = $_POST['email'] ?? '';
            $pass  = $_POST['passwordd'] ?? '';
            $usuarioModel = new Usuario();
            $user = $usuarioModel->autenticar($email, $pass);
            if($user){
                $_SESSION['usuario'] = [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol']
                ];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Credenciales inválidas';
                $this->view('auth/login', ['error'=>$error]);
                return;
            }
        }
        $this->view('auth/login');
    }

    public function logout(){
        session_destroy();
        header('Location: index.php?controller=Auth&action=login');
        exit;
    }
}
