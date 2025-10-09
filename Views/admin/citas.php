<?php /* Nueva interfaz de administración de citas con filtros y acciones */ ?>
<h1 class="h4 mb-3">Gestión de Citas</h1>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger py-2 small">
    <?= htmlspecialchars($_SESSION['flash_error']);
    unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body py-3">
    <div class="row g-3 align-items-end">
      <div class="col-sm-2">
        <label class="form-label mb-0 small">Estado</label>
  <select id="fEstado" class="form-select form-select-sm" onchange="filtrarCitas()">
          <option value="">Todos</option>
          <option value="pendiente">Pendiente</option>
          <option value="realizada">Realizada</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-sm-2">
        <label class="form-label mb-0 small">Fecha</label>
  <input type="date" id="fFecha" class="form-control form-control-sm" onchange="filtrarCitas()">
      </div>
      <div class="col-sm-3">
        <label class="form-label mb-0 small">Texto (ID, motivo, paciente)</label>
        <input type="text" id="fTexto" class="form-control form-control-sm" oninput="filtrarCitas()"
          placeholder="Buscar...">
      </div>
      <div class="col-sm-3">
        <label class="form-label mb-0 small">Psicólogo</label>
  <select id="fPs" class="form-select form-select-sm" onchange="filtrarCitas()">
          <option value="">Todos</option>
          <?php foreach ($psicologos as $p): ?>
            <option value="<?= (int) $p['id'] ?>">#<?= (int) $p['id'] ?>   <?= htmlspecialchars($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-2 d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">Limpiar</button>
        
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-sm table-striped align-middle" id="tablaCitasAdmin">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Paciente</th>
        <th>Psicólogo</th>
        <th>Fecha/Hora</th>
        <th>Estado</th>
        <th>Evaluaciones</th>
        <th>Motivo</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
<div id="citasVacio" class="alert alert-info py-2 d-none">Sin resultados con los filtros actuales.</div>

<!-- Modal Ver Evaluaciones -->
<div class="modal fade" id="verEvaluacionesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Evaluaciones de la Cita <span id="evalCitaId"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="evaluacionesContent">
        <div class="text-center py-3">
          <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
          <p class="small text-muted mt-2">Cargando evaluaciones...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Reasignar -->
<div class="modal fade" id="reasignarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Reasignar Cita <span id="reasignarId"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formReasignar" method="post" action="<?= url('admin', 'citas') ?>"
          onsubmit="return validarReasignar()">
          <input type="hidden" name="id" id="reasignarInputId">
          <input type="hidden" name="op" value="reasignar">
          <input type="hidden" name="fecha_hora" id="reasignarFechaHoraFinal">
          <div class="mb-2">
            <label class="form-label small">Psicólogo destino</label>
            <select name="id_psicologo" id="reasignarSelectPs" class="form-select form-select-sm" required
              onchange="cargarSlotsReasignar()">
              <option value="">Seleccione</option>
              <?php foreach ($psicologos as $p): ?>
                <option value="<?= (int) $p['id'] ?>">#<?= (int) $p['id'] ?>   <?= htmlspecialchars($p['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small">Fecha</label>
            <input type="date" id="reasignarFecha" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>"
              onchange="cargarSlotsReasignar()">
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
      <div class="modal-header py-2">
        <h6 class="modal-title">Cancelar Cita <span id="cancelarId"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="post" action="<?= url('admin', 'citas') ?>" onsubmit="return confirmarCancelar()">
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

<script>
  const BASE = '<?= RUTA ?>';
  let datosCitas = <?= json_encode($citas) ?>;
  function fetchCitas() {
    const p = new URLSearchParams();
    const e = document.getElementById('fEstado').value.trim(); if (e) p.append('estado', e);
    const f = document.getElementById('fFecha').value.trim(); if (f) p.append('fecha', f);
    const t = document.getElementById('fTexto').value.trim(); if (t) p.append('texto', t);
    const ps = document.getElementById('fPs').value.trim(); if (ps) p.append('ps', ps);
    p.append('ajax', 'list');
    return fetch(BASE + 'index.php?url=admin/citas&' + p.toString())
      .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
      })
      .then(txt => {
        try {
          const j = JSON.parse(txt);
          if (j.error) {
            console.error('Error del servidor:', j.error);
            throw new Error(j.error);
          }
          datosCitas = j.citas || [];
        } catch (e) {
          if (e instanceof SyntaxError) {
            console.error('Respuesta no JSON:', txt.substring(0, 500));
            throw new Error('El servidor no devolvió JSON válido');
          }
          throw e;
        }
      });
  }
  function formatearEstado(est) {
    const map = {
      pendiente: 'warning text-dark',
      realizada: 'success',
      cancelada: 'danger'
    };
    const cls = map[est] || 'secondary';
    const texto = est.charAt(0).toUpperCase() + est.slice(1);
    return `<span class="badge bg-${cls}">${texto}</span>`;
  }
  function renderTabla() {
    const tb = document.querySelector('#tablaCitasAdmin tbody'); tb.innerHTML = '';
    if (!datosCitas.length) { document.getElementById('citasVacio').classList.remove('d-none'); return; }
    document.getElementById('citasVacio').classList.add('d-none');
    datosCitas.forEach(c => {
      const tr = document.createElement('tr');
      // data attributes used by filtrarCitas()
      tr.setAttribute('data-estado', String(c.estado_cita || '').toLowerCase());
      tr.setAttribute('data-fecha', String((c.fecha_hora || '').substring(0, 10)));
      const pacienteNombre = (c.paciente_nombre || c.paciente || c.id_paciente || '').toString();
      const psicologoNombre = (c.psicologo_nombre || c.psicologo || c.id_psicologo || '').toString();
      tr.setAttribute('data-paciente', pacienteNombre.toLowerCase());
      tr.setAttribute('data-psicologo', psicologoNombre.toLowerCase());
      tr.setAttribute('data-id-paciente', String(c.id_paciente || ''));
      tr.setAttribute('data-id-psicologo', String(c.id_psicologo || ''));
      tr.setAttribute('data-motivo', String(c.motivo_consulta || '').toLowerCase());

      const countEval = c.count_evaluaciones || 0;
      const evalHtml = countEval > 0
        ? `<button class='btn btn-sm btn-outline-info' onclick='verEvaluaciones(${c.id})' title='Ver evaluaciones'><i class='fas fa-clipboard-check'></i> ${countEval}</button>`
        : `<span class='text-muted small'>0</span>`;

      tr.innerHTML = `<td>${c.id}</td>
      <td>${pacienteNombre}</td><td>${psicologoNombre}</td><td>${c.fecha_hora}</td><td>${formatearEstado(c.estado_cita)}</td>
      <td class='text-center'>${evalHtml}</td>
      <td class='small' style='max-width:200px'>${(c.motivo_consulta || '').replace(/</g, '&lt;')}</td>
      <td class='text-nowrap'>${accionesHtml(c)}</td>`;
      tb.appendChild(tr);
    });

    // Apply current filters immediately after rendering
    filtrarCitas();
  }

  function filtrarCitas() {
    const est = (document.getElementById('fEstado').value || '').trim().toLowerCase();
    const fecha = (document.getElementById('fFecha').value || '').trim();
    const texto = (document.getElementById('fTexto').value || '').trim().toLowerCase();
    const ps = (document.getElementById('fPs').value || '').trim();

    let visibles = 0, total = 0;
    document.querySelectorAll('#tablaCitasAdmin tbody tr').forEach(tr => {
      total++;
      const estRow = (tr.dataset.estado || '').toLowerCase();
      const fechaRow = tr.dataset.fecha || '';
      const pacRow = tr.dataset.paciente || '';
      const psRow = tr.dataset.psicologo || '';
      const idPac = tr.dataset.idPaciente || '';
      const idPs = tr.dataset.idPsicologo || '';
      const motivo = tr.dataset.motivo || '';

      let ok = true;

      if (est && estRow !== est) ok = false;
      if (ok && fecha && fechaRow !== fecha) ok = false;
      if (ok && ps) {
        // ps filter is id (from select). compare to data-id-psicologo
        if (String(idPs) !== String(ps)) ok = false;
      }
      if (ok && texto) {
        // search in id, motivo, paciente, psicologo
        const idRow = tr.querySelector('td') ? tr.querySelector('td').textContent : '';
        const hay = idRow.includes(texto) || motivo.includes(texto) || pacRow.includes(texto) || psRow.includes(texto) || idPac.includes(texto) || idPs.includes(texto);
        if (!hay) ok = false;
      }

      tr.style.display = ok ? '' : 'none';
      if (ok) visibles++;
    });

    const vacio = document.getElementById('citasVacio');
    if (visibles === 0) vacio.classList.remove('d-none'); else vacio.classList.add('d-none');
  }
  function accionesHtml(c) {
    const countEval = c.count_evaluaciones || 0;
    let html = '';

    // Botón Cancelar (solo si es pendiente y sin evaluaciones)
    if (c.estado_cita === 'pendiente') {
      if (countEval > 0) {
        html += `<span class='badge bg-warning text-dark me-1' title='Tiene ${countEval} evaluaciones'><i class="fas fa-lock"></i></span>`;
      } else {
        html += `<button class='btn btn-outline-danger btn-sm me-1' onclick='abrirCancelar(${c.id})' title='Cancelar'><i class="fas fa-ban"></i></button>`;
      }
    }

    // Botón Reasignar
    if (c.estado_cita === 'realizada' || c.estado_cita === 'cancelada') {
      html += `<button class='btn btn-outline-secondary btn-sm' disabled title='No disponible'><i class="fas fa-exchange-alt"></i></button>`;
    } else {
      html += `<button class='btn btn-outline-secondary btn-sm' onclick='abrirReasignar(${c.id},${c.id_psicologo},"${c.fecha_hora}")' title='Reasignar'><i class="fas fa-exchange-alt"></i></button>`;
    }

    return html;
  }
  function limpiarFiltros() {
    ['fEstado', 'fFecha', 'fTexto', 'fPs'].forEach(id => document.getElementById(id).value = '');
    // Aplicar filtrado cliente inmediatamente (no forzamos AJAX)
    filtrarCitas();
  }
  function refrescarCitas() {
    fetchCitas().then(renderTabla).catch(err => {
      console.error('Error al cargar citas:', err);
      alert('Error cargando citas');
    });
  }
  function abrirReasignar(id, actual, fh) {
    document.getElementById('reasignarId').textContent = '#' + id;
    document.getElementById('reasignarInputId').value = id;
    document.getElementById('reasignarSelectPs').value = actual;
    document.getElementById('reasignarAviso').style.display = 'none';
    const f = fh.substring(0, 10);
    document.getElementById('reasignarFecha').value = f;
    document.getElementById('reasignarHora').value = '';
    const cont = document.getElementById('reasignarSlots');
    cont.innerHTML = '<em class="text-muted">Cargando...</em>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('reasignarModal')).show();
    // cargar slots inmediatamente
    setTimeout(() => { cargarSlotsReasignar(); }, 60);
  }
  function cargarSlotsReasignar() {
    const ps = document.getElementById('reasignarSelectPs').value;
    const fecha = document.getElementById('reasignarFecha').value;
    const cont = document.getElementById('reasignarSlots');
    if (!ps || !fecha) {
      cont.innerHTML = '<em class="text-muted">Seleccione psicólogo y fecha</em>';
      return;
    }
    cont.innerHTML = '<em>Cargando...</em>';
    const url = BASE + 'index.php?url=admin/citas&ajax=slots&ps=' + encodeURIComponent(ps) + '&fecha=' + encodeURIComponent(fecha);
    console.log('Cargando slots desde:', url);
    fetch(url)
      .then(r => {
        console.log('Respuesta status:', r.status, 'Content-Type:', r.headers.get('Content-Type'));
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
      })
      .then(txt => {
        console.log('Respuesta texto (primeros 200 chars):', txt.substring(0, 200));

        // Verificar que la respuesta sea JSON
        const s = txt.trim();
        if (!s.startsWith('{') && !s.startsWith('[')) {
          console.error('Respuesta NO es JSON, contenido completo:', txt);
          throw new Error('Respuesta inválida del servidor (no JSON)');
        }

        const j = JSON.parse(txt);
        console.log('JSON parseado:', j);

        // Soportar varias claves de mensaje de error/respuesta del servidor
        const serverError = j.error || j.err || j.exception || j.msg || null;
        const serverMessage = j.message || j.msg || j.ms || null;

        if (serverError) {
          cont.innerHTML = '<span class="text-danger small">Error: ' + String(serverError) + '</span>';
          return;
        }

        if (serverMessage) {
          cont.innerHTML = '<span class="text-warning small">' + String(serverMessage) + '</span>';
          return;
        }

        if (!j.slots || !j.slots.length) {
          const dia = j.dia || '';
          cont.innerHTML = '<span class="text-warning small">Sin horas libres' + (dia ? ' para ' + dia : '') + '</span>';
          return;
        }
        cont.innerHTML = j.slots.map(h => `<button type='button' class='btn btn-sm btn-outline-primary m-1' onclick='selSlotReasignar("${h}")'>${h}</button>`).join('');
      })
      .catch(err => {
        console.error('Error completo:', err);
        cont.innerHTML = '<span class="text-danger small">Error: ' + err.message + '</span>';
      });
  }
  function selSlotReasignar(h) { document.getElementById('reasignarHora').value = h;[...document.querySelectorAll('#reasignarSlots button')].forEach(b => b.classList.remove('active')); const btn = [...document.querySelectorAll('#reasignarSlots button')].find(b => b.textContent === h); if (btn) btn.classList.add('active'); }
  function abrirCancelar(id) { document.getElementById('cancelarId').textContent = '#' + id; document.getElementById('cancelarInputId').value = id; bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelarModal')).show(); }
  function validarReasignar() { const ps = document.getElementById('reasignarSelectPs').value; const fecha = document.getElementById('reasignarFecha').value; const hora = document.getElementById('reasignarHora').value; if (!ps || !fecha || !hora) { alert('Selecciona psicólogo, fecha y hora.'); return false; } document.getElementById('reasignarFechaHoraFinal').value = fecha + ' ' + hora + ':00'; return confirm('Confirmar reasignación?'); }
  function confirmarCancelar() { return confirm('Cancelar definitivamente?'); }

  // Ver evaluaciones de una cita
  function verEvaluaciones(idCita) {
    document.getElementById('evalCitaId').textContent = '#' + idCita;
    const content = document.getElementById('evaluacionesContent');
    content.innerHTML = `<div class="text-center py-3">
    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
    <p class="small text-muted mt-2">Cargando evaluaciones...</p>
  </div>`;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('verEvaluacionesModal')).show();

    // Cargar evaluaciones vía AJAX
    fetch(BASE + 'index.php?url=admin/citas&ajax=evaluaciones&id_cita=' + idCita)
      .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
      })
      .then(txt => {
        console.log('Respuesta evaluaciones:', txt.substring(0, 200));
        try {
          const data = JSON.parse(txt);

          if (data.error) {
            content.innerHTML = `<div class="alert alert-danger py-2">${data.error}</div>`;
            return;
          }

          if (!data.evaluaciones || data.evaluaciones.length === 0) {
            content.innerHTML = `<div class="alert alert-info py-2">Esta cita no tiene evaluaciones registradas.</div>`;
            return;
          }

          let html = '<div class="list-group">';
          data.evaluaciones.forEach((ev, idx) => {
            const fecha = ev.fecha_creacion ? new Date(ev.fecha_creacion).toLocaleString('es-ES') : 'N/A';
            html += `
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="mb-0">Evaluación ${idx + 1}</h6>
              <span class="badge bg-info">${fecha}</span>
            </div>
            <div class="row">
              <div class="col-md-4">
                <strong>Estado Emocional:</strong>
                <div class="progress mt-1" style="height: 25px;">
                  <div class="progress-bar ${ev.estado_emocional >= 7 ? 'bg-success' : ev.estado_emocional >= 4 ? 'bg-warning' : 'bg-danger'}" 
                       style="width: ${ev.estado_emocional * 10}%">
                    ${ev.estado_emocional}/10
                  </div>
                </div>
              </div>
              <div class="col-md-8">
                <strong>Comentarios:</strong>
                <p class="small mb-0 mt-1">${(ev.comentarios || 'Sin comentarios').replace(/</g, '&lt;')}</p>
              </div>
            </div>
          </div>`;
          });
          html += '</div>';

          content.innerHTML = html;

        } catch (e) {
          if (e instanceof SyntaxError) {
            console.error('Respuesta no JSON:', txt.substring(0, 500));
            content.innerHTML = `<div class="alert alert-danger py-2">Error: El servidor no devolvió JSON válido</div>`;
          } else {
            throw e;
          }
        }
      })
      .catch(err => {
        console.error('Error al cargar evaluaciones:', err);
        content.innerHTML = `<div class="alert alert-danger py-2">Error al cargar evaluaciones: ${err.message}</div>`;
      });
  }

  document.addEventListener('DOMContentLoaded', renderTabla);
</script>