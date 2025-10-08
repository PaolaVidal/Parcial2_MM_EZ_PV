<?php /** @var array $tickets */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Mis Tickets de Pago</h2>
  <button class="btn btn-outline-primary btn-sm" onclick="abrirScannerTicket()">
    <i class="fas fa-qrcode me-1"></i> Escanear Ticket
  </button>
</div>
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

<!-- Modal para ver QR de ticket -->
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

<!-- Modal Scanner para verificar tickets de pago -->
<div class="modal fade" id="scannerModalTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title"><i class="fas fa-qrcode me-1"></i> Escanear Ticket de Pago</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" onclick="detenerScannerTicket()"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded position-relative" style="background:#111;min-height:320px;">
              <div id="qrReaderTicket" style="width:100%;height:100%;"></div>
              <div id="scannerEstadoTicket" class="position-absolute top-0 start-0 small text-white px-2 py-1" style="background:rgba(0,0,0,.4);border-bottom-right-radius:6px;">Inactivo</div>
            </div>
            <div class="mt-2 d-flex gap-2">
              <button id="btnScanStartTicket" class="btn btn-sm btn-primary" onclick="iniciarScannerTicket()"><i class="fas fa-play me-1"></i> Iniciar</button>
              <button id="btnScanStopTicket" class="btn btn-sm btn-outline-secondary" onclick="detenerScannerTicket()" disabled><i class="fas fa-stop"></i></button>
              <button id="btnScanRestartTicket" class="btn btn-sm btn-outline-secondary" onclick="reiniciarScannerTicket()" disabled><i class="fas fa-sync"></i></button>
            </div>
            <div class="mt-3">
              <label class="form-label small mb-1">Entrada manual</label>
              <div class="input-group input-group-sm">
                <input type="text" id="scanManualTicket" class="form-control" placeholder="PAGO:123" autocomplete="off">
                <button class="btn btn-outline-primary" onclick="procesarTokenTicket(document.getElementById('scanManualTicket').value.trim(),false)"><i class="fas fa-search"></i></button>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100 shadow-sm">
              <div class="card-header py-2"><strong>Información del Ticket</strong></div>
              <div class="card-body" id="scanResultadoTicket" style="min-height:150px;">
                <em class="text-muted">Aún sin escaneo...</em>
              </div>
              <div class="card-footer py-2">
                <div id="scanMensajeTicket" class="small"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="detenerScannerTicket()">Cerrar</button>
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

// ========================
// Scanner de Tickets QR
// ========================
let html5QrTicket = null;
let scannerActivoTicket = false;
let cooldownTicket = false;
let ultimoTokenTicket = '';

function ensureHtml5LibTicket(cb){
  if(window.Html5Qrcode){ cb(); return; }
  
  const s = document.createElement('script');
  s.src = BASE + 'public/js/html5-qrcode.min.js';
  s.async = true;
  
  s.onload = function() {
    cb();
  };
  
  s.onerror = function() {
    mostrarScanMsgTicket('❌ Error cargando librería QR local. Verifica public/js/html5-qrcode.min.js','danger');
    console.error('Error cargando html5-qrcode.min.js desde public/js/');
  };
  
  document.head.appendChild(s);
}

function abrirScannerTicket(){
  ultimoTokenTicket='';
  document.getElementById('scanResultadoTicket').innerHTML='<em class="text-muted">Aún sin escaneo...</em>';
  document.getElementById('scanManualTicket').value='';
  mostrarScanMsgTicket('','');
  if(window.bootstrap){ 
    bootstrap.Modal.getOrCreateInstance(document.getElementById('scannerModalTicket')).show(); 
  }
  
  // Precargar librería y mostrar estado
  mostrarScanMsgTicket('Cargando librería...','info');
  ensureHtml5LibTicket(()=>{
    mostrarScanMsgTicket('✓ Listo. Presiona "Iniciar" para escanear tickets.','success');
  });
}

function iniciarScannerTicket(){
  ensureHtml5LibTicket(()=>{
    if(scannerActivoTicket) return;
    const div = document.getElementById('qrReaderTicket');
    div.innerHTML='';
    
    // Limpiar instancia previa
    if(html5QrTicket){
      html5QrTicket.clear().catch(()=>{});
    }
    
    html5QrTicket = new Html5Qrcode('qrReaderTicket');
    mostrarScanMsgTicket('Solicitando acceso a cámara...','info');
    
    Html5Qrcode.getCameras().then(cams=>{
      if(!cams.length){ 
        mostrarScanMsgTicket('No hay cámaras disponibles','warning'); 
        return; 
      }
      
      // Preferir cámara trasera
      let camId = cams[0].id;
      const backCam = cams.find(c=>c.label && c.label.toLowerCase().includes('back'));
      if(backCam) camId = backCam.id;
      
      html5QrTicket.start(
        camId,
        {fps:10, qrbox: {width:230, height:230}, aspectRatio: 1.0},
        onScanTicket,
        ()=>{/* ignorar errores de lectura */}
      )
      .then(()=>{ 
        scannerActivoTicket=true; 
        actualizarEstadoScannerTicket('Activo - Escaneando'); 
        toggleScannerBtnsTicket();
        mostrarScanMsgTicket('Scanner activo. Apunta al código QR del ticket.','success');
      })
      .catch(e=>{
        const msg = e.toString();
        if(msg.includes('Permission') || msg.includes('NotAllowed')){
          mostrarScanMsgTicket('⚠️ Permiso denegado. Permite el acceso a la cámara.','danger');
        } else if(msg.includes('NotFound')){
          mostrarScanMsgTicket('⚠️ No se encontró cámara disponible.','danger');
        } else {
          mostrarScanMsgTicket('Error al iniciar: '+msg.substring(0,50),'danger');
        }
        console.error('Error al iniciar scanner ticket:', e);
      });
    }).catch(e=>{
      mostrarScanMsgTicket('Error enumerando cámaras: '+e,'danger');
      console.error('Error getCameras ticket:', e);
    });
  });
}

function detenerScannerTicket(){ 
  if(!scannerActivoTicket||!html5QrTicket) return; 
  html5QrTicket.stop()
    .then(()=>{
      scannerActivoTicket=false; 
      actualizarEstadoScannerTicket('Detenido'); 
      toggleScannerBtnsTicket();
      mostrarScanMsgTicket('Scanner detenido','info');
    })
    .catch(e=>{
      console.error('Error deteniendo ticket scanner:', e);
      scannerActivoTicket=false;
      toggleScannerBtnsTicket();
    }); 
}

function reiniciarScannerTicket(){ 
  detenerScannerTicket(); 
  setTimeout(()=>{
    if(html5QrTicket){
      html5QrTicket.clear().catch(()=>{});
    }
    iniciarScannerTicket();
  }, 500); 
}

function onScanTicket(decoded){ 
  console.log('QR detectado:', decoded);
  procesarTokenTicket(decoded.trim(), true); 
}

function procesarTokenTicket(token, desdeCam){
  console.log('Procesando token:', token, 'Desde cámara:', desdeCam);
  
  if(!token) {
    mostrarScanMsgTicket('Token vacío','warning');
    return;
  }
  
  if(!token.startsWith('PAGO:')){ 
    mostrarScanMsgTicket('⚠️ Formato inválido. Se esperaba PAGO:ID, recibido: ' + token.substring(0,20),'danger');
    console.warn('Token inválido:', token);
    return; 
  }
  
  if(desdeCam){
    if(token===ultimoTokenTicket) {
      console.log('Token duplicado, ignorando');
      return; // evitar repetir
    }
    ultimoTokenTicket=token;
    if(cooldownTicket) {
      console.log('En cooldown, ignorando');
      return;
    }
    cooldownTicket=true;
    setTimeout(()=>cooldownTicket=false, 1200);
  }
  
  const idPago = token.substring(5);
  console.log('ID Pago extraído:', idPago);
  mostrarScanMsgTicket('Consultando pago #'+idPago+'...','info');
  
  // Consultar datos del ticket por ID de pago (endpoint JSON)
  const url = BASE+'index.php?url=ticket/consultarPago/'+encodeURIComponent(idPago);
  console.log('Consultando URL:', url);
  
  fetch(url)
    .then(r=>{
      console.log('Respuesta status:', r.status);
      if(!r.ok) throw new Error('HTTP '+r.status);
      return r.text();
    })
    .then(text=>{
      console.log('Respuesta raw:', text.substring(0,200));
      try {
        return JSON.parse(text);
      } catch(e) {
        console.error('Error parseando JSON:', e);
        throw new Error('Respuesta no es JSON válido: ' + text.substring(0,100));
      }
    })
    .then(j=>{
      console.log('JSON parseado:', j);
      if(!j.ok){
        mostrarScanMsgTicket(j.msg || 'Error desconocido','danger');
        document.getElementById('scanResultadoTicket').innerHTML='<div class="alert alert-warning">'+htmlEscape(j.msg)+'</div>';
        return;
      }
      
      // Ticket encontrado - mostrar información
      const t = j.ticket;
      const p = j.pago;
      const estadoBadge = (p && p.estado_pago==='pagado') ? 'success' : 'warning text-dark';
      
      mostrarScanMsgTicket('✓ Ticket verificado','success');
      document.getElementById('scanResultadoTicket').innerHTML=`
        <div class="mb-2">
          <strong>Ticket ID:</strong> ${t.id}<br>
          <strong>Pago ID:</strong> ${idPago}<br>
          <strong>Monto:</strong> $${p ? Number(p.monto_total).toFixed(2) : 'N/A'}<br>
          <strong>Estado:</strong> <span class="badge bg-${estadoBadge}">${p ? htmlEscape(p.estado_pago) : 'desconocido'}</span><br>
          <small class="text-muted">Emisión: ${t.fecha_emision || 'N/A'}</small>
        </div>
        <div class="mt-3">
          <a href="${BASE}ticket/ver/${t.id}" class="btn btn-sm btn-primary" target="_blank">
            <i class="fas fa-eye me-1"></i> Ver Detalle
          </a>
          <a href="${BASE}ticket/pdf/${t.id}" class="btn btn-sm btn-danger ms-1" target="_blank">
            <i class="fas fa-file-pdf me-1"></i> PDF
          </a>
        </div>
      `;
      
      if(desdeCam){
        detenerScannerTicket();
      }
    })
    .catch(e=>{
      console.error('Error consultando ticket:', e);
      mostrarScanMsgTicket('❌ Error: ' + e.message,'danger');
      document.getElementById('scanResultadoTicket').innerHTML='<div class="alert alert-danger"><strong>Error:</strong> '+htmlEscape(e.message)+'</div>';
    });
}

function mostrarScanMsgTicket(msg,tipo){ 
  const el=document.getElementById('scanMensajeTicket'); 
  if(!el) return; 
  el.textContent=msg||''; 
  el.className='small fw-semibold'; 
  if(tipo==='danger') el.classList.add('text-danger');
  else if(tipo==='warning') el.classList.add('text-warning');
  else if(tipo==='success') el.classList.add('text-success');
  else if(tipo==='info') el.classList.add('text-info');
}

function actualizarEstadoScannerTicket(t){ 
  const e=document.getElementById('scannerEstadoTicket'); 
  if(e) e.textContent=t; 
}

function toggleScannerBtnsTicket(){ 
  document.getElementById('btnScanStartTicket').disabled=scannerActivoTicket; 
  document.getElementById('btnScanStopTicket').disabled=!scannerActivoTicket; 
  document.getElementById('btnScanRestartTicket').disabled=!scannerActivoTicket; 
}

// Limpiar al cerrar modal
document.getElementById('scannerModalTicket').addEventListener('hidden.bs.modal',()=>{ 
  detenerScannerTicket();
  setTimeout(()=>{
    if(html5QrTicket){
      html5QrTicket.clear().catch(()=>{});
      html5QrTicket = null;
    }
  }, 300);
});

// Función auxiliar para escapar HTML
function htmlEscape(str){
  const div = document.createElement('div');
  div.textContent = str || '';
  return div.innerHTML;
}
</script>
