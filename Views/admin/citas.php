
<h1 class="h4 mb-3">Gestión de Citas</h1>
<table class="table table-sm align-middle">
  <thead class="table-light"><tr><th>ID</th><th>Paciente</th><th>Psicólogo</th><th>Fecha</th><th>Estado</th><th>Motivo</th><th></th></tr></thead>
  <tbody>
  <?php foreach($citas as $c): ?>
    <tr>
      <td><?= $c['id'] ?></td>
      <td><?= $c['id_paciente'] ?></td>
      <td><?= $c['id_psicologo'] ?></td>
      <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
      <td><?= $c['estado_cita'] ?></td>
      <td style="max-width:180px" class="small"><?= htmlspecialchars($c['motivo_consulta']) ?></td>
      <td class="text-nowrap">
        <form method="post" action="<?= url('admin','citas') ?>" class="d-inline">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <input type="hidden" name="op" value="cancelar">
          <input type="text" name="motivo" class="form-control form-control-sm d-inline-block" placeholder="Motivo" style="width:120px" required>
          <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancelar cita?')">✖</button>
        </form>
        <form method="post" action="<?= url('admin','citas') ?>" class="d-inline">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <input type="hidden" name="op" value="reprogramar">
          <input type="datetime-local" name="fecha_hora" class="form-control form-control-sm d-inline-block" style="width:170px" required>
          <button class="btn btn-sm btn-outline-primary">⟳</button>
        </form>
        <form method="post" action="<?= url('admin','citas') ?>" class="d-inline">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <input type="hidden" name="op" value="reasignar">
          <select name="id_psicologo" class="form-select form-select-sm d-inline-block" style="width:130px" required>
            <option value="">Psicólogo</option>
            <?php foreach($psicologos as $p): ?>
              <option value="<?= $p['id'] ?>">#<?= $p['id'] ?> <?= htmlspecialchars($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-secondary">⇄</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php if(empty($citas)): ?><div class="alert alert-info">Sin citas.</div><?php endif; ?>
<?php require __DIR__.'/../layout/footer.php'; ?>