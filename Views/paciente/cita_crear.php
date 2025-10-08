
<h2 class="h5 mb-3">Nueva Cita</h2>
<form method="post" class="card card-body p-3" style="max-width:520px">
  <div class="mb-2">
    <label class="form-label mb-0">Fecha y Hora</label>
    <input type="datetime-local" name="fecha_hora" class="form-control form-control-sm" required>
  </div>
  <div class="mb-2">
    <label class="form-label mb-0">Psicólogo (ID)</label>
    <input type="number" name="id_psicologo" class="form-control form-control-sm" required>
  </div>
  <div class="mb-3">
    <label class="form-label mb-0">Motivo</label>
    <textarea name="motivo" rows="3" class="form-control form-control-sm" required></textarea>
  </div>
  <button class="btn btn-success btn-sm">Crear</button>
  <a href="<?= RUTA ?>cita/mis" class="btn btn-secondary btn-sm">Cancelar</a>
</form>