<h1 class="h3 mb-4">Crear Cita</h1>
<form method="post" class="row g-3">
  <div class="col-md-3">
    <label class="form-label">ID Paciente</label>
    <input type="number" name="id_paciente" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">ID Psicólogo</label>
    <input type="number" name="id_psicologo" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Fecha y Hora</label>
    <input type="datetime-local" name="fecha_hora" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Motivo de Consulta</label>
    <textarea name="motivo_consulta" class="form-control" required></textarea>
  </div>
  <div class="col-12">
    <button class="btn btn-success">Guardar</button>
  <a href="<?= url('Cita','index') ?>" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
