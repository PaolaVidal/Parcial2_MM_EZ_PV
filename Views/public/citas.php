<?php
<h2 class="h6 mb-3">Mis Citas</h2>
<?php if(!$citas): ?><div class="alert alert-info">Sin citas.</div>
<?php else: ?>
<table class="table table-sm">
  <thead><tr><th>ID</th><th>Fecha</th><th>Estado</th><th>Motivo</th></tr></thead>
  <tbody>
    <?php foreach($citas as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
        <td><?= $c['estado_cita'] ?></td>
        <td><?= htmlspecialchars($c['motivo_consulta']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<a href="<?= RUTA ?>public/panel" class="btn btn-secondary btn-sm">Volver</a>