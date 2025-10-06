<h2>Citas del Psicólogo</h2>
<?php if(isset($_GET['ok'])): ?><div class="alert alert-success">Operación realizada correctamente.</div><?php endif; ?>
<?php if(isset($_GET['err'])): 
  $map = [
    'datos'=>'Datos incompletos',
    'paciente'=>'Paciente no válido',
    'formato'=>'Formato de fecha/hora inválido',
    'minutos'=>'Los minutos deben ser 00 o 30',
    'pasado'=>'La hora seleccionada ya pasó',
    'horario'=>'Fuera del horario permitido (08:00-17:00)',
    'ocupado'=>'Ya existe una cita en ese horario',
    'ex'=>'Error interno, reintenta' 
  ];
  $msg = $map[$_GET['err']] ?? $_GET['err'];
?>
  <div class="alert alert-danger">Error: <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<div class="card mb-4">
  <div class="card-header">Crear nueva cita</div>
  <div class="card-body">
  <form method="post" action="<?= RUTA ?>index.php?url=psicologo/crear" class="row g-3" onsubmit="return prepararFechaHora()">
      <div class="col-md-4">
        <label class="form-label">Buscar Paciente</label>
        <input type="text" class="form-control mb-2" id="filtroPaciente" placeholder="Filtrar..." oninput="filtrarPacientes()">
        <select name="id_paciente" id="selectPaciente" class="form-select" required size="6" style="min-height:160px">
          <?php foreach($pacientes as $p): $nom = $p['nombre'] ?? $p['Nombre'] ?? ('Paciente #'.$p['id']); ?>
            <option value="<?= (int)$p['id'] ?>" data-text="<?= strtolower(htmlspecialchars($nom)) ?>"><?= htmlspecialchars($nom) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Fecha</label>
  <input type="date" id="fechaSel" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" onchange="validarFechaInput(); cargarSlots()" required>
        <div class="d-flex align-items-center mt-2 gap-2">
          <label class="form-label mb-0">Intervalo</label>
          <select id="intervalo" class="form-select form-select-sm w-auto" onchange="cargarSlots()">
            <option value="30">30 min</option>
            <option value="60">60 min</option>
          </select>
        </div>
        <div id="slots" class="mt-3 small">
          <em>Cargando slots...</em>
        </div>
        <input type="hidden" name="fecha_hora" id="fechaHoraFinal" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Motivo</label>
        <textarea name="motivo_consulta" class="form-control" rows="4" maxlength="255"></textarea>
        <div class="text-end mt-3">
          <button class="btn btn-primary">Crear cita</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <h4>Pendientes</h4>
    <table class="table table-sm table-striped">
  <thead><tr><th>ID</th><th>Paciente</th><th>Fecha/Hora</th><th>QR</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach($data['pendientes'] as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= htmlspecialchars($c['id_paciente']) ?></td>
            <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
            <td class="text-center">
              <?php $img = 'qrcodes/cita_id_'.$c['id'].'.png'; ?>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirQR('<?= htmlspecialchars($img) ?>')" title="Ver QR">&#128439;</button>
            </td>
            <td>
              <!-- Botón de token removido: ahora QR basado sólo en ID -->
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="col-md-6">
    <h4>Realizadas (últimas)</h4>
    <table class="table table-sm table-striped">
      <thead><tr><th>ID</th><th>Paciente</th><th>Fecha/Hora</th><th>Pago</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach($data['realizadas'] as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= htmlspecialchars($c['id_paciente']) ?></td>
            <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
            <td>
              <?php // Determinar pago
              $pagoModel = new Pago();
              $p = $pagoModel->obtenerPorCita((int)$c['id']);
              if($p && $p['estado_pago']==='pagado'){
                echo '<span class="badge bg-success">Pagado</span>';
              } else {
                echo '<span class="badge bg-warning text-dark">Pendiente</span>';
              }
              ?>
            </td>
            <td>
              <?php if(!$p || $p['estado_pago']!=='pagado'): ?>
                <form method="post" action="<?= RUTA ?>index.php?url=psicologo/pagar" style="display:inline" onsubmit="return confirm('Marcar pagado?');">
                  <input type="hidden" name="id_cita" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-success">Marcar Pagado</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<hr>
<h4>Listado de Citas</h4>
<div class="row g-3 mb-2">
  <div class="col-md-3">
    <label class="form-label mb-0">Estado</label>
    <select id="fEstado" class="form-select form-select-sm" onchange="filtrarTabla()">
      <option value="">Todos</option>
      <option value="pendiente">Pendiente</option>
      <option value="realizada">Realizada</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label mb-0">Fecha</label>
    <input type="date" id="fFecha" class="form-control form-control-sm" onchange="filtrarTabla()">
  </div>
  <div class="col-md-3">
    <label class="form-label mb-0">Buscar (ID / Paciente)</label>
    <input type="text" id="fTexto" class="form-control form-control-sm" oninput="filtrarTabla()" placeholder="Buscar...">
  </div>
  <div class="col-md-3 d-flex align-items-end justify-content-end">
    <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">Limpiar</button>
  </div>
</div>
<table class="table table-sm table-striped" id="tablaCitas">
  <thead><tr><th>ID</th><th>Paciente</th><th>Fecha/Hora</th><th>Estado</th><th>QR</th><th>Pago</th><th>Acciones</th></tr></thead>
  <tbody>
    <?php $pagoModel = new Pago(); ?>
    <?php foreach(array_merge($data['pendientes'],$data['realizadas']) as $c): ?>
      <?php $p = $pagoModel->obtenerPorCita((int)$c['id']); ?>
      <tr data-estado="<?= htmlspecialchars($c['estado_cita']) ?>" data-fecha="<?= substr($c['fecha_hora'],0,10) ?>" data-paciente="<?= htmlspecialchars($c['id_paciente']) ?>">
        <td><?= (int)$c['id'] ?></td>
        <td><?= htmlspecialchars($c['id_paciente']) ?></td>
        <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
        <td><span class="badge bg-<?= $c['estado_cita']==='pendiente'?'warning text-dark':'info' ?>"><?= htmlspecialchars($c['estado_cita']) ?></span></td>
        <td class="text-center">
          <?php $img = 'qrcodes/cita_id_'.$c['id'].'.png'; ?>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirQR('<?= htmlspecialchars($img) ?>')" title="Ver QR">&#128439;</button>
        </td>
        <td>
          <?php if($p && $p['estado_pago']==='pagado'): ?>
            <span class="badge bg-success">Pagado</span>
          <?php else: ?>
            <span class="badge bg-danger">Pendiente</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if(!$p || $p['estado_pago']!=='pagado'): ?>
            <form method="post" action="<?= RUTA ?>index.php?url=psicologo/pagar" style="display:inline" onsubmit="return confirm('Marcar pagado?');">
              <input type="hidden" name="id_cita" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-sm btn-success">Pagar</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
// Base absoluta generada desde el front controller
const BASE = '<?= RUTA ?>';
// copyToken removido: ya no se usan tokens, QR contiene CITA:ID
function abrirQR(ruta){ window.open(ruta,'_blank','noopener'); }
function filtrarPacientes(){
  const f = document.getElementById('filtroPaciente').value.toLowerCase();
  const sel = document.getElementById('selectPaciente');
  [...sel.options].forEach(o=>{ o.hidden = f && !o.dataset.text.includes(f); });
}
function cargarSlots(){
  const fecha = document.getElementById('fechaSel').value;
  const interval = document.getElementById('intervalo').value;
  const cont = document.getElementById('slots');
  cont.innerHTML = '<em>Cargando...</em>';
  // Intento principal: ajax=slots sobre la misma acción (evita problemas de routing)
  const url = BASE + 'index.php?url=psicologo/citas&ajax=slots&fecha='+encodeURIComponent(fecha)+'&interval='+interval;
  fetch(url).then(r=> r.ok ? r.text():Promise.reject({r}))
    .then(txt=>{ let j; try{ j=JSON.parse(txt);}catch(e){ throw {parse:true,txt}; } return j; })
    .then(j=>{
      if(j.error){
        let msg = 'Error';
        if(j.error==='fecha_pasada') msg='La fecha ya pasó';
        else if(j.error==='formato_fecha') msg='Formato de fecha inválido';
        cont.innerHTML = '<span class="text-danger">'+msg+'</span>';
        return;
      }
      if(!j.slots || !j.slots.length){ cont.innerHTML='<span class="text-muted">Sin horas libres</span>'; return; }
      cont.innerHTML = j.slots.map(h=>`<button type="button" class="btn btn-sm btn-outline-primary m-1" onclick="selSlot('${h}')">${h}</button>`).join('');
    })
    .catch(err=>{ 
      if(err && err.parse){
        cont.innerHTML='<span class="text-danger">Respuesta no válida</span>'; 
        console.error('Respuesta HTML/devuelta en lugar de JSON:', err.txt.substring(0,200));
      } else {
        cont.innerHTML='<span class="text-danger">Error cargando</span>';
        console.error('Slots error',err);
      }
    });
}
function selSlot(h){
  const fecha = document.getElementById('fechaSel').value;
  document.getElementById('fechaHoraFinal').value = fecha + ' ' + h + ':00';
  // Marcar visualmente
  [...document.querySelectorAll('#slots button')].forEach(b=>b.classList.remove('active'));
  const btn = [...document.querySelectorAll('#slots button')].find(b=>b.textContent===h); if(btn) btn.classList.add('active');
}
function prepararFechaHora(){
  const fecha = document.getElementById('fechaSel').value;
  const hoy = (new Date()).toISOString().slice(0,10);
  if(!fecha || fecha < hoy){ alert('Fecha inválida.'); return false; }
  if(!document.getElementById('fechaHoraFinal').value){ alert('Selecciona una hora.'); return false; }
  return true;
}
function validarFechaInput(){
  const inp = document.getElementById('fechaSel');
  const hoy = (new Date()).toISOString().slice(0,10);
  if(inp.value < hoy){ inp.value = hoy; }
}
document.addEventListener('DOMContentLoaded', validarFechaInput);
document.addEventListener('DOMContentLoaded', cargarSlots);
function filtrarTabla(){
  const est = document.getElementById('fEstado').value;
  const fecha = document.getElementById('fFecha').value;
  const txt = document.getElementById('fTexto').value.toLowerCase();
  document.querySelectorAll('#tablaCitas tbody tr').forEach(tr=>{
    const okEstado = !est || tr.dataset.estado===est;
    const okFecha = !fecha || tr.dataset.fecha===fecha;
    const id = tr.children[0].textContent;
    const pac = tr.dataset.paciente.toLowerCase();
    const okTxt = !txt || id.includes(txt) || pac.includes(txt);
    tr.style.display = (okEstado && okFecha && okTxt)?'':'none';
  });
}
function limpiarFiltros(){
  document.getElementById('fEstado').value='';
  document.getElementById('fFecha').value='';
  document.getElementById('fTexto').value='';
  filtrarTabla();
}
</script>
