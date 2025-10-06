<?php
class Solicitud {
    private $dbWrapper;        // Puede ser wrapper o PDO
    private PDO $pdo;          // Siempre el PDO final

    public function __construct() {
        // Intentar obtener algo desde la clase Database (wrapper o PDO)
        if(class_exists('Database')){
            if(method_exists('Database','get')) {
                $this->dbWrapper = Database::get();
            } elseif(method_exists('Database','getInstance')) {
                $this->dbWrapper = Database::getInstance();
            }
        }

        // Resolver PDO
        if($this->dbWrapper instanceof PDO){
            $this->pdo = $this->dbWrapper;
        } elseif($this->dbWrapper) {
            // Intentar métodos comunes
            if(method_exists($this->dbWrapper,'getConnection')) {
                $cand = $this->dbWrapper->getConnection();
                if($cand instanceof PDO) $this->pdo = $cand;
            } elseif(method_exists($this->dbWrapper,'getPdo')) {
                $cand = $this->dbWrapper->getPdo();
                if($cand instanceof PDO) $this->pdo = $cand;
            } elseif(property_exists($this->dbWrapper,'pdo')) {
                $cand = $this->dbWrapper->pdo;
                if($cand instanceof PDO) $this->pdo = $cand;
            }
        }

        // Si aún no tenemos PDO, crear conexión directa fallback
        if(empty($this->pdo)){
            $this->pdo = new PDO('mysql:host=localhost;dbname=psicologia;charset=utf8','root','',[
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
    }

    private function p(): PDO { return $this->pdo; }

    // Obtener solicitudes pendientes
    public function pendientes(): array {
        $sql = "SELECT id,id_paciente,dui,campo,valor_nuevo,fecha,estado
                FROM solicitudes
                WHERE estado='pendiente'
                ORDER BY fecha DESC";
        return $this->p()->query($sql)->fetchAll();
    }

    // Marcar solicitud con nuevo estado (aprobada / rechazada)
    public function marcar(int $id,string $estado): bool {
        $st = $this->p()->prepare("UPDATE solicitudes SET estado=:e, fecha_respuesta=NOW() WHERE id=:id");
        return $st->execute([':e'=>$estado, ':id'=>$id]);
    }

    // (Opcional) crear solicitud (para pruebas)
    public function crear(int $idPaciente,string $dui,string $campo,string $valor): bool {
        $st = $this->p()->prepare("INSERT INTO solicitudes(id_paciente,dui,campo,valor_nuevo,fecha,estado)
                                   VALUES(:p,:d,:c,:v,NOW(),'pendiente')");
        return $st->execute([':p'=>$idPaciente,':d'=>$dui,':c'=>$campo,':v'=>$valor]);
    }
}