<h2>Citas del Psicólogo</h2>

<!-- Mensajes de éxito -->
<?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i>
    <?php
    $okMsgs = [
      'finalizada' => 'Cita finalizada correctamente',
      'cancelada' => 'Cita cancelada correctamente',
      'pagado' => 'Pago registrado correctamente'
    ];
    echo $okMsgs[$_GET['ok']] ?? 'Operación realizada correctamente';
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Mensajes informativos -->
<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-info alert-dismissible fade show">
    <i class="fas fa-info-circle me-1"></i>
    <?php
    $infoMsgs = [
      'ya_cancelada' => 'Esta cita ya está cancelada'
    ];
    echo $infoMsgs[$_GET['msg']] ?? htmlspecialchars($_GET['msg']);
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Mensajes de error -->
<?php if (isset($_GET['err'])):
  $map = [
    'datos' => 'Datos incompletos',
    'paciente' => 'Paciente no válido',
    'formato' => 'Formato de fecha/hora inválido',
    'minutos' => 'Los minutos deben ser 00 o 30',
    'pasado' => 'La hora seleccionada ya pasó',
    'horario' => 'Fuera del horario permitido (08:00-17:00)',
    'fuera_horario' => 'La hora seleccionada no está dentro de tu horario configurado',
    'ocupado' => 'Ya existe una cita en ese horario',
    'ex' => 'Error interno, reintenta',
    'nf' => 'Cita no encontrada',
    'ya_realizada' => 'No se puede cancelar una cita que ya está realizada',
    'cancel' => 'Error al cancelar la cita',
    'con_evaluaciones' => 'No se puede cancelar una cita que ya tiene evaluaciones registradas. Si deseas terminar la atención, usa el botón "Finalizar Cita".'
  ];
  $msg = $map[$_GET['err']] ?? $_GET['err'];
  ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-1"></i>
    Error: <?= htmlspecialchars($msg) ?>
    <?php if ($_GET['err'] === 'ex' && !empty($_SESSION['crear_cita_error'])) {
      echo '<br><small class="text-muted">Detalle: ' . htmlspecialchars($_SESSION['crear_cita_error']) . '</small>';
      unset($_SESSION['crear_cita_error']);
    } ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<div class="card mb-4">
  <div class="card-header">Crear nueva cita</div>
  <div class="card-body">
    <form method="post" action="<?= RUTA ?>index.php?url=psicologo/crear" class="row g-3"
      onsubmit="return prepararFechaHora()">
      <div class="col-md-4">
        <label class="form-label">Paciente (solo activos)</label>
        <select name="id_paciente" class="form-select" required>
          <option value="">-- Seleccione --</option>
          <?php foreach ($pacientes as $p):
            // Excluir pacientes inactivos si viene el campo estado
            if (isset($p['estado']) && $p['estado'] !== 'activo')
              continue;
            $nom = $p['nombre'] ?? $p['Nombre'] ?? ('Paciente #' . $p['id']); ?>
            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($nom) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Fecha</label>
        <input type="date" id="fechaSel" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"
          onchange="validarFechaInput(); cargarSlots()" required>
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
<div class="d-flex align-items-center justify-content-between mb-2">
  <h4 class="mb-0">Listado de Citas</h4>
  <div>
    <button class="btn btn-outline-secondary btn-sm me-2" onclick="abrirScannerModal()"><i
        class="fas fa-qrcode me-1"></i>Escanear QR</button>
  </div>
</div>
<div class="row g-3 mb-2">
  <div class="col-md-3">
    <label class="form-label mb-0">Estado</label>
    <select id="fEstado" class="form-select form-select-sm" onchange="filtrarTabla()">
      <option value="">Todos</option>
      <option value="pendiente">Pendiente</option>
      <option value="realizada">Realizada</option>
      <option value="cancelada">Cancelada</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label mb-0">Fecha</label>
    <input type="date" id="fFecha" class="form-control form-control-sm" onchange="filtrarTabla()">
  </div>
  <div class="col-md-3">
    <label class="form-label mb-0">Buscar (ID / Paciente)</label>
    <input type="text" id="fTexto" class="form-control form-control-sm" oninput="filtrarTabla()"
      placeholder="Buscar...">
  </div>
  <div class="col-md-3 d-flex align-items-end justify-content-end">
    <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">Limpiar</button>
  </div>
</div>
<?php // Mapa id -> nombre para mostrar nombres en la tabla
$mapPac = [];
foreach ($pacientes as $p) {
  $mapPac[$p['id']] = $p['nombre'] ?? $p['Nombre'] ?? ('Paciente #' . $p['id']);
}
?>
<table class="table table-sm table-striped" id="tablaCitas">
  <thead>
    <tr>
      <th>ID</th>
      <th>Paciente</th>
      <th>Fecha/Hora</th>
      <th>Estado</th>
      <th>QR</th>
      <th>Pago</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php $pagoModel = new Pago();
    $todas = array_merge($data['pendientes'], $data['realizadas'], $data['canceladas']); ?>
    <?php foreach ($todas as $c): ?>
      <?php $p = $pagoModel->obtenerPorCita((int) $c['id']); ?>
      <?php $nombrePac = $mapPac[$c['id_paciente']] ?? ('Paciente #' . $c['id_paciente']); ?>
      <tr data-estado="<?= htmlspecialchars($c['estado_cita']) ?>" data-fecha="<?= substr($c['fecha_hora'], 0, 10) ?>"
        data-paciente="<?= htmlspecialchars(strtolower($nombrePac)) ?>">
        <td><?= (int) $c['id'] ?></td>
        <td><?= htmlspecialchars($nombrePac) ?></td>
        <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
        <td><span
            class="badge bg-<?= $c['estado_cita'] === 'pendiente' ? 'warning text-dark' : ($c['estado_cita'] === 'realizada' ? 'success' : 'danger') ?>"><?= htmlspecialchars($c['estado_cita']) ?></span>
        </td>
        <td class="text-center">
          <?php $img = htmlspecialchars($c['qr_code']); ?>
          <button type="button" class="btn btn-outline-secondary btn-sm"
            onclick="mostrarQRModal('<?= $img ?>','CITA:<?= (int) $c['id'] ?>')" title="Ver QR">QR</button>
        <td>
          <?php if ($p && $p['estado_pago'] === 'pagado'): ?>
            <span class="badge bg-success">Pagado</span>
          <?php else: ?>
            <span class="badge bg-danger">Pendiente</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="d-flex flex-wrap gap-1">
            <!-- Botón Atender/Ver Cita -->
            <?php if ($c['estado_cita'] === 'pendiente'): ?>
              <a href="<?= RUTA ?>index.php?url=psicologo/atenderCita&id=<?= (int) $c['id'] ?>"
                class="btn btn-sm btn-primary" title="Atender cita">
                <i class="fas fa-user-md"></i> Atender
              </a>
            <?php elseif ($c['estado_cita'] === 'realizada'): ?>
              <a href="<?= RUTA ?>index.php?url=psicologo/atenderCita&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-info"
                title="Ver detalles">
                <i class="fas fa-eye"></i> Ver
              </a>
            <?php endif; ?>

            <!-- Botón Cancelar (solo para pendientes SIN evaluaciones) -->
            <?php if ($c['estado_cita'] === 'pendiente'): ?>
              <?php
              $countEval = $c['count_evaluaciones'] ?? 0;
              if ($countEval === 0):
                ?>
                <form method="post" action="<?= RUTA ?>index.php?url=psicologo/cancelarCita" style="display:inline"
                  onsubmit="return confirm('¿Seguro que deseas cancelar esta cita? Esta acción no se puede deshacer.');">
                  <input type="hidden" name="id_cita" value="<?= (int) $c['id'] ?>">
                  <button class="btn btn-sm btn-danger" title="Cancelar cita">
                    <i class="fas fa-times"></i> Cancelar
                  </button>
                </form>
              <?php else: ?>
                <span class="badge bg-secondary"
                  title="No se puede cancelar porque ya tiene <?= $countEval ?> evaluación(es)">
                  <i class="fas fa-clipboard-check"></i> <?= $countEval ?> eval.
                </span>
              <?php endif; ?>
            <?php endif; ?>

            <!-- Botón Pagar -->
            <?php if ($c['estado_cita'] === 'realizada' && (!$p || $p['estado_pago'] !== 'pagado')): ?>
              <form method="post" action="<?= RUTA ?>index.php?url=psicologo/pagar" style="display:inline"
                onsubmit="return confirm('¿Marcar como pagado?');">
                <input type="hidden" name="id_cita" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-sm btn-success" title="Registrar pago">
                  <i class="fas fa-dollar-sign"></i> Pagar
                </button>
              </form>
            <?php elseif ($p && $p['estado_pago'] === 'pagado'): ?>
              <?php
              $ticketM = new TicketPago();
              $ticket = $ticketM->obtenerPorPago($p['id']);
              if ($ticket) {
                $rutaTicket = RUTA . 'ticket/ver/' . $ticket['id'];
                echo '<a class="btn btn-sm btn-outline-secondary" href="' . $rutaTicket . '" title="Ver ticket"><i class="fas fa-ticket-alt"></i> Ticket</a>';
              }
              ?>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
  // Base absoluta generada desde el front controller
  const BASE = '<?= RUTA ?>';
  // copyToken removido: ya no se usan tokens, QR contiene CITA:ID
  function abrirQR(ruta) { window.open(ruta, '_blank', 'noopener'); }
  function cargarSlots() {
    const fecha = document.getElementById('fechaSel').value;
    const interval = 30; // fijo
    const cont = document.getElementById('slots');
    cont.innerHTML = '<em>Cargando...</em>';
    const urlPrimary = BASE + 'index.php?url=psicologo/slots&fecha=' + encodeURIComponent(fecha) + '&interval=' + interval;
    const urlFallback = BASE + 'index.php?url=psicologo/citas&ajax=slots&fecha=' + encodeURIComponent(fecha) + '&interval=' + interval;
    const tryFetch = (u) => fetch(u).then(r => r.ok ? r.text() : Promise.reject());
    tryFetch(urlPrimary).catch(() => tryFetch(urlFallback))
      .then(txt => { let j; try { j = JSON.parse(txt); } catch (e) { throw { parse: true, txt }; } return j; })
      .then(j => {
        if (j.error) {
          let msg = 'Error';
          if (j.error === 'fecha_pasada') msg = 'La fecha ya pasó';
          else if (j.error === 'formato_fecha') msg = 'Formato de fecha inválido';
          cont.innerHTML = '<span class="text-danger">' + msg + '</span>';
          return;
        }
        if (!j.slots || !j.slots.length) { cont.innerHTML = '<span class="text-muted">Sin horas libres</span>'; return; }
        cont.innerHTML = j.slots.map(h => `<button type="button" class="btn btn-sm btn-outline-primary m-1" onclick="selSlot('${h}')">${h}</button>`).join('');
      })
      .catch(err => { if (err && err.parse) { cont.innerHTML = '<span class="text-danger">Respuesta no válida</span>'; console.error(err.txt); } else { cont.innerHTML = '<span class="text-danger">Error cargando</span>'; } });
    return; // fin función
  }
  function selSlot(h) {
    const fecha = document.getElementById('fechaSel').value;
    document.getElementById('fechaHoraFinal').value = fecha + ' ' + h + ':00';
    // Marcar visualmente
    [...document.querySelectorAll('#slots button')].forEach(b => b.classList.remove('active'));
    const btn = [...document.querySelectorAll('#slots button')].find(b => b.textContent === h); if (btn) btn.classList.add('active');
  }
  function prepararFechaHora() {
    const fecha = document.getElementById('fechaSel').value;
    const hoy = (new Date()).toISOString().slice(0, 10);
    if (!fecha || fecha < hoy) { alert('Fecha inválida.'); return false; }
    if (!document.getElementById('fechaHoraFinal').value) { alert('Selecciona una hora.'); return false; }
    return true;
  }
  function validarFechaInput() {
    const inp = document.getElementById('fechaSel');
    const hoy = (new Date()).toISOString().slice(0, 10);
    if (inp.value < hoy) { inp.value = hoy; }
  }
  document.addEventListener('DOMContentLoaded', validarFechaInput);
  document.addEventListener('DOMContentLoaded', cargarSlots);
  function filtrarTabla() {
    const est = document.getElementById('fEstado').value;
    const fecha = document.getElementById('fFecha').value;
    const txt = document.getElementById('fTexto').value.toLowerCase();
    document.querySelectorAll('#tablaCitas tbody tr').forEach(tr => {
      const okEstado = !est || tr.dataset.estado === est;
      const okFecha = !fecha || tr.dataset.fecha === fecha;
      const id = tr.children[0].textContent;
      const pac = tr.dataset.paciente.toLowerCase();
      const okTxt = !txt || id.includes(txt) || pac.includes(txt);
      tr.style.display = (okEstado && okFecha && okTxt) ? '' : 'none';
    });
  }
  function limpiarFiltros() {
    document.getElementById('fEstado').value = '';
    document.getElementById('fFecha').value = '';
    document.getElementById('fTexto').value = '';
    filtrarTabla();
  }

  // Mostrar modal con el QR
  function mostrarQRModal(rutaRel, contenido) {
    try {
      let base = BASE;
      // Normalizar base (asegurar termina en /)
      if (!base.endsWith('/')) base += '';
      let ruta = rutaRel || '';
      // Quitar posible prefijo public/
      ruta = ruta.replace(/^public\//, '');
      // Prepend base si no es absoluta ni ya incluye BASE
      if (!/^https?:/i.test(ruta) && !ruta.startsWith(base)) {
        ruta = base + ruta;
      }
      const img = document.getElementById('qrModalImg');
      const span = document.getElementById('qrModalCode');
      span.textContent = contenido || '';
      img.alt = 'QR ' + contenido;
      img.removeAttribute('data-error');
      img.onerror = function () {
        if (img.getAttribute('data-error') === '2') return; // ya intentamos fallback
        const tried = img.getAttribute('data-error');
        if (!tried) {
          // Primer fallo: intentar prefijo public/
          img.setAttribute('data-error', '1');
          const clean = img.src.replace(/\?t=\d+/, '');
          if (!/\/public\//.test(clean)) {
            let altSrc = clean.replace(/(qrcodes\/.*)$/, 'public/$1');
            img.src = altSrc + (altSrc.includes('?') ? '' : '?t=' + (Date.now()));
            return;
          }
        }
        // Segundo fallo: marcar error final
        img.setAttribute('data-error', '2');
        img.classList.add('border-danger');
        img.style.opacity = '0.4';
        span.textContent = 'No se pudo cargar la imagen (' + rutaRel + ')';
      };
      img.onload = function () {
        img.classList.remove('border-danger');
        img.style.opacity = '1';
      };
      img.src = ruta + (ruta.includes('?') ? '' : '?t=' + (Date.now())); // evitar cache si se regeneró
      if (window.bootstrap && bootstrap.Modal) {
        const modalEl = document.getElementById('qrModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      } else {
        alert('Bootstrap JS no cargado, abre en nueva pestaña');
        window.open(img.src, '_blank', 'noopener');
      }
    } catch (e) {
      console.error('Error mostrando modal QR', e);
      alert('No se pudo mostrar el QR');
    }
  }
</script>

<!-- Modal Scanner QR -->
<div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title"><i class="fas fa-qrcode me-1"></i> Escanear Cita</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"
          onclick="detenerScannerModal()"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded position-relative" style="background:#111;min-height:320px;">
              <div id="qrReaderModal" style="width:100%;height:100%;"></div>
              <div id="scannerEstado" class="position-absolute top-0 start-0 small text-white px-2 py-1"
                style="background:rgba(0,0,0,.4);border-bottom-right-radius:6px;">Inactivo</div>
            </div>
            <div class="mt-2 d-flex gap-2">
              <button id="btnScanStart" class="btn btn-sm btn-primary" onclick="iniciarScannerModal()"><i
                  class="fas fa-play"></i></button>
              <button id="btnScanStop" class="btn btn-sm btn-outline-secondary" onclick="detenerScannerModal()"
                disabled><i class="fas fa-stop"></i></button>
              <button id="btnScanRestart" class="btn btn-sm btn-outline-secondary" onclick="reiniciarScannerModal()"
                disabled><i class="fas fa-sync"></i></button>
            </div>
            <div class="mt-3">
              <label class="form-label small mb-1">Entrada manual</label>
              <div class="input-group input-group-sm">
                <input type="text" id="scanManualInput" class="form-control" placeholder="CITA:123" autocomplete="off">
                <button class="btn btn-outline-primary"
                  onclick="procesarTokenModal(document.getElementById('scanManualInput').value.trim(),false)"><i
                    class="fas fa-search"></i></button>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100 shadow-sm">
              <div class="card-header py-2"><strong>Resultado</strong></div>
              <div class="card-body" id="scanResultado" style="min-height:150px;">
                <em class="text-muted">Aún sin escaneo...</em>
              </div>
              <div class="card-footer py-2">
                <button id="btnScanAtender" class="btn btn-primary btn-sm d-none w-100" onclick="atenderCitaModal()">
                  <i class="fas fa-user-md me-1"></i>Atender Cita
                </button>
                <div id="scanMensaje" class="small mt-2"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="detenerScannerModal()">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  // --- Scanner Modal Logic ---
  let html5QrModal = null;
  let scannerActivo = false;
  let cooldownScan = false;
  let ultimoTokenScan = '';
  let citaModal = null;

  function ensureHtml5Lib(cb) {
    if (window.Html5Qrcode) { cb(); return; }

    const s = document.createElement('script');
    s.src = BASE + 'public/js/html5-qrcode.min.js';
    s.async = true;

    s.onload = function () {
      cb();
    };

    s.onerror = function () {
      mostrarScanMsg('❌ Error cargando librería QR local. Verifica public/js/html5-qrcode.min.js', 'danger');
      console.error('Error cargando html5-qrcode.min.js desde public/js/');
    };

    document.head.appendChild(s);
  }
  function abrirScannerModal() {
    citaModal = null;
    ultimoTokenScan = '';
    document.getElementById('scanResultado').innerHTML = '<em class="text-muted">Aún sin escaneo...</em>';
    document.getElementById('btnScanAtender').classList.add('d-none');
    document.getElementById('scanManualInput').value = '';
    mostrarScanMsg('', '');
    if (window.bootstrap) {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('scannerModal')).show();
    }

    // Precargar librería y mostrar estado
    mostrarScanMsg('Cargando librería...', 'info');
    ensureHtml5Lib(() => {
      mostrarScanMsg('✓ Listo. Presiona "Iniciar" para escanear.', 'success');
    });
  }
  function iniciarScannerModal() {
    ensureHtml5Lib(() => {
      if (scannerActivo) return;
      const div = document.getElementById('qrReaderModal');
      div.innerHTML = '';

      // Destruir instancia previa si existe
      if (html5QrModal) {
        html5QrModal.clear().catch(() => { });
      }

      html5QrModal = new Html5Qrcode('qrReaderModal');
      mostrarScanMsg('Solicitando acceso a cámara...', 'info');

      Html5Qrcode.getCameras().then(cams => {
        if (!cams.length) {
          mostrarScanMsg('No hay cámaras disponibles', 'warning');
          return;
        }
        // Preferir cámara trasera si está disponible
        let camId = cams[0].id;
        const backCam = cams.find(c => c.label && c.label.toLowerCase().includes('back'));
        if (backCam) camId = backCam.id;

        html5QrModal.start(
          camId,
          { fps: 10, qrbox: { width: 230, height: 230 }, aspectRatio: 1.0 },
          onScanModal,
          () => {/* ignorar errores de lectura */ }
        )
          .then(() => {
            scannerActivo = true;
            actualizarEstadoScanner('Activo - Escaneando');
            toggleScannerBtns();
            mostrarScanMsg('Scanner activo. Apunta al código QR.', 'success');
          })
          .catch(e => {
            const msg = e.toString();
            if (msg.includes('Permission') || msg.includes('NotAllowed')) {
              mostrarScanMsg('⚠️ Permiso denegado. Permite el acceso a la cámara.', 'danger');
            } else if (msg.includes('NotFound')) {
              mostrarScanMsg('⚠️ No se encontró cámara disponible.', 'danger');
            } else {
              mostrarScanMsg('Error al iniciar: ' + msg.substring(0, 50), 'danger');
            }
            console.error('Error al iniciar scanner:', e);
          });
      }).catch(e => {
        mostrarScanMsg('Error enumerando cámaras: ' + e, 'danger');
        console.error('Error getCameras:', e);
      });
    });
  }
  function detenerScannerModal() {
    if (!scannerActivo || !html5QrModal) return;
    html5QrModal.stop()
      .then(() => {
        scannerActivo = false;
        actualizarEstadoScanner('Detenido');
        toggleScannerBtns();
        mostrarScanMsg('Scanner detenido', 'info');
      })
      .catch(e => {
        console.error('Error deteniendo:', e);
        scannerActivo = false;
        toggleScannerBtns();
      });
  }
  function reiniciarScannerModal() { detenerScannerModal(); setTimeout(iniciarScannerModal, 300); }
  function onScanModal(decoded) { procesarTokenModal(decoded.trim(), true); }
  function procesarTokenModal(token, desdeCam) {
    if (!token) return;
    if (!token.startsWith('CITA:')) {
      mostrarScanMsg('Formato inválido', 'danger');
      return;
    }
    if (desdeCam) {
      if (token === ultimoTokenScan) return;
      ultimoTokenScan = token;
      if (cooldownScan) return;
      cooldownScan = true;
      setTimeout(() => cooldownScan = false, 1200);
    }

    fetch(BASE + 'index.php?url=psicologo/scanConsultar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'token=' + encodeURIComponent(token)
    })
      .then(r => r.json())
      .then(j => {
        if (!j.ok) {
          mostrarScanMsg(j.msg || 'Error', 'danger');
          citaModal = null;
          renderResultadoScan(null);
          return;
        }
        citaModal = j.cita;
        renderResultadoScan(j.cita);

        const btnAtender = document.getElementById('btnScanAtender');

        // Mostrar botón con texto según estado
        if (j.cita.estado_cita !== 'cancelada') {
          btnAtender.classList.remove('d-none');

          // Cambiar texto y estilo del botón según acción
          if (j.accion === 'atender') {
            btnAtender.innerHTML = '<i class="fas fa-user-md me-1"></i>Atender Cita';
            btnAtender.className = 'btn btn-primary btn-sm w-100';
            mostrarScanMsg('✓ Cita encontrada. Puedes atenderla.', 'success');
          } else {
            btnAtender.innerHTML = '<i class="fas fa-eye me-1"></i>Ver Cita';
            btnAtender.className = 'btn btn-info btn-sm w-100';
            mostrarScanMsg('Cita ya realizada. Puedes ver detalles.', 'info');
          }
        } else {
          btnAtender.classList.add('d-none');
          mostrarScanMsg('Cita cancelada', 'warning');
        }

        detenerScannerModal();
      })
      .catch(() => mostrarScanMsg('Fallo red', 'danger'));
  }

  function renderResultadoScan(c) {
    const d = document.getElementById('scanResultado');
    if (!c) {
      d.innerHTML = '<em class="text-muted">Sin datos</em>';
      return;
    }

    const estadoBadge = c.estado_cita === 'pendiente'
      ? 'warning text-dark'
      : (c.estado_cita === 'realizada' ? 'success' : 'danger');

    d.innerHTML = `
    <div class="mb-2"><strong>ID Cita:</strong> #${c.id}</div>
    <div class="mb-2"><strong>Paciente:</strong> ${c.nombre_paciente || 'Paciente #' + c.id_paciente}</div>
    <div class="mb-2"><strong>Fecha/Hora:</strong> ${c.fecha_hora}</div>
    <div><strong>Estado:</strong> <span class="badge bg-${estadoBadge}">${c.estado_cita.toUpperCase()}</span></div>
  `;
  }

  function atenderCitaModal() {
    if (!citaModal) {
      mostrarScanMsg('No hay cita cargada', 'warning');
      return;
    }

    // Redirigir a la vista de atender cita
    window.location.href = BASE + 'index.php?url=psicologo/atenderCita&id=' + citaModal.id;
  }
  function mostrarScanMsg(msg, tipo) {
    const el = document.getElementById('scanMensaje');
    if (!el) return;
    el.textContent = msg || '';
    el.className = 'small fw-semibold mt-1';
    if (tipo === 'danger') el.classList.add('text-danger');
    else if (tipo === 'warning') el.classList.add('text-warning');
    else if (tipo === 'success') el.classList.add('text-success');
    else if (tipo === 'info') el.classList.add('text-info');
  }
  function actualizarEstadoScanner(t) {
    const e = document.getElementById('scannerEstado');
    if (e) e.textContent = t;
  }
  function toggleScannerBtns() {
    document.getElementById('btnScanStart').disabled = scannerActivo;
    document.getElementById('btnScanStop').disabled = !scannerActivo;
    document.getElementById('btnScanRestart').disabled = !scannerActivo;
  }
  // Limpiar al cerrar modal
  document.getElementById('scannerModal').addEventListener('hidden.bs.modal', () => {
    detenerScannerModal();
    setTimeout(() => {
      if (html5QrModal) {
        html5QrModal.clear().catch(() => { });
        html5QrModal = null;
      }
    }, 300);
  });
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
        <img id="qrModalImg" src="" alt="QR" class="img-fluid border p-1 mb-2"
          style="max-width:240px;background:#fff" />
        <div class="small text-muted">Contenido: <span id="qrModalCode"></span></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2"
          onclick="window.open(document.getElementById('qrModalImg').src,'_blank','noopener')">Abrir en nueva
          pestaña</button>
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
    function mostrarQRModal(rutaRel, contenido) {
      try {
        let base = (typeof BASE !== 'undefined' ? BASE : '');
        if (base && !base.endsWith('/')) base += '';
        let ruta = (rutaRel || '').replace(/^public\//, '');
        if (!/^https?:/i.test(ruta) && base && !ruta.startsWith(base)) ruta = base + ruta;
        const img = document.getElementById('qrModalImg');
        const span = document.getElementById('qrModalCode');
        if (!img || !span) { alert('Modal no presente en el DOM'); return; }
        span.textContent = contenido || '';
        img.alt = 'QR ' + contenido;
        img.onerror = function () {
          if (img.getAttribute('data-error') === '2') return;
          const tried = img.getAttribute('data-error');
          if (!tried) {
            img.setAttribute('data-error', '1');
            const clean = img.src.replace(/\?t=\d+/, '');
            if (!/\/public\//.test(clean)) {
              let altSrc = clean.replace(/(qrcodes\/.*)$/, 'public/$1');
              img.src = altSrc + (altSrc.includes('?') ? '' : '?t=' + (Date.now()));
              return;
            }
          }
          img.setAttribute('data-error', '2');
          span.textContent = 'No se pudo cargar el QR';
          img.classList.add('border-danger');
        };
        img.src = ruta + (ruta.includes('?') ? '' : '?t=' + (Date.now()));
        if (window.bootstrap && bootstrap.Modal) {
          const modalEl = document.getElementById('qrModal');
          bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
          window.open(img.src, '_blank', 'noopener');
        }
      } catch (e) { console.error(e); alert('Error mostrando QR'); }
    }
  }
</script>