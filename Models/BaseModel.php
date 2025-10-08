<?php
/**
 * BaseModel ofrece acceso rápido a PDO y helpers comunes.
 */
require_once __DIR__ . '/../config/db.php';

abstract class BaseModel {
    protected $db; // instancia PDO

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Acceso controlado al PDO para otros servicios/controladores */
    public function pdo(): PDO {
        return $this->db;
    }
}
