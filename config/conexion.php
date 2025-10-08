<?php
class Conexion {
    private $host = "localhost";
    private $db = "";
    private $user = "root";
    private $pass = ""; // tu contraseña
    public $pdo; // debe ser pública o usar get/set
    private $stmt;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4",
                $this->user,
                $this->pass
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Ejecutar query de selección
    public function consulta($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ejecutar insert, update, delete
    public function ejecutar($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Obtener último ID insertado
    public function ultimoId() {
        return $this->pdo->lastInsertId();
    }
}
?>
