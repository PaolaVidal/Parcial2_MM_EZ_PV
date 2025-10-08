
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard Administrador</h1>
  <a href="<?= url('admin','estadisticas') ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-file-pdf me-1"></i>Exportar Reporte PDF
  </a>
</div>

<!-- Tarjetas de Resumen -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-primary h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="text-muted mb-1 small">Total Usuarios</p>
            <h3 class="mb-0"><?= ($usuariosCounts['activo'] ?? 0) + ($usuariosCounts['inactivo'] ?? 0) ?></h3>
          </div>
          <div class="fs-1 text-primary opacity-50">
            <i class="fas fa-users"></i>
          </div>
        </div>
        <small class="text-success"><i class="fas fa-check-circle"></i> <?= $usuariosCounts['activo'] ?? 0 ?> activos</small>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card border-info h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="text-muted mb-1 small">Total Pacientes</p>
            <h3 class="mb-0"><?= $totalPacientes ?? 0 ?></h3>
          </div>
          <div class="fs-1 text-info opacity-50">
            <i class="fas fa-user-injured"></i>
          </div>
        </div>
        <small class="text-success"><i class="fas fa-check-circle"></i> <?= $pacientesActivos ?? 0 ?> activos</small>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card border-warning h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="text-muted mb-1 small">Citas Este Mes</p>
            <h3 class="mb-0"><?= $citasMes ?? 0 ?></h3>
          </div>
          <div class="fs-1 text-warning opacity-50">
            <i class="fas fa-calendar-check"></i>
          </div>
        </div>
        <small class="text-info"><i class="fas fa-clock"></i> <?= $citasPendientes ?? 0 ?> pendientes</small>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card border-success h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="text-muted mb-1 small">Ingresos Mes</p>
            <h3 class="mb-0">$<?= number_format($ingresosMes ?? 0, 2) ?></h3>
          </div>
          <div class="fs-1 text-success opacity-50">
            <i class="fas fa-dollar-sign"></i>
          </div>
        </div>
        <small class="text-success"><i class="fas fa-arrow-up"></i> <?= $pagosPagados ?? 0 ?> pagos completados</small>
      </div>
    </div>
  </div>
</div>

<!-- Gráfico Principal -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-calendar me-2 text-info"></i>Citas por Estado</h6>
      </div>
      <div class="card-body">
        <canvas id="chartCitas" height="180"></canvas>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-chart-line me-2 text-success"></i>Ingresos Mensuales (últimos 6 meses)</h6>
      </div>
      <div class="card-body">
        <canvas id="chartIngresosMes" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Actividad Reciente -->
<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-clock me-2 text-warning"></i>Citas Próximas</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Psicólogo</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($proximasCitas)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No hay citas próximas</td></tr>
              <?php else: ?>
                <?php foreach(array_slice($proximasCitas, 0, 5) as $c): ?>
                  <tr>
                    <td class="small"><?= date('d/m/Y H:i', strtotime($c['fecha_hora'])) ?></td>
                    <td class="small"><?= htmlspecialchars($c['paciente_nombre'] ?? '') ?></td>
                    <td class="small"><?= htmlspecialchars($c['psicologo_nombre'] ?? '') ?></td>
                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($c['estado_cita']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-exclamation-circle me-2 text-danger"></i>Pagos Pendientes</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Monto</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($pagosPendientesLista)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No hay pagos pendientes</td></tr>
              <?php else: ?>
                <?php foreach(array_slice($pagosPendientesLista, 0, 5) as $p): ?>
                  <tr>
                    <td class="small"><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                    <td class="small"><?= htmlspecialchars($p['paciente_nombre'] ?? '') ?></td>
                    <td class="small">$<?= number_format($p['monto_total'], 2) ?></td>
                    <td><span class="badge bg-danger"><?= htmlspecialchars($p['estado_pago']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(async function(){
  try {
    const r2 = await fetch('<?= url('Admin','jsonCitasEstados') ?>').then(r=>r.json());
    const r3 = await fetch('<?= url('Admin','jsonIngresosMes') ?>').then(r=>r.json());

    // Chart de Citas
    new Chart(document.getElementById('chartCitas'), {
      type: 'bar',
      data: {
        labels: r2.map(o => o.estado.charAt(0).toUpperCase() + o.estado.slice(1)),
        datasets: [{
          label: 'Cantidad de Citas',
          data: r2.map(o => o.total),
          backgroundColor: ['#ffc107', '#198754', '#dc3545'],
          borderRadius: 8,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
          legend: { display: true, position: 'bottom' },
          tooltip: { 
            callbacks: { 
              label: function(context) { return context.parsed.y + ' citas'; } 
            } 
          }
        },
        scales: { 
          y: { 
            beginAtZero: true, 
            ticks: { stepSize: 1 } 
          } 
        }
      }
    });

    // Chart de Ingresos
    new Chart(document.getElementById('chartIngresosMes'), {
      type: 'line',
      data: {
        labels: r3.map(o => o.mes),
        datasets: [{
          label: 'Ingresos ($)',
          data: r3.map(o => o.total),
          borderColor: '#198754',
          backgroundColor: 'rgba(25, 135, 84, 0.2)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 4,
          pointBackgroundColor: '#198754',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
          legend: { display: true, position: 'bottom' },
          tooltip: { 
            callbacks: { 
              label: function(context) { return '$' + context.parsed.y.toFixed(2); } 
            } 
          }
        },
        scales: { 
          y: { 
            beginAtZero: true,
            ticks: {
              callback: function(value) { return '$' + value; }
            }
          } 
        }
      }
    });
  } catch(e) {
    console.error('Error cargando gráficos:', e);
  }
})();
</script>
<?php require __DIR__.'/../layout/footer.php'; ?>