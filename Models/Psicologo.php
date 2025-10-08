<?php
/** Modelo Psicologo */
require_once __DIR__ . '/BaseModel.php';

class Psicologo extends BaseModel {

    private string $tPsicologo;
    private string $tUsuario;
    private string $tCita;
    private string $colIdUsuario = 'id_usuario';
    private ?string $colEspecialidad = 'id_especialidad';
    private ?string $colExperiencia = null;
    private ?string $colHorario = null;

    public function __construct() {
        parent::__construct();
        $this->resolverTablas();
        $this->resolverColumnas();
    }

    private function resolverTablas(): void {
        // Carga listado de tablas (MySQL)
        $tables = $this->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        // Detectar tabla psicólogo
        $this->tPsicologo = $this->pick($tables,
            ['Psicologo','psicologo','psicologos','Psicologos']
        ) ?? 'Psicologo';

        // Detectar tabla usuario
        $this->tUsuario = $this->pick($tables,
            ['Usuario','usuario','usuarios','Usuarios']
        ) ?? 'Usuario';

        // Detectar tabla cita
        $this->tCita = $this->pick($tables,
            ['Cita','cita','citas','Citas']
        ) ?? 'Cita';
    }

    private function resolverColumnas(): void {
        $st = $this->db->query("DESCRIBE {$this->tPsicologo}");
        $cols = $st->fetchAll(PDO::FETCH_COLUMN);
        $this->colIdUsuario = $this->matchCol($cols,['id_usuario','idUsuario','usuario_id']) ?? $this->colIdUsuario;
        $this->colEspecialidad = $this->matchCol($cols,['id_especialidad','especialidad','Especialidad']);
        $this->colExperiencia  = $this->matchCol($cols,['experiencia','Experiencia','exp']);
        $this->colHorario      = $this->matchCol($cols,['horario','Horario','agenda']);
    }

    private function matchCol(array $cols,array $cands): ?string {
        foreach($cands as $c){
            foreach($cols as $col){
                if(strtolower($col) === strtolower($c)) return $col;
            }
        }
        return null;
    }

    private function pick(array $tables, array $candidates): ?string {
        foreach($candidates as $c){
            if(in_array($c,$tables,true)) return $c;
        }
        return null;
    }

    /* ================== Helpers ================== */

    private function q(string $sql): PDOStatement {
        return $this->db->query($sql);
    }

    /* ================== CRUD ================== */

    public function obtenerPorUsuario(int $idUsuario): ?array {
        $st = $this->db->prepare("SELECT * FROM {$this->tPsicologo} WHERE id_usuario = ? LIMIT 1");
        $st->execute([$idUsuario]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function get(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM {$this->tPsicologo} WHERE id = :id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function crear(int $idUsuario, array $data): int {
        $columns = [$this->colIdUsuario];
        $place   = [':idUsuario'];
        $params  = [':idUsuario'=>$idUsuario];

        if($this->colEspecialidad){
            $columns[] = $this->colEspecialidad;
            $place[]   = ':especialidad';
            $params[':especialidad'] = $data['id_especialidad'] ?? $data['especialidad'] ?? null;
        }
        if($this->colExperiencia){
            $columns[] = $this->colExperiencia;
            $place[]   = ':experiencia';
            $params[':experiencia'] = $data['experiencia'] ?? null;
        }
        if($this->colHorario){
            $columns[] = $this->colHorario;
            $place[]   = ':horario';
            $params[':horario'] = $data['horario'] ?? null;
        }

        $sql = "INSERT INTO {$this->tPsicologo} (".implode(',',$columns).") VALUES (".implode(',',$place).")";
        $st  = $this->db->prepare($sql);
        if(!$st->execute($params)){
            $err = $st->errorInfo();
            throw new Exception('Insert Psicologo error: '.$err[2]);
        }
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool {
        $sets = [];
        $params = [':id'=>$id];
        if($this->colEspecialidad){
            $sets[] = "{$this->colEspecialidad} = :especialidad";
            $params[':especialidad'] = $data['id_especialidad'] ?? $data['especialidad'] ?? '';
        }
        if($this->colExperiencia){
            $sets[] = "{$this->colExperiencia} = :experiencia";
            $params[':experiencia'] = $data['experiencia'] ?? '';
        }
        if($this->colHorario){
            $sets[] = "{$this->colHorario} = :horario";
            $params[':horario'] = $data['horario'] ?? '';
        }
        if(!$sets) return true;
        $sql = "UPDATE {$this->tPsicologo} SET ".implode(',',$sets)." WHERE id = :id";
        $st  = $this->db->prepare($sql);
        return $st->execute($params);
    }

    public function eliminar(int $id): bool {
        // Si prefieres baja lógica cambia a: UPDATE ... SET estado='inactivo'
        $st = $this->db->prepare("DELETE FROM {$this->tPsicologo} WHERE id = :id");
        return $st->execute([':id'=>$id]);
    }

    /* ============== Listados / Estadísticas ============== */

    public function listarActivos(): array {
        $sql = "SELECT p.*, u.nombre, u.email, u.estado
                FROM {$this->tPsicologo} p
                JOIN {$this->tUsuario} u ON u.id = p.id_usuario
                WHERE u.estado = 'activo' AND u.rol='psicologo'";
        return $this->q($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos(): array {
        $sql = "SELECT p.*, u.nombre, u.email, u.estado, e.nombre as nombre_especialidad
                FROM {$this->tPsicologo} p
                JOIN {$this->tUsuario} u ON u.id = p.id_usuario
                LEFT JOIN Especialidad e ON e.id = p.id_especialidad
                WHERE u.rol='psicologo'
                ORDER BY u.nombre";
        return $this->q($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function masSolicitados(int $limit = 5): array {
        $st = $this->db->prepare(
            "SELECT c.id_psicologo, COUNT(*) total
             FROM {$this->tCita} c
             GROUP BY c.id_psicologo
             ORDER BY total DESC
             LIMIT :lim"
        );
        $st->bindValue(':lim',$limit,PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sesionesPorPsicologo(): array {
        $sql = "SELECT c.id_psicologo, COUNT(*) total
                FROM {$this->tCita} c
                WHERE c.estado_cita = 'realizada'
                GROUP BY c.id_psicologo";
        return $this->q($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
