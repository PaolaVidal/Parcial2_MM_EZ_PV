<h1 class="h3 mb-3">Listado de Pagos</h1>
<table class="table table-hover">
  <thead class="table-dark">
    <tr>
      <th>ID</th><th>ID Cita</th><th>Monto Base</th><th>Total</th><th>Estado Pago</th><th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($pagos as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= $p['id_cita'] ?></td>
        <td>$<?= number_format($p['monto_base'],2) ?></td>
        <td>$<?= number_format($p['monto_total'],2) ?></td>
        <td><span class="badge bg-<?= $p['estado_pago']==='pagado' ? 'success':'warning' ?>"><?= $p['estado_pago'] ?></span></td>
  <td><a href="<?= url('Pago','ver',['id'=>$p['id']]) ?>" class="btn btn-sm btn-primary">Ver</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
