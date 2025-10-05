<h1 class="h3 mb-3">Listado de Pagos</h1>
<h2 class="h5 mb-3">Pagos</h2>
<?php if (!$pagos): ?>
  <div class="alert alert-info">Sin registros.</div>
<?php else: ?>
<table class="table table-sm">
  <thead>
    <tr><th>ID</th><th>Cita</th><th>Monto</th><th>Estado</th><th>Fecha</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach($pagos as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= $p['id_cita'] ?></td>
      <td><?= number_format($p['monto_total'],2) ?></td>
      <td><?= $p['estado_pago'] ?></td>
      <td><?= $p['fecha'] ?></td>
      <td><a class="btn btn-sm btn-outline-primary" href="<?= RUTA ?>pago/ver/<?= $p['id'] ?>">Ver</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
