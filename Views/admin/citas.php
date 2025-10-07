<?php /* Nueva interfaz de administración de citas con filtros y acciones */ ?>
<h1 class="h4 mb-3">Gestión de Citas</h1>

<?php if(!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger py-2 small"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body py-3">
    <div class="row g-3 align-items-end">
      <div class="col-sm-2">
        <label class="form-label mb-0 small">Estado</label>
        <select id="fEstado" class="form-select form-select-sm" onchange="refrescarCitas()">
          <option value="">Todos</option>
          <option value="pendiente">Pendiente</option>
          <option value="realizada">Realizada</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-sm-2">
        <label class="form-label mb-0 small">Fecha</label>
        <input type="date" id="fFecha" class="form-control form-control-sm" onchange="refrescarCitas()">
      </div>
      <div class="col-sm-3">
        <label class="form-label mb-0 small">Texto (ID, motivo, paciente)</label>
        <input type="text" id="fTexto" class="form-control form-control-sm" oninput="refrescarCitas()" placeholder="Buscar...">
      </div>
      <div class="col-sm-3">
        <label class="form-label mb-0 small">Psicólogo</label>
        <select id="fPs" class="form-select form-select-sm" onchange="refrescarCitas()">
          <option value="">Todos</option>
          <?php foreach($psicologos as $p): ?>
            <option value="<?= (int)$p['id'] ?>">#<?= (int)$p['id'] ?> <?= htmlspecialchars($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-2 d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">Limpiar</button>
        <button class="btn btn-outline-primary btn-sm" onclick="refrescarCitas()"><i class="fas fa-sync"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-sm table-striped align-middle" id="tablaCitasAdmin">
    <thead class="table-light"><tr>
      <th>ID</th><th>Paciente</th><th>Psicólogo</th><th>Fecha/Hora</th><th>Estado</th><th>Motivo</th><th>Acciones</th>
    </tr></thead>
    <tbody></tbody>
  </table>
</div>
<div id="citasVacio" class="alert alert-info py-2 d-none">Sin resultados con los filtros actuales.</div>

<!-- Modal Reasignar -->
<div class="modal fade" id="reasignarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Reasignar Cita <span id="reasignarId"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formReasignar" method="post" action="<?= url('admin','citas') ?>" onsubmit="return validarReasignar()">
          <input type="hidden" name="id" id="reasignarInputId">
          <input type="hidden" name="op" value="reasignar">
          <input type="hidden" name="fecha_hora" id="reasignarFechaHoraFinal">
          <div class="mb-2">
            <label class="form-label small">Psicólogo destino</label>
            <select name="id_psicologo" id="reasignarSelectPs" class="form-select form-select-sm" required onchange="cargarSlotsReasignar()">
              <option value="">Seleccione</option>
              <?php foreach($psicologos as $p): ?>
                <option value="<?= (int)$p['id'] ?>">#<?= (int)$p['id'] ?> <?= htmlspecialchars($p['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small">Fecha</label>
            <input type="date" id="reasignarFecha" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>" onchange="cargarSlotsReasignar()">
          </div>
          <input type="hidden" id="reasignarHora">
          <div class="mb-2">
            <label class="form-label small">Horas disponibles (30m)</label>
            <div id="reasignarSlots" class="small"><em class='text-muted'>Seleccione psicólogo y fecha</em></div>
          </div>
          <div class="alert alert-warning py-2 small" id="reasignarAviso" style="display:none"></div>
          <div class="text-end">
            <button class="btn btn-primary btn-sm">Confirmar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cancelar -->
<div class="modal fade" id="cancelarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Cancelar Cita <span id="cancelarId"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form method="post" action="<?= url('admin','citas') ?>" onsubmit="return confirmarCancelar()">
          <input type="hidden" name="id" id="cancelarInputId">
          <input type="hidden" name="op" value="cancelar">
          <label class="form-label small">Motivo</label>
          <input type="text" class="form-control form-control-sm" name="motivo" required maxlength="120">
          <div class="text-end mt-3"><button class="btn btn-danger btn-sm">Cancelar Cita</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Reprogramar -->
<div class="modal fade" id="reprogramarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Reprogramar Cita <span id="reprogramarId"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form method="post" action="<?= url('admin','citas') ?>" onsubmit="return validarReprogramar()">
          <input type="hidden" name="id" id="reprogramarInputId">
            <input type="hidden" name="op" value="reprogramar">
            <label class="form-label small">Nueva fecha/hora</label>
            <input type="datetime-local" name="fecha_hora" id="reprogramarFecha" class="form-control form-control-sm" required>
            <div class="form-text small">Minutos permitidos: 00 o 30</div>
            <div class="text-end mt-3"><button class="btn btn-primary btn-sm">Guardar</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const BASE='<?= RUTA ?>';
let datosCitas = <?= json_encode($citas) ?>;
function fetchCitas(){
  const p = new URLSearchParams();
  const e=document.getElementById('fEstado').value.trim(); if(e) p.append('estado',e);
  const f=document.getElementById('fFecha').value.trim(); if(f) p.append('fecha',f);
  const t=document.getElementById('fTexto').value.trim(); if(t) p.append('texto',t);
  const ps=document.getElementById('fPs').value.trim(); if(ps) p.append('ps',ps);
  p.append('ajax','list');
  return fetch(BASE+'index.php?url=admin/citas&'+p.toString()).then(r=>r.json()).then(j=>{ datosCitas=j.citas||[]; });
}
function formatearEstado(est){
  const map={pendiente:'warning text-dark',realizada:'success',cancelada:'secondary'}; const cls = map[est]||'light';
  return `<span class="badge bg-${cls}">${est}</span>`;
}
function renderTabla(){
  const tb=document.querySelector('#tablaCitasAdmin tbody'); tb.innerHTML='';
  if(!datosCitas.length){ document.getElementById('citasVacio').classList.remove('d-none'); return; }
  document.getElementById('citasVacio').classList.add('d-none');
  datosCitas.forEach(c=>{ const tr=document.createElement('tr'); tr.innerHTML=`<td>${c.id}</td>
      <td>${c.id_paciente}</td><td>${c.id_psicologo}</td><td>${c.fecha_hora}</td><td>${formatearEstado(c.estado_cita)}</td>
      <td class='small' style='max-width:200px'>${(c.motivo_consulta||'').replace(/</g,'&lt;')}</td>
      <td class='text-nowrap'>${accionesHtml(c)}</td>`; tb.appendChild(tr); });
}
function accionesHtml(c){
  const btnCancel = `<button class='btn btn-outline-danger btn-sm me-1' onclick='abrirCancelar(${c.id})' title='Cancelar'><i class="fas fa-ban"></i></button>`;
  const btnReprog = `<button class='btn btn-outline-primary btn-sm me-1' onclick='abrirReprogramar(${c.id},"${c.fecha_hora}")' title='Reprogramar'><i class="fas fa-sync"></i></button>`;
  let btnReasig; if(c.estado_cita==='realizada' || c.estado_cita==='cancelada'){
    btnReasig = `<button class='btn btn-outline-secondary btn-sm' disabled title='No disponible'><i class="fas fa-exchange-alt"></i></button>`;
  } else { btnReasig = `<button class='btn btn-outline-secondary btn-sm' onclick='abrirReasignar(${c.id},${c.id_psicologo},"${c.fecha_hora}")' title='Reasignar'><i class="fas fa-exchange-alt"></i></button>`; }
  return btnCancel+btnReprog+btnReasig;
}
function limpiarFiltros(){ ['fEstado','fFecha','fTexto','fPs'].forEach(id=>document.getElementById(id).value=''); refrescarCitas(); }
function refrescarCitas(){ fetchCitas().then(renderTabla).catch(()=>alert('Error cargando citas')); }
function abrirReasignar(id,actual,fh){
  document.getElementById('reasignarId').textContent='#'+id;
  document.getElementById('reasignarInputId').value=id;
  document.getElementById('reasignarSelectPs').value=actual;
  document.getElementById('reasignarAviso').style.display='none';
  const f=fh.substring(0,10);
  document.getElementById('reasignarFecha').value=f;
  document.getElementById('reasignarHora').value='';
  const cont = document.getElementById('reasignarSlots');
  cont.innerHTML='<em class="text-muted">Cargando...</em>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('reasignarModal')).show();
  // cargar slots inmediatamente
  setTimeout(()=>{ cargarSlotsReasignar(); },60);
}
function cargarSlotsReasignar(){ const ps=document.getElementById('reasignarSelectPs').value; const fecha=document.getElementById('reasignarFecha').value; const cont=document.getElementById('reasignarSlots'); if(!ps||!fecha){ cont.innerHTML='<em class="text-muted">Seleccione psicólogo y fecha</em>'; return;} cont.innerHTML='<em>Cargando...</em>'; fetch(BASE+'index.php?url=admin/citas&ajax=slots&ps='+encodeURIComponent(ps)+'&fecha='+encodeURIComponent(fecha)).then(r=>r.json()).then(j=>{ if(!j.slots||!j.slots.length){ cont.innerHTML='<span class="text-danger small">Sin horas libres</span>'; return;} cont.innerHTML=j.slots.map(h=>`<button type='button' class='btn btn-sm btn-outline-primary m-1' onclick='selSlotReasignar("${h}")'>${h}</button>`).join(''); }).catch(()=>{ cont.innerHTML='<span class="text-danger small">Error</span>'; }); }
function selSlotReasignar(h){ document.getElementById('reasignarHora').value=h; [...document.querySelectorAll('#reasignarSlots button')].forEach(b=>b.classList.remove('active')); const btn=[...document.querySelectorAll('#reasignarSlots button')].find(b=>b.textContent===h); if(btn) btn.classList.add('active'); }
function abrirCancelar(id){ document.getElementById('cancelarId').textContent='#'+id; document.getElementById('cancelarInputId').value=id; bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelarModal')).show(); }
function abrirReprogramar(id,fh){ document.getElementById('reprogramarId').textContent='#'+id; document.getElementById('reprogramarInputId').value=id; const loc=fh.replace(' ','T').substring(0,16); document.getElementById('reprogramarFecha').value=loc; bootstrap.Modal.getOrCreateInstance(document.getElementById('reprogramarModal')).show(); }
function validarReprogramar(){ const v=document.getElementById('reprogramarFecha').value; if(!v) return false; const m=parseInt(v.split(':')[1]); if(m!==0 && m!==30){ alert('Minutos deben ser 00 o 30'); return false;} return confirm('Confirmar reprogramación?'); }
function validarReasignar(){ const ps=document.getElementById('reasignarSelectPs').value; const fecha=document.getElementById('reasignarFecha').value; const hora=document.getElementById('reasignarHora').value; if(!ps||!fecha||!hora){ alert('Selecciona psicólogo, fecha y hora.'); return false;} document.getElementById('reasignarFechaHoraFinal').value=fecha+' '+hora+':00'; return confirm('Confirmar reasignación?'); }
function confirmarCancelar(){ return confirm('Cancelar definitivamente?'); }
 document.addEventListener('DOMContentLoaded', renderTabla);
</script>

<?php require __DIR__.'/../layout/footer.php'; ?>