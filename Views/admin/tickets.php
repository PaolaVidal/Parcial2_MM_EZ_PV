<?php /** @var array $tickets */ ?>
<h1 class="h4 mb-3">Gestión de Tickets de Pago</h1>

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
      <div class="col-md-2">
        <label class="form-label mb-0">Buscar Paciente</label>
        <input type="text" id="fPaciente" class="form-control form-control-sm" placeholder="Nombre o ID">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Buscar Psicólogo</label>
        <input type="text" id="fPsicologo" class="form-control form-control-sm" placeholder="Nombre o ID">
      </div>
      <div class="col-md-1">
        <label class="form-label mb-0">ID Cita</label>
        <input type="number" id="fCita" class="form-control form-control-sm" min="1">
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosTickets()" title="Limpiar filtros">
          <i class="fas fa-eraser"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<?php if(empty($tickets)): ?>
  <div class="alert alert-info py-2">No hay tickets registrados en el sistema.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-hover align-middle" id="tablaTickets">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Fecha Emisión</th>
          <th>Cita</th>
          <th>Paciente</th>
          <th>Psicólogo</th>
          <th>Monto</th>
          <th>Estado Pago</th>
          <th>QR</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($tickets as $t): ?>
        <?php 
          $badge = $t['estado_pago']==='pagado' ? 'success' : 'warning text-dark';
          $rutaVer = RUTA.'ticket/ver/'.(int)$t['id'];
          $qr = htmlspecialchars($t['qr_code'] ?? '');
          $qrContenido = 'PAGO:'.$t['id_pago'];
          $psicologoNombre = htmlspecialchars($t['psicologo_nombre'] ?? 'N/A');
          $pacienteNombre = htmlspecialchars($t['nombre_paciente'] ?? 'Sin nombre');
        ?>
        <tr data-estado="<?= htmlspecialchars($t['estado_pago']) ?>" 
            data-fecha="<?= substr($t['fecha_emision'],0,10) ?>" 
            data-paciente="<?= htmlspecialchars(strtolower($pacienteNombre)) ?>" 
            data-psicologo="<?= htmlspecialchars(strtolower($psicologoNombre)) ?>"
            data-id-psicologo="<?= (int)$t['id_psicologo'] ?>"
            data-id-paciente="<?= (int)$t['id_paciente'] ?>"
            data-cita="<?= (int)$t['id_cita'] ?>">
          <td class="fw-semibold">#<?= (int)$t['id'] ?></td>
          <td><span class="small"><?= htmlspecialchars(substr($t['fecha_emision'],0,16)) ?></span></td>
          <td>
            <span class="badge bg-secondary">#<?= (int)$t['id_cita'] ?></span>
            <br><small class="text-muted"><?= htmlspecialchars(substr($t['fecha_hora'],0,16)) ?></small>
          </td>
          <td>
            <span class="d-block"><?= $pacienteNombre ?></span>
            <small class="text-muted">ID: <?= (int)$t['id_paciente'] ?></small>
          </td>
          <td>
            <span class="d-block"><?= $psicologoNombre ?></span>
            <small class="text-muted">ID: <?= (int)$t['id_psicologo'] ?></small>
          </td>
          <td class="fw-semibold">$<?= number_format((float)$t['monto_total'],2) ?></td>
          <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($t['estado_pago']) ?></span></td>
          <td>
            <button class="btn btn-outline-secondary btn-sm" onclick="mostrarQRModalTicket('<?= $qr ?>','<?= $qrContenido ?>')" title="Ver QR">
              <i class="fas fa-qrcode"></i>
            </button>
          </td>
          <td>
            <a class="btn btn-primary btn-sm" href="<?= $rutaVer ?>" title="Ver detalle">
              <i class="fas fa-eye"></i> Ver
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="small text-muted mt-2" id="resumenTickets"></div>
  </div>
<?php endif; ?>

<!-- Modal QR del Ticket -->
<div class="modal fade" id="qrModalTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">QR del Ticket de Pago</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <img id="qrTicketImgModal" src="" alt="QR" class="img-fluid border p-2 mb-2" style="max-width:280px;background:#fff"/>
        <div class="small text-muted mb-2">
          <strong>Contenido:</strong> <span id="qrTicketCode" class="font-monospace"></span>
        </div>
        <div class="alert alert-info py-2 small mb-0">
          <i class="fas fa-info-circle"></i> Este código QR puede ser escaneado para verificar el pago.
        </div>
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
    if(!ruta) {
      // Construir ruta a partir del contenido PAGO:id
      const idPago = (contenido||'').replace(/^PAGO:/,'');
      ruta = 'public/qrcodes/ticket_'+idPago+'.png';
    }
    
    // Normalizar ruta
    ruta = ruta.replace(/^\/+/,'');
    if(!/^https?:/i.test(ruta)){
      if(!ruta.startsWith('public/')){
        if(ruta.startsWith('qrcodes/')) {
          ruta = 'public/'+ruta;
        } else if(!ruta.includes('/')) {
          ruta = 'public/qrcodes/'+ruta;
        }
      }
      ruta = BASE + ruta;
    }
    
    const img = document.getElementById('qrTicketImgModal');
    const span = document.getElementById('qrTicketCode');
    span.textContent = contenido || '';
    
    img.onerror = function(){
      if(img.dataset.altTried) { 
        span.textContent='No se pudo cargar la imagen del QR'; 
        return; 
      }
      img.dataset.altTried='1';
      let alt = img.src.replace(/public\//,'');
      if(alt!==img.src) {
        img.src = alt + (alt.includes('?')?'&':'?')+'t='+(Date.now());
      } else {
        span.textContent='Error cargando QR';
      }
    };
    
    img.src = ruta + (ruta.includes('?')?'&':'?')+'t='+(Date.now());
    delete img.dataset.altTried;
    
    if(window.bootstrap && bootstrap.Modal){
      bootstrap.Modal.getOrCreateInstance(document.getElementById('qrModalTicket')).show();
    }
  } catch(e){ 
    console.error('Error mostrando QR:', e); 
    alert('Error mostrando el código QR'); 
  }
}

// --- Sistema de Filtros ---
const fEstado = document.getElementById('fEstadoTicket');
const fDesde = document.getElementById('fDesde');
const fHasta = document.getElementById('fHasta');
const fPaciente = document.getElementById('fPaciente');
const fPsicologo = document.getElementById('fPsicologo');
const fCita = document.getElementById('fCita');
const resumen = document.getElementById('resumenTickets');

[fEstado, fDesde, fHasta, fPaciente, fPsicologo, fCita].forEach(el => {
  el.addEventListener('input', filtrarTickets);
});

function filtrarTickets(){
  const est = fEstado.value.trim().toLowerCase();
  const d1 = fDesde.value; 
  const d2 = fHasta.value;
  const txtPac = fPaciente.value.trim().toLowerCase();
  const txtPs = fPsicologo.value.trim().toLowerCase();
  const cita = fCita.value.trim();
  
  let visibles = 0, total = 0;
  let totalMonto = 0, visibleMonto = 0;
  
  document.querySelectorAll('#tablaTickets tbody tr').forEach(tr => {
    total++;
    const estRow = tr.dataset.estado.toLowerCase();
    const fechaRow = tr.dataset.fecha; // yyyy-mm-dd
    const pacRow = tr.dataset.paciente;
    const psRow = tr.dataset.psicologo;
    const idPacRow = tr.dataset.idPaciente;
    const idPsRow = tr.dataset.idPsicologo;
    const citaRow = tr.dataset.cita;
    
    // Extraer monto de la celda
    const montoCell = tr.querySelector('td:nth-child(6)');
    const monto = montoCell ? parseFloat(montoCell.textContent.replace(/[$,]/g,'')) : 0;
    totalMonto += monto;
    
    let ok = true;
    
    // Filtro por estado
    if(est && estRow !== est) ok = false;
    
    // Filtro por rango de fechas
    if(ok && d1 && fechaRow < d1) ok = false;
    if(ok && d2 && fechaRow > d2) ok = false;
    
    // Filtro por paciente (nombre o ID)
    if(ok && txtPac) {
      if(!pacRow.includes(txtPac) && !idPacRow.includes(txtPac)) {
        ok = false;
      }
    }
    
    // Filtro por psicólogo (nombre o ID)
    if(ok && txtPs) {
      if(!psRow.includes(txtPs) && !idPsRow.includes(txtPs)) {
        ok = false;
      }
    }
    
    // Filtro por ID de cita
    if(ok && cita && citaRow !== cita) ok = false;
    
    tr.style.display = ok ? '' : 'none';
    if(ok) {
      visibles++;
      visibleMonto += monto;
    }
  });
  
  if(resumen) {
    resumen.innerHTML = `
      Mostrando <strong>${visibles}</strong> de <strong>${total}</strong> tickets
      &nbsp;|&nbsp; Monto visible: <strong>$${visibleMonto.toFixed(2)}</strong>
      &nbsp;|&nbsp; Monto total: <strong>$${totalMonto.toFixed(2)}</strong>
    `;
  }
}

function limpiarFiltrosTickets(){ 
  fEstado.value = ''; 
  fDesde.value = ''; 
  fHasta.value = ''; 
  fPaciente.value = ''; 
  fPsicologo.value = ''; 
  fCita.value = ''; 
  filtrarTickets(); 
}

// Ejecutar filtros al cargar la página
document.addEventListener('DOMContentLoaded', filtrarTickets);
</script>

<?php require __DIR__.'/../layout/footer.php'; ?>
