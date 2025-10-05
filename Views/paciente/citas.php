
<h2 class="h5 mb-3">Mis Citas</h2>

<?php if(!empty($mensaje)): ?>
  <div class="alert alert-<?= htmlspecialchars($tipoMsj) ?> py-2"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<button class="btn btn-sm btn-primary mb-3" id="btnToggleForm">
  <i class="fas fa-plus me-1"></i> Nueva Cita
</button>

<div id="formNuevaCita" class="card card-body p-3 mb-4" style="max-width:650px; display:none;">
  <h6 class="text-primary mb-2">Crear Nueva Cita</h6>
  <form method="post">
    <div class="row">
      <div class="col-md-5 mb-2">
        <label class="form-label mb-0">Fecha y Hora</label>
        <input type="datetime-local" name="fecha_hora" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-4 mb-2">
        <label class="form-label mb-0">Psicólogo</label>
        <select name="id_psicologo" class="form-select form-select-sm" required>
          <option value="">Seleccione...</option>
          <?php foreach($psicologos as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 mb-2 d-flex align-items-end">
        <button class="btn btn-success btn-sm w-100">Guardar</button>
      </div>
    </div>
    <div class="mb-2">
      <label class="form-label mb-0">Motivo</label>
      <textarea name="motivo" rows="3" class="form-control form-control-sm" required></textarea>
    </div>
  </form>
</div>

<?php if(!$citas): ?>
  <div class="alert alert-info">Aún no tienes citas.</div>
<?php else: ?>
<table class="table table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th><th>Fecha / Hora</th><th>Estado</th><th>Motivo</th><th>QR</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($citas as $c): ?>
    <tr>
      <td><?= $c['id'] ?></td>
      <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
      <td><span class="badge bg-<?= $c['estado_cita']==='pendiente'?'warning text-dark':'success' ?>">
        <?= $c['estado_cita'] ?></span></td>
      <td style="max-width:220px;"><?= htmlspecialchars($c['motivo_consulta']) ?></td>
      <td>
        <?php if(!empty($c['qr_code'])): ?>
          <small><?= htmlspecialchars($c['qr_code']) ?></small>
        <?php else: ?>
          <small class="text-muted">N/A</small>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<script>
  (function(){
    const btn = document.getElementById('btnToggleForm');
    const box = document.getElementById('formNuevaCita');
    if(btn && box){
      btn.addEventListener('click', () => {
        box.style.display = box.style.display === 'none' ? 'block' : 'none';
      });
    }
  })();
</script>