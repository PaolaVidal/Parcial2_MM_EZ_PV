<?php /** @var array $tickets */ ?>
<h2 class="mb-3">Mis Tickets de Pago</h2>
<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end small">
      <div class="col-md-2">
        <label class="form-label mb-0">Estado Pago</label>
        <select id="fEstadoTicket" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="pagado">Pagado</option>
          <option value="pendiente">Pendiente</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Desde</label>
        <input type="date" id="fDesde" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Hasta</label>
        <input type="date" id="fHasta" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="form-label mb-0">Buscar (paciente / ID)</label>
        <input type="text" id="fTextoTicket" class="form-control form-control-sm" placeholder="Ej: Ana / 15">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Cita (ID)</label>
        <input type="number" id="fCita" class="form-control form-control-sm" min="1">
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosTickets()">Limpiar</button>
      </div>
    </div>
  </div>
</div>
<?php if(empty($tickets)): ?>
  <div class="alert alert-info">Aún no hay tickets generados.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle" id="tablaTickets">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Fecha Emisión</th>
          <th>Cita</th>
          <th>Paciente</th>
          <th>Monto</th>
          <th>Estado Pago</th>
          <th>QR</th>
          <th>Ver</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($tickets as $t): ?>
        <?php 
          $badge = $t['estado_pago']==='pagado' ? 'success' : 'warning text-dark';
          $rutaVer = RUTA.'ticket/ver/'.(int)$t['id'];
          $qr = htmlspecialchars($t['qr_code'] ?? '');
          $qrContenido = 'PAGO:'.$t['id_pago'];
        ?>
        <tr data-estado="<?= htmlspecialchars($t['estado_pago']) ?>" data-fecha="<?= substr($t['fecha_emision'],0,10) ?>" data-paciente="<?= htmlspecialchars(strtolower($t['nombre_paciente'])) ?>" data-cita="<?= (int)$t['id_cita'] ?>">
          <td class="fw-semibold"><?= (int)$t['id'] ?></td>
          <td><span class="d-block small"><?= htmlspecialchars($t['fecha_emision']) ?></span></td>
          <td>#<?= (int)$t['id_cita'] ?><br><small class="text-muted"><?= htmlspecialchars($t['fecha_hora']) ?></small></td>
          <td><?= htmlspecialchars($t['nombre_paciente']) ?></td>
          <td>$<?= number_format((float)$t['monto_total'],2) ?></td>
          <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($t['estado_pago']) ?></span></td>
          <td>
            <button class="btn btn-outline-secondary btn-sm" onclick="mostrarQRModalTicket('<?= $qr ?>','<?= $qrContenido ?>')">QR</button>
          </td>
          <td class="text-nowrap">
            <a class="btn btn-primary btn-sm" href="<?= $rutaVer ?>" title="Ver detalle"><i class="fas fa-eye"></i></a>
            <a class="btn btn-danger btn-sm" href="<?= RUTA ?>ticket/pdf/<?= (int)$t['id'] ?>" target="_blank" title="Descargar PDF"><i class="fas fa-file-pdf"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="small text-muted" id="resumenTickets"></div>
  </div>
<?php endif; ?>

<!-- Reusar modal QR de citas si existe; si no, definimos uno específico -->
<div class="modal fade" id="qrModalTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">QR del Ticket</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <img id="qrTicketImgModal" src="" alt="QR" class="img-fluid border p-1 mb-2" style="max-width:240px;background:#fff"/>
        <div class="small text-muted">Contenido: <span id="qrTicketCode"></span></div>
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>
const BASE = '<?= RUTA ?>';
function mostrarQRModalTicket(rutaRel, contenido){
  try {
    let ruta = (rutaRel||'').trim();
    if(!ruta) { // intentar construir por id_pago dentro del contenido
      const idPago = (contenido||'').replace(/^PAGO:/,'');
      ruta = 'public/qrcodes/ticket_'+idPago+'.png';
    }
    ruta = ruta.replace(/^\/+/,'');
    if(!/^https?:/i.test(ruta)){
      if(!ruta.startsWith('public/')){
        if(ruta.startsWith('qrcodes/')) ruta = 'public/'+ruta; else if(!ruta.includes('/')) ruta = 'public/qrcodes/'+ruta;
      }
      ruta = BASE + ruta; // absoluta
    }
    const img = document.getElementById('qrTicketImgModal');
    const span = document.getElementById('qrTicketCode');
    span.textContent = contenido || '';
    img.onerror = function(){
      if(img.dataset.altTried) { span.textContent='No se pudo cargar.'; return; }
      img.dataset.altTried='1';
      let alt = img.src.replace(/public\//,'');
      if(alt!==img.src) img.src = alt + (alt.includes('?')?'':'?t='+(Date.now())); else span.textContent='Error cargando';
    };
    img.src = ruta + (ruta.includes('?')?'':'?t='+(Date.now()));
    if(window.bootstrap && bootstrap.Modal){
      bootstrap.Modal.getOrCreateInstance(document.getElementById('qrModalTicket')).show();
    }
  } catch(e){ console.error(e); alert('Error mostrando QR'); }
}

// --- Filtros cliente ---
const fEstado = document.getElementById('fEstadoTicket');
const fDesde = document.getElementById('fDesde');
const fHasta = document.getElementById('fHasta');
const fTexto = document.getElementById('fTextoTicket');
const fCita = document.getElementById('fCita');
const resumen = document.getElementById('resumenTickets');
[fEstado,fDesde,fHasta,fTexto,fCita].forEach(el=> el.addEventListener('input', filtrarTickets));

function filtrarTickets(){
  const est = fEstado.value.trim().toLowerCase();
  const d1 = fDesde.value; const d2 = fHasta.value;
  const txt = fTexto.value.trim().toLowerCase();
  const cita = fCita.value.trim();
  let visibles = 0, total=0;
  document.querySelectorAll('#tablaTickets tbody tr').forEach(tr=>{
    total++;
    const estRow = tr.dataset.estado.toLowerCase();
    const fechaRow = tr.dataset.fecha; // yyyy-mm-dd
    const pacRow = tr.dataset.paciente;
    const citaRow = tr.dataset.cita;
    let ok = true;
    if(est && estRow!==est) ok=false;
    if(ok && d1 && fechaRow < d1) ok=false;
    if(ok && d2 && fechaRow > d2) ok=false;
    if(ok && txt && !(pacRow.includes(txt) || tr.firstElementChild.textContent.includes(txt))) ok=false;
    if(ok && cita && citaRow !== cita) ok=false;
    tr.style.display = ok?'' : 'none';
    if(ok) visibles++;
  });
  if(resumen) resumen.textContent = visibles + ' de ' + total + ' tickets mostrados';
}
function limpiarFiltrosTickets(){ fEstado.value=''; fDesde.value=''; fHasta.value=''; fTexto.value=''; fCita.value=''; filtrarTickets(); }
document.addEventListener('DOMContentLoaded', filtrarTickets);
</script>
