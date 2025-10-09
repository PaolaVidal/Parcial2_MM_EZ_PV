<?php
/**
 * Clase de conexión PDO (Singleton)
 * Ajusta credenciales según tu entorno local.
 */
class Database {
    private static $instance = null; // Instancia única
    private $pdo;                   // Objeto PDO

    private $host = 'localhost';
    private $db   = 'psicologia2';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';

    // Constructor privado para evitar múltiples instancias
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die('Error de Conexión: ' . $e->getMessage());
        }
    }

    // Obtener la instancia única
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Retornar el objeto PDO para consultas
    public function getConnection() {
        return $this->pdo;
    }
}
