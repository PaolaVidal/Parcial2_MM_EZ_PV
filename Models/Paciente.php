<?php
/** Modelo Paciente */
require_once __DIR__ . '/BaseModel.php';

class Paciente extends BaseModel {

    private string $tabla = 'Paciente';
    private string $colNombre = 'nombre';
    private ?string $colEmail = 'email';
    private ?string $colTelefono = 'telefono';
    private ?string $colDireccion = 'direccion';

    // AÑADIR SI NO EXISTEN:
    private ?string $colDui = 'dui';
    private ?string $colCodigoAcceso = 'codigo_acceso';
    private ?string $colFechaNac = 'fecha_nacimiento';
    private ?string $colGenero = 'genero';
    private ?string $colHistorial = 'historial_clinico';

    public function __construct(){
        parent::__construct();
        $this->detectarColumnas();
    }

    private function detectarColumnas(): void {
        try {
            $cols = $this->db->query("DESCRIBE {$this->tabla}")->fetchAll(PDO::FETCH_COLUMN);
            $pick = function(array $cands) use ($cols){
                foreach($cands as $c) foreach($cols as $col) if(strtolower($col)===strtolower($c)) return $col;
                return null;
            };
            $this->colNombre       = $pick(['nombre']) ?? $this->colNombre;
            $this->colEmail        = $pick(['email','correo']);
            $this->colTelefono     = $pick(['telefono','tel']);
            $this->colDireccion    = $pick(['direccion','dir']);
            $this->colEstado       = $pick(['estado']);
            $this->colDui          = $pick(['dui']);
            $this->colCodigoAcceso = $pick(['codigo_acceso','codigo']);
            $this->colFechaNac     = $pick(['fecha_nacimiento','nacimiento']);
            $this->colGenero       = $pick(['genero','sexo']);
            $this->colHistorial    = $pick(['historial_clinico','historial']);
        } catch(Throwable $e){}
    }

    private function c(?string $c): bool { return !empty($c); }

    public function listarTodos(): array {
        $sel = ['id',$this->colNombre];
        foreach([
            $this->colEmail,$this->colTelefono,$this->colDireccion,$this->colEstado,
            $this->colDui,$this->colCodigoAcceso,$this->colFechaNac,$this->colGenero,$this->colHistorial
        ] as $c) if($this->c($c)) $sel[]=$c;
        $sql="SELECT ".implode(',',$sel)." FROM {$this->tabla} ORDER BY {$this->colNombre}";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $d): int {
        $codigo = $this->c($this->colCodigoAcceso)?$this->generarCodigoUnico():null;
        $cols=[]; $place=[]; $p=[];
        $add = function(string $key, ?string $col) use (&$cols,&$place,&$p,$d){
            if(!$col || !array_key_exists($key,$d)) return;
            $cols[]=$col;
            if($d[$key]===''){ $place[]='NULL'; return; }
            $ph=':i_'.$key;
            $place[]=$ph;
            $p[$ph]=$d[$key];
        };
        $add('nombre',$this->colNombre);
        $add('email',$this->colEmail);
        $add('telefono',$this->colTelefono);
        $add('direccion',$this->colDireccion);
        $add('dui',$this->colDui);
        if($codigo && $this->c($this->colCodigoAcceso)){ $cols[]=$this->colCodigoAcceso; $place[]=':i_code'; $p[':i_code']=$codigo; }
        $add('fecha_nacimiento',$this->colFechaNac);
        $add('genero',$this->colGenero);
        $add('historial_clinico',$this->colHistorial);
        if($this->c($this->colEstado)){ $cols[]=$this->colEstado; $place[]="'activo'"; }
        $sql="INSERT INTO {$this->tabla} (".implode(',',$cols).") VALUES (".implode(',',$place).")";
        $st=$this->db->prepare($sql);
        if(!$st->execute($p)){ $e=$st->errorInfo(); throw new Exception('Insert paciente: '.$e[2]); }
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): bool {
        $sets=[]; $p=[':id'=>$id];
        $upd=function(string $key, ?string $col) use (&$sets,&$p,$d){
            if(!$col || !array_key_exists($key,$d)) return;
            if($d[$key]===''){ $sets[]="$col=NULL"; return; }
            $ph=':u_'.$key;
            $sets[]="$col=$ph";
            $p[$ph]=$d[$key];
        };
        foreach(['nombre','email','telefono','direccion','dui','fecha_nacimiento','genero','historial_clinico'] as $k){
            $colVar = match($k){
                'nombre'=>$this->colNombre,'email'=>$this->colEmail,'telefono'=>$this->colTelefono,
                'direccion'=>$this->colDireccion,'dui'=>$this->colDui,'fecha_nacimiento'=>$this->colFechaNac,
                'genero'=>$this->colGenero,'historial_clinico'=>$this->colHistorial
            };
            $upd($k,$colVar);
        }
        if(!$sets) return false;
        $sql="UPDATE {$this->tabla} SET ".implode(',',$sets)." WHERE id=:id";
        $st=$this->db->prepare($sql);
        return $st->execute($p);
    }

    public function cambiarEstado(int $id,string $estado): bool {
        if(!$this->c($this->colEstado)) return false;
        if(!in_array($estado,['activo','inactivo'],true)) return false;
        $st=$this->db->prepare("UPDATE {$this->tabla} SET {$this->colEstado}=:e WHERE id=:id");
        return $st->execute([':e'=>$estado,':id'=>$id]);
    }

    public function regenerarCodigoAcceso(int $id): ?string {
        if(!$this->c($this->colCodigoAcceso)) return null;
        $codigo=$this->generarCodigoUnico();
        $st=$this->db->prepare("UPDATE {$this->tabla} SET {$this->colCodigoAcceso}=:c WHERE id=:id");
        if($st->execute([':c'=>$codigo,':id'=>$id])) return $codigo;
        return null;
    }

    private function generarCodigoUnico(): string {
        do {
            $c=strtoupper(bin2hex(random_bytes(4)));
            $st=$this->db->prepare("SELECT id FROM {$this->tabla} WHERE {$this->colCodigoAcceso}=? LIMIT 1");
            $st->execute([$c]);
        } while($st->fetch());
        return $c;
    }

    public function getById(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM {$this->tabla} WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function getByDui(string $dui): ?array {
        if(empty($this->colDui)) return null;
        $dui = preg_replace('/[^0-9-]/','', $dui);
        if($dui==='') return null;
        $st = $this->db->prepare("SELECT * FROM {$this->tabla} WHERE {$this->colDui}=? LIMIT 1");
        $st->execute([$dui]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function getByDuiCodigo(string $dui,string $codigo): ?array {
        if(empty($this->colDui) || empty($this->colCodigoAcceso)) return null;
        $dui = preg_replace('/[^0-9-]/','', $dui);
        $codigo = strtoupper(trim($codigo));
        if($dui==='' || $codigo==='') return null;
        $sql = "SELECT id, {$this->colDui} dui, {$this->colCodigoAcceso} codigo_acceso, {$this->colNombre} nombre
                FROM {$this->tabla}
                WHERE {$this->colDui}=? AND {$this->colCodigoAcceso}=? LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([$dui,$codigo]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}
