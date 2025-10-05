
<h2 class="h5 mb-3">Psicólogos Disponibles</h2>
<?php if(empty($psicologos)): ?>
  <div class="alert alert-info py-2">No hay psicólogos activos.</div>
<?php else: ?>
<table class="table table-sm table-striped" style="max-width:700px">
  <thead class="table-light">
    <tr><th>Nombre</th><th>Especialidad</th></tr>
  </thead>
  <tbody>
  <?php foreach($psicologos as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['nombre']) ?></td>
      <td><?= htmlspecialchars($p['especialidad'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<a href="<?= RUTA ?>public/portal" class="btn btn-secondary btn-sm mt-2">Volver</a>
