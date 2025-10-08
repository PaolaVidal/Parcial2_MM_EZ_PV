<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">
      <i class="fas fa-user-md me-2"></i>
      <?= $puedeEditar ? 'Atender Cita' : 'Ver Cita' ?> #<?= htmlspecialchars($cita['id']) ?>
    </h2>
    <a href="<?= RUTA ?>index.php?url=psicologo/citas" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
  </div>

  <?php if(isset($_GET['ok'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-1"></i>
      <?php if($_GET['ok']==='finalizada'): ?>
        Cita finalizada correctamente.
      <?php endif; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if(isset($_GET['err'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-1"></i>
      <?php
        $errores = [
          'nf' => 'Cita no encontrada',
          'sin_eval' => 'Debes agregar al menos una evaluación antes de finalizar la cita',
          'update' => 'Error al actualizar el estado de la cita'
        ];
        echo $errores[$_GET['err']] ?? 'Error desconocido';
      ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
      <i class="fas fa-info-circle me-1"></i>
      <?php if($_GET['msg']==='ya_realizada'): ?>
        Esta cita ya está marcada como realizada.
      <?php endif; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Columna izquierda: Información de la cita -->
    <div class="col-lg-5">
      <div class="card shadow-sm mb-3">
        <div class="card-header py-2 bg-primary text-white">
          <strong><i class="fas fa-info-circle me-1"></i>Información de la Cita</strong>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="text-muted small mb-1">ID Cita:</label>
            <div class="fw-bold">#<?= htmlspecialchars($cita['id']) ?></div>
          </div>
          
          <div class="mb-3">
            <label class="text-muted small mb-1">Paciente:</label>
            <div class="fw-bold">
              <i class="fas fa-user me-1 text-primary"></i>
              <?= htmlspecialchars($paciente['nombre'] ?? 'Desconocido') ?>
            </div>
          </div>

          <?php if(!empty($paciente['dui'])): ?>
          <div class="mb-3">
            <label class="text-muted small mb-1">DUI:</label>
            <div><?= htmlspecialchars($paciente['dui']) ?></div>
          </div>
          <?php endif; ?>

          <?php if(!empty($paciente['telefono'])): ?>
          <div class="mb-3">
            <label class="text-muted small mb-1">Teléfono:</label>
            <div><i class="fas fa-phone me-1 text-success"></i><?= htmlspecialchars($paciente['telefono']) ?></div>
          </div>
          <?php endif; ?>

          <?php if(!empty($paciente['email'])): ?>
          <div class="mb-3">
            <label class="text-muted small mb-1">Email:</label>
            <div><i class="fas fa-envelope me-1 text-info"></i><?= htmlspecialchars($paciente['email']) ?></div>
          </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="text-muted small mb-1">Fecha y Hora:</label>
            <div class="fw-bold">
              <i class="fas fa-calendar-alt me-1 text-warning"></i>
              <?= date('d/m/Y H:i', strtotime($cita['fecha_hora'])) ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="text-muted small mb-1">Estado de la Cita:</label>
            <div>
              <?php
                $badgeClass = [
                  'pendiente' => 'bg-warning text-dark',
                  'realizada' => 'bg-success',
                  'cancelada' => 'bg-danger'
                ];
                $clase = $badgeClass[$cita['estado_cita']] ?? 'bg-secondary';
              ?>
              <span class="badge <?= $clase ?>">
                <?= strtoupper(htmlspecialchars($cita['estado_cita'])) ?>
              </span>
            </div>
          </div>

          <?php if(!empty($cita['motivo_consulta'])): ?>
          <div class="mb-0">
            <label class="text-muted small mb-1">Motivo de Consulta:</label>
            <div class="border rounded p-2 bg-light">
              <?= nl2br(htmlspecialchars($cita['motivo_consulta'])) ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if($puedeEditar): ?>
      <!-- Botón de finalizar -->
      <div class="card shadow-sm border-success">
        <div class="card-body text-center">
          <p class="mb-2 small text-muted" id="textoFinalizar">
            <i class="fas fa-info-circle me-1"></i>
            <span id="msgSinEval" <?= count($evaluaciones) > 0 ? 'style="display:none"' : '' ?>>
              Agrega al menos una evaluación para poder finalizar
            </span>
            <span id="msgConEval" <?= count($evaluaciones) === 0 ? 'style="display:none"' : '' ?>>
              Ya tienes <?= count($evaluaciones) ?> evaluación(es). Puedes finalizar la cita.
            </span>
          </p>
          <form method="post" action="<?= RUTA ?>index.php?url=psicologo/finalizarCita" 
                onsubmit="return confirm('¿Confirmar que la cita está finalizada? No podrás agregar más evaluaciones.')">
            <input type="hidden" name="id_cita" value="<?= $cita['id'] ?>">
            <button type="submit" id="btnFinalizarCita" class="btn btn-success w-100" 
                    <?= count($evaluaciones) === 0 ? 'disabled' : '' ?>>
              <i class="fas fa-check-circle me-1"></i>Finalizar Cita
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Columna derecha: Evaluaciones -->
    <div class="col-lg-7">
      <?php if($puedeEditar): ?>
      <!-- Formulario para agregar evaluación -->
      <div class="card shadow-sm mb-3 border-primary">
        <div class="card-header py-2 bg-primary text-white">
          <strong><i class="fas fa-plus-circle me-1"></i>Agregar Nueva Evaluación</strong>
        </div>
        <div class="card-body">
          <form id="formEvaluacion">
            <input type="hidden" name="id_cita" value="<?= $cita['id'] ?>">
            
            <div class="mb-3">
              <label class="form-label">
                Estado Emocional (1-10)
                <span class="text-danger">*</span>
              </label>
              <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range flex-grow-1" 
                       id="estadoEmocional" name="estado_emocional" 
                       min="1" max="10" value="5" 
                       oninput="document.getElementById('estadoValor').textContent=this.value">
                <span class="badge bg-primary px-3 py-2" style="min-width:50px">
                  <span id="estadoValor">5</span>
                </span>
              </div>
              <div class="d-flex justify-content-between small text-muted mt-1">
                <span>1 - Muy mal</span>
                <span>10 - Excelente</span>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">
                Comentarios
                <span class="text-danger">*</span>
              </label>
              <textarea name="comentarios" id="comentarios" 
                        class="form-control" rows="4" 
                        placeholder="Observaciones, notas de la sesión, progreso del paciente..."
                        required maxlength="1000"></textarea>
              <div class="form-text">Máximo 1000 caracteres</div>
            </div>

            <div class="text-end">
              <button type="button" class="btn btn-outline-secondary me-2" onclick="limpiarFormulario()">
                <i class="fas fa-eraser me-1"></i>Limpiar
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Guardar Evaluación
              </button>
            </div>
          </form>
          
          <div id="mensajeEval" class="alert d-none mt-3"></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Lista de evaluaciones -->
      <div class="card shadow-sm">
        <div class="card-header py-2 bg-secondary text-white">
          <strong>
            <i class="fas fa-list me-1"></i>
            Evaluaciones Registradas 
            <span class="badge bg-light text-dark ms-1" id="countEval"><?= count($evaluaciones) ?></span>
          </strong>
        </div>
        <div class="card-body">
          <div id="listaEvaluaciones">
            <?php if(empty($evaluaciones)): ?>
              <div class="text-center text-muted py-4" id="sinEvaluaciones">
                <i class="fas fa-clipboard-list fa-3x mb-2 opacity-25"></i>
                <p class="mb-0">No hay evaluaciones registradas</p>
                <?php if($puedeEditar): ?>
                  <small>Agrega la primera evaluación arriba</small>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <?php foreach($evaluaciones as $eval): ?>
                <div class="eval-item border rounded p-3 mb-2 bg-light">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <span class="badge bg-info">Evaluación #<?= $eval['id'] ?></span>
                      <span class="badge bg-primary ms-1">
                        Estado: <?= $eval['estado_emocional'] ?>/10
                      </span>
                    </div>
                  </div>
                  <div class="mb-0">
                    <label class="small text-muted mb-1">Comentarios:</label>
                    <div><?= nl2br(htmlspecialchars($eval['comentarios'])) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?= RUTA ?>';
const PUEDE_EDITAR = <?= $puedeEditar ? 'true' : 'false' ?>;

<?php if($puedeEditar): ?>
// Manejar envío de formulario de evaluación
document.getElementById('formEvaluacion').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const mensajeDiv = document.getElementById('mensajeEval');
  
  // Validar que comentarios no esté vacío
  const comentarios = formData.get('comentarios').trim();
  if(!comentarios){
    mostrarMensaje('Los comentarios son obligatorios', 'warning');
    return;
  }
  
  // Deshabilitar botón mientras se procesa
  const btnSubmit = this.querySelector('button[type="submit"]');
  const btnTextOriginal = btnSubmit.innerHTML;
  btnSubmit.disabled = true;
  btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
  
  fetch(BASE + 'index.php?url=psicologo/guardarEvaluacion', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if(data.ok){
      mostrarMensaje(data.msg || 'Evaluación guardada correctamente', 'success');
      agregarEvaluacionALista(data.evaluacion);
      limpiarFormulario();
    } else {
      mostrarMensaje(data.msg || 'Error al guardar evaluación', 'danger');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    mostrarMensaje('Error de conexión al guardar', 'danger');
  })
  .finally(() => {
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = btnTextOriginal;
  });
});

function limpiarFormulario(){
  document.getElementById('estadoEmocional').value = 5;
  document.getElementById('estadoValor').textContent = 5;
  document.getElementById('comentarios').value = '';
  document.getElementById('mensajeEval').classList.add('d-none');
}

function mostrarMensaje(msg, tipo){
  const div = document.getElementById('mensajeEval');
  div.className = 'alert alert-' + tipo + ' mt-3';
  div.textContent = msg;
  div.classList.remove('d-none');
  
  // Auto-ocultar después de 5 segundos si es éxito
  if(tipo === 'success'){
    setTimeout(() => {
      div.classList.add('d-none');
    }, 5000);
  }
}

function agregarEvaluacionALista(eval){
  const lista = document.getElementById('listaEvaluaciones');
  
  // Ocultar mensaje de "sin evaluaciones"
  const sinEval = document.getElementById('sinEvaluaciones');
  if(sinEval){
    sinEval.remove();
  }
  
  // Crear elemento de evaluación
  const evalHtml = `
    <div class="eval-item border rounded p-3 mb-2 bg-light">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <span class="badge bg-info">Evaluación #${eval.id}</span>
          <span class="badge bg-primary ms-1">Estado: ${eval.estado_emocional}/10</span>
        </div>
      </div>
      <div class="mb-0">
        <label class="small text-muted mb-1">Comentarios:</label>
        <div>${escapeHtml(eval.comentarios).replace(/\n/g, '<br>')}</div>
      </div>
    </div>
  `;
  
  lista.insertAdjacentHTML('beforeend', evalHtml);
  
  // Actualizar contador
  const count = document.querySelectorAll('.eval-item').length;
  document.getElementById('countEval').textContent = count;
  
  // Habilitar botón finalizar y actualizar mensaje
  actualizarBotonFinalizar(count);
  
  // Scroll suave hacia la nueva evaluación
  setTimeout(() => {
    const items = document.querySelectorAll('.eval-item');
    items[items.length - 1].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }, 100);
}

function actualizarBotonFinalizar(count){
  const btnFinalizar = document.getElementById('btnFinalizarCita');
  const msgSinEval = document.getElementById('msgSinEval');
  const msgConEval = document.getElementById('msgConEval');
  
  if(btnFinalizar){
    if(count > 0){
      btnFinalizar.disabled = false;
      if(msgSinEval) msgSinEval.style.display = 'none';
      if(msgConEval){
        msgConEval.style.display = 'inline';
        msgConEval.textContent = `Ya tienes ${count} evaluación${count > 1 ? 'es' : ''}. Puedes finalizar la cita.`;
      }
    } else {
      btnFinalizar.disabled = true;
      if(msgSinEval) msgSinEval.style.display = 'inline';
      if(msgConEval) msgConEval.style.display = 'none';
    }
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
<?php endif; ?>
</script>

<style>
.eval-item {
  transition: all 0.3s ease;
}

.eval-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.form-range::-webkit-slider-thumb {
  background: #0d6efd;
}

.form-range::-moz-range-thumb {
  background: #0d6efd;
}
</style>
