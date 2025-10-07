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
  'fuera_horario'=>'La hora seleccionada no está dentro de tu horario configurado',
    'ocupado'=>'Ya existe una cita en ese horario',
    'ex'=>'Error interno, reintenta' 
  ];
  $msg = $map[$_GET['err']] ?? $_GET['err'];
?>
  <div class="alert alert-danger">Error: <?= htmlspecialchars($msg) ?><?php if($_GET['err']==='ex' && !empty($_SESSION['crear_cita_error'])){ echo '<br><small class="text-muted">Detalle: '.htmlspecialchars($_SESSION['crear_cita_error']).'</small>'; unset($_SESSION['crear_cita_error']); } ?></div>
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
        <div class="mt-2">
          <span class="badge bg-secondary">Intervalo fijo: 30 min</span>
          <input type="hidden" id="intervalo" value="30">
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

<!-- Se eliminan tablas separadas de pendientes/realizadas: ahora sólo tabla unificada -->
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
<?php // Mapa id -> nombre para mostrar nombres en la tabla
  $mapPac = [];
  foreach($pacientes as $p){
    $mapPac[$p['id']] = $p['nombre'] ?? $p['Nombre'] ?? ('Paciente #'.$p['id']);
  }
?>
<table class="table table-sm table-striped" id="tablaCitas">
  <thead><tr><th>ID</th><th>Paciente</th><th>Fecha/Hora</th><th>Estado</th><th>QR</th><th>Pago</th><th>Acciones</th></tr></thead>
  <tbody>
    <?php $pagoModel = new Pago(); $todas = array_merge($data['pendientes'],$data['realizadas']); ?>
    <?php foreach($todas as $c): ?>
      <?php $p = $pagoModel->obtenerPorCita((int)$c['id']); ?>
      <?php $nombrePac = $mapPac[$c['id_paciente']] ?? ('Paciente #'.$c['id_paciente']); ?>
      <tr data-estado="<?= htmlspecialchars($c['estado_cita']) ?>" data-fecha="<?= substr($c['fecha_hora'],0,10) ?>" data-paciente="<?= htmlspecialchars(strtolower($nombrePac)) ?>">
        <td><?= (int)$c['id'] ?></td>
        <td><?= htmlspecialchars($nombrePac) ?></td>
        <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
        <td><span class="badge bg-<?= $c['estado_cita']==='pendiente'?'warning text-dark':'info' ?>"><?= htmlspecialchars($c['estado_cita']) ?></span></td>
        <td class="text-center">
          <?php $img = htmlspecialchars($c['qr_code']); ?>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="mostrarQRModal('<?= $img ?>','CITA:<?= (int)$c['id'] ?>')" title="Ver QR">QR</button>
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
          <?php else: ?>
            <?php 
              $ticketM = new TicketPago();
              $ticket = $ticketM->obtenerPorPago($p['id']);
              if($ticket){
                $rutaTicket = RUTA . 'ticket/ver/'.$ticket['id'];
                echo '<a class="btn btn-sm btn-outline-primary" href="'.$rutaTicket.'">Ticket</a>';
              }
            ?>
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
  const interval = 30; // fijo
  const cont = document.getElementById('slots');
  cont.innerHTML = '<em>Cargando...</em>';
  const urlPrimary = BASE + 'index.php?url=psicologo/slots&fecha='+encodeURIComponent(fecha)+'&interval='+interval;
  const urlFallback = BASE + 'index.php?url=psicologo/citas&ajax=slots&fecha='+encodeURIComponent(fecha)+'&interval='+interval;
  const tryFetch = (u)=>fetch(u).then(r=> r.ok ? r.text():Promise.reject());
  tryFetch(urlPrimary).catch(()=>tryFetch(urlFallback))
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
    .catch(err=>{ if(err && err.parse){cont.innerHTML='<span class="text-danger">Respuesta no válida</span>'; console.error(err.txt);} else { cont.innerHTML='<span class="text-danger">Error cargando</span>'; } });
  return; // fin función
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

// Mostrar modal con el QR
function mostrarQRModal(rutaRel, contenido){
  try {
    let base = BASE;
    // Normalizar base (asegurar termina en /)
    if(!base.endsWith('/')) base += '';
    let ruta = rutaRel || '';
    // Quitar posible prefijo public/
    ruta = ruta.replace(/^public\//,'');
    // Prepend base si no es absoluta ni ya incluye BASE
    if(!/^https?:/i.test(ruta) && !ruta.startsWith(base)){
      ruta = base + ruta;
    }
    const img = document.getElementById('qrModalImg');
    const span = document.getElementById('qrModalCode');
    span.textContent = contenido || '';
    img.alt = 'QR '+contenido;
    img.removeAttribute('data-error');
    img.onerror = function(){
      if(img.getAttribute('data-error')==='2') return; // ya intentamos fallback
      const tried = img.getAttribute('data-error');
      if(!tried){
        // Primer fallo: intentar prefijo public/
        img.setAttribute('data-error','1');
        const clean = img.src.replace(/\?t=\d+/,'');
        if(!/\/public\//.test(clean)){
          let altSrc = clean.replace(/(qrcodes\/.*)$/,'public/$1');
          img.src = altSrc + (altSrc.includes('?')?'':'?t='+(Date.now()));
          return;
        }
      }
      // Segundo fallo: marcar error final
      img.setAttribute('data-error','2');
      img.classList.add('border-danger');
      img.style.opacity='0.4';
      span.textContent = 'No se pudo cargar la imagen ('+rutaRel+')';
    };
    img.onload = function(){
      img.classList.remove('border-danger');
      img.style.opacity='1';
    };
    img.src = ruta + (ruta.includes('?')?'':'?t='+(Date.now())); // evitar cache si se regeneró
    if(window.bootstrap && bootstrap.Modal){
      const modalEl = document.getElementById('qrModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    } else {
      alert('Bootstrap JS no cargado, abre en nueva pestaña');
      window.open(img.src,'_blank','noopener');
    }
  } catch(e){
    console.error('Error mostrando modal QR', e);
    alert('No se pudo mostrar el QR');
  }
}
</script>

<!-- Modal QR -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Código QR de la Cita</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <img id="qrModalImg" src="" alt="QR" class="img-fluid border p-1 mb-2" style="max-width:240px;background:#fff"/>
        <div class="small text-muted">Contenido: <span id="qrModalCode"></span></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="window.open(document.getElementById('qrModalImg').src,'_blank','noopener')">Abrir en nueva pestaña</button>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

      <script>
      // Fallback: definir mostrarQRModal si por alguna razón no quedó en el bloque anterior
      if (typeof mostrarQRModal !== 'function') {
        console.warn('Fallback: definiendo mostrarQRModal al final de la vista');
        function mostrarQRModal(rutaRel, contenido){
          try {
            let base = (typeof BASE!=='undefined'?BASE:'');
            if(base && !base.endsWith('/')) base += '';
            let ruta = (rutaRel||'').replace(/^public\//,'');
            if(!/^https?:/i.test(ruta) && base && !ruta.startsWith(base)) ruta = base + ruta;
            const img = document.getElementById('qrModalImg');
            const span = document.getElementById('qrModalCode');
            if(!img || !span){ alert('Modal no presente en el DOM'); return; }
            span.textContent = contenido || '';
            img.alt = 'QR '+contenido;
            img.onerror = function(){
              if(img.getAttribute('data-error')==='2') return;
              const tried = img.getAttribute('data-error');
              if(!tried){
                img.setAttribute('data-error','1');
                const clean = img.src.replace(/\?t=\d+/,'');
                if(!/\/public\//.test(clean)){
                  let altSrc = clean.replace(/(qrcodes\/.*)$/,'public/$1');
                  img.src = altSrc + (altSrc.includes('?')?'':'?t='+(Date.now()));
                  return;
                }
              }
              img.setAttribute('data-error','2');
              span.textContent='No se pudo cargar el QR';
              img.classList.add('border-danger');
            };
            img.src = ruta + (ruta.includes('?')?'':'?t='+(Date.now()));
            if(window.bootstrap && bootstrap.Modal){
              const modalEl = document.getElementById('qrModal');
              bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
              window.open(img.src,'_blank','noopener');
            }
          } catch(e){ console.error(e); alert('Error mostrando QR'); }
        }
      }
      </script>
