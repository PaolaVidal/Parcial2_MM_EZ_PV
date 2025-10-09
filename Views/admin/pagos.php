<h1 class="h4 mb-3">Pagos & Tickets</h1>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card card-body">
      <h6 class="text-muted mb-2">Pagos Pendientes</h6>
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cita</th>
            <th>Monto</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendientes as $p): ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= $p['id_cita'] ?></td>
              <td>$<?= number_format($p['monto_total'], 2) ?></td>
              <td><?= $p['fecha'] ?></td>
              <td>
                <form method="post" action="<?= RUTA ?>pago/ver/<?= (int) $p['id'] ?>"
                  onsubmit="return confirm('Registrar pago en caja para el pago #<?= (int) $p['id'] ?> por $<?= number_format($p['monto_total'], 2) ?>?');">
                  <input type="hidden" name="accion" value="marcar_pagado">
                  <button class="btn btn-sm btn-success" type="submit">Registrar Pago</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($pendientes)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted">Sin pendientes</td>
            </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card card-body">
      <h6 class="text-muted mb-2">Ingresos por Mes (Año)</h6>
      <canvas id="chartIngresosMes" height="160"></canvas>
    </div>
  </div>
</div>
<div class="card card-body mb-4">
  <h6 class="text-muted mb-2">Ingresos por Psicólogo</h6>
  <canvas id="chartIngresosPsico" height="160"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ingresosMes = <?= json_encode($ingresosMes) ?>;
  const ingresosPs = <?= json_encode($ingPorPsico) ?>;
  new Chart(document.getElementById('chartIngresosMes'), { type: 'bar', data: { labels: ingresosMes.map(o => o.mes), datasets: [{ label: 'Ingresos', data: ingresosMes.map(o => o.total), backgroundColor: '#20c997' }] } });
  new Chart(document.getElementById('chartIngresosPsico'), { type: 'bar', data: { labels: ingresosPs.map(o => 'Ps ' + o.id_psicologo), datasets: [{ label: 'Total', data: ingresosPs.map(o => o.total), backgroundColor: '#fd7e14' }] } });
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>