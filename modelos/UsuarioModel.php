<?php
require_once 'config/conexion.php';
require_once 'clases/Usuario.php';

class UsuarioModel {
    private $cn;

    public function __construct() {
        $this->cn = new Conexion();
    }

    public function listar($filtro = '') {
        if ($filtro) {
            $sql = "SELECT * FROM usuario WHERE nombre LIKE ? OR email LIKE ?";
            $results = $this->cn->consulta($sql, ["%$filtro%", "%$filtro%"]);
        } else {
            $sql = "SELECT * FROM usuario";
            $results = $this->cn->consulta($sql);
        }

        $usuarios = [];
        foreach ($results as $row) {
            $usuarios[] = new Usuario($row['id'], $row['nombre'], $row['email'], $row['contrasena'], $row['rol'], $row['estado']);
        }
        return $usuarios;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM usuario WHERE id = ?";
        $results = $this->cn->consulta($sql, [$id]);
        if (!empty($results)) {
            $row = $results[0];
            return new Usuario($row['id'], $row['nombre'], $row['email'], $row['contrasena'], $row['rol'], $row['estado']);
        }
        return null;
    }

    public function crear($nombre, $email, $contrasena, $rol, $estado) {
        $usuario = new Usuario(null, $nombre, $email, $contrasena, $rol, $estado);
        return $this->insert($usuario);
    }

    public function actualizar($id, $nombre, $email, $contrasena, $rol, $estado) {
        $usuario = new Usuario($id, $nombre, $email, $contrasena, $rol, $estado);
        return $this->update($usuario);
    }

    public function eliminar($id) {
        $usuario = new Usuario($id);
        return $this->delete($usuario);
    }

    private function insert($usuarioObj) {
        $sql = "INSERT INTO usuario (nombre, email, contrasena, rol, estado) VALUES (?, ?, ?, ?, ?)";
        return $this->cn->ejecutar($sql, [$usuarioObj->getNombre(), $usuarioObj->getEmail(), $usuarioObj->getContrasena(), $usuarioObj->getRol(), $usuarioObj->getEstado()]);
    }

    private function update($usuarioObj) {
        $sql = "UPDATE usuario SET nombre = ?, email = ?, contrasena = ?, rol = ?, estado = ? WHERE id = ?";
        return $this->cn->ejecutar($sql, [$usuarioObj->getNombre(), $usuarioObj->getEmail(), $usuarioObj->getContrasena(), $usuarioObj->getRol(), $usuarioObj->getEstado(), $usuarioObj->getId()]);
    }

    private function delete($usuarioObj) {
        $sql = "DELETE FROM usuario WHERE id = ?";
        return $this->cn->ejecutar($sql, [$usuarioObj->getId()]);
    }
}
?>
