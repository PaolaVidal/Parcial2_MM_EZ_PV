<h2>Dashboard Psicólogo</h2>
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-bg-primary mb-3"><div class="card-body"><h6 class="card-title">Pendientes Hoy</h6><p class="fs-4 mb-0"><?= (int)$pendHoy ?></p></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-success mb-3"><div class="card-body"><h6 class="card-title">Realizadas Hoy</h6><p class="fs-4 mb-0"><?= (int)$realHoy ?></p></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-warning mb-3"><div class="card-body"><h6 class="card-title">Ingresos</h6><p class="fs-4 mb-0">$<?= number_format($ingresos,2) ?></p></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-info mb-3"><div class="card-body"><h6 class="card-title">Slots Próximos</h6><p class="small mb-0"><?php foreach($slots as $s) echo htmlspecialchars($s).'<br>'; ?></p></div></div>
  </div>
</div>
<p><a href="index.php?controller=Psicologo&action=citas" class="btn btn-outline-primary">Ver Citas</a> <a href="index.php?controller=Psicologo&action=scan" class="btn btn-outline-secondary">Escanear QR</a></p>
