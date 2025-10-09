<?php
$anioActual = date('Y');
$mesActual = date('m');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas del Sistema</h1>
  <div class="btn-group">
    <button type="button" class="btn btn-danger" onclick="exportarPDFGraficas()">
      <i class="fas fa-file-pdf me-1"></i>PDF Gráficas
    </button>
    <button type="button" class="btn btn-warning" onclick="exportarPDFDatos()">
      <i class="fas fa-file-pdf me-1"></i>PDF Datos
    </button>
    <button type="button" class="btn btn-success" onclick="exportarExcel()">
      <i class="fas fa-file-excel me-1"></i>Exportar Excel
    </button>
  </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
  </div>
  <div class="card-body">
    <form method="GET" action="<?= url('admin', 'estadisticas') ?>" class="row g-3">
      <div class="col-md-3">
        <label class="form-label small fw-bold">Año</label>
        <select name="anio" class="form-select">
          <?php for ($a = date('Y'); $a >= 2020; $a--): ?>
            <option value="<?= $a ?>" <?= ($anio ?? $anioActual) == $a ? 'selected' : '' ?>><?= $a ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Mes</label>
        <select name="mes" class="form-select">
          <option value="">Todos</option>
          <?php
          $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
          for ($m = 1; $m <= 12; $m++):
            ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($mes ?? '') == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
              <?= $meses[$m - 1] ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Psicólogo</label>
        <select name="psicologo" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($psicologos as $ps): ?>
            <option value="<?= $ps['id'] ?>" <?= ($psicologo ?? '') == $ps['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($ps['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">&nbsp;</label>
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search me-1"></i>Filtrar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Tarjetas de Resumen -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-primary">
      <div class="card-body text-center">
        <i class="fas fa-calendar-check fs-1 text-primary mb-2"></i>
        <h3 class="mb-1"><?= $stats['total_citas'] ?? 0 ?></h3>
        <p class="text-muted mb-0 small">Total Citas</p>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-success">
      <div class="card-body text-center">
        <i class="fas fa-check-circle fs-1 text-success mb-2"></i>
        <h3 class="mb-1"><?= $stats['citas_realizadas'] ?? 0 ?></h3>
        <p class="text-muted mb-0 small">Citas Realizadas</p>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-warning">
      <div class="card-body text-center">
        <i class="fas fa-clock fs-1 text-warning mb-2"></i>
        <h3 class="mb-1"><?= $stats['citas_pendientes'] ?? 0 ?></h3>
        <p class="text-muted mb-0 small">Citas Pendientes</p>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-info">
      <div class="card-body text-center">
        <i class="fas fa-dollar-sign fs-1 text-info mb-2"></i>
        <h3 class="mb-1">$<?= number_format($stats['ingresos_totales'] ?? 0, 2) ?></h3>
        <p class="text-muted mb-0 small">Ingresos Totales</p>
      </div>
    </div>
  </div>
</div>

<!-- Gráficos Principales -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Citas por Mes</h6>
      </div>
      <div class="card-body">
        <canvas id="chartCitasPorMes" height="250"></canvas>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-success"></i>Citas por Estado</h6>
      </div>
      <div class="card-body">
        <canvas id="chartCitasPorEstado" height="250"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-chart-line me-2 text-info"></i>Ingresos por Mes</h6>
      </div>
      <div class="card-body">
        <canvas id="chartIngresosPorMes" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Tablas Detalladas -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-user-md me-2 text-primary"></i>Top 10 Psicólogos</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Psicólogo</th>
                <th>Especialidad</th>
                <th>Citas</th>
                <th>Ingresos</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($topPsicologos)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-3">Sin datos</td>
                </tr>
              <?php else: ?>
                <?php $pos = 1;
                foreach ($topPsicologos as $tp): ?>
                  <tr>
                    <td><?= $pos++ ?></td>
                    <td><?= htmlspecialchars($tp['nombre']) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($tp['especialidad'] ?? 'N/A') ?></span></td>
                    <td><strong><?= $tp['total_citas'] ?></strong></td>
                    <td class="text-success fw-bold">$<?= number_format($tp['ingresos'], 2) ?></td>
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
        <h6 class="mb-0"><i class="fas fa-user-injured me-2 text-success"></i>Top 10 Pacientes</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Paciente</th>
                <th>Citas</th>
                <th>Pagos</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($topPacientes)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">Sin datos</td>
                </tr>
              <?php else: ?>
                <?php $pos = 1;
                foreach ($topPacientes as $tp): ?>
                  <tr>
                    <td><?= $pos++ ?></td>
                    <td><?= htmlspecialchars($tp['nombre']) ?></td>
                    <td><strong><?= $tp['total_citas'] ?></strong></td>
                    <td class="text-success fw-bold">$<?= number_format($tp['total_pagado'], 2) ?></td>
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

<!-- Horarios por Psicólogo -->
<div class="card mb-4">
  <div class="card-header bg-white">
    <h6 class="mb-0"><i class="fas fa-clock me-2 text-warning"></i>Horarios de Atención</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Psicólogo</th>
            <th>Lunes</th>
            <th>Martes</th>
            <th>Miércoles</th>
            <th>Jueves</th>
            <th>Viernes</th>
            <th>Sábado</th>
            <th>Domingo</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($horariosCompletos)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-3">Sin horarios configurados</td>
            </tr>
          <?php else: ?>
            <?php foreach ($horariosCompletos as $h): ?>
              <tr>
                <td class="fw-bold"><?= htmlspecialchars($h['nombre']) ?></td>
                <?php foreach (['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'] as $dia): ?>
                  <td class="small">
                    <?php if (isset($h['horarios'][$dia])): ?>
                      <?php foreach ($h['horarios'][$dia] as $bloque): ?>
                        <div class="badge bg-success mb-1">
                          <?= date('h:i A', strtotime($bloque['hora_inicio'])) ?><br>
                          <?= date('h:i A', strtotime($bloque['hora_fin'])) ?>
                        </div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Datos para los gráficos
  const citasPorMesData = <?= json_encode($citasPorMes ?? []) ?>;
  const citasPorEstadoData = <?= json_encode($citasPorEstado ?? []) ?>;
  const ingresosPorMesData = <?= json_encode($ingresosPorMes ?? []) ?>;

  // Chart: Citas por Mes
  new Chart(document.getElementById('chartCitasPorMes'), {
    type: 'bar',
    data: {
      labels: citasPorMesData.map(d => d.mes),
      datasets: [{
        label: 'Citas',
        data: citasPorMesData.map(d => d.total),
        backgroundColor: '#0d6efd',
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

  // Chart: Citas por Estado
  new Chart(document.getElementById('chartCitasPorEstado'), {
    type: 'doughnut',
    data: {
      labels: citasPorEstadoData.map(d => d.estado),
      datasets: [{
        data: citasPorEstadoData.map(d => d.total),
        backgroundColor: ['#ffc107', '#198754', '#dc3545'],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'right' } }
    }
  });

  // Chart: Ingresos por Mes
  new Chart(document.getElementById('chartIngresosPorMes'), {
    type: 'line',
    data: {
      labels: ingresosPorMesData.map(d => d.mes),
      datasets: [{
        label: 'Ingresos ($)',
        data: ingresosPorMesData.map(d => d.total),
        borderColor: '#198754',
        backgroundColor: 'rgba(25, 135, 84, 0.2)',
        fill: true,
        tension: 0.4,
        borderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });

  // Funciones de exportación (evitan duplicar el parámetro url y la doble '?')
  const baseEstadisticasUrl = '<?= url('admin', 'estadisticas') ?>';

  function buildExportUrl(tipo) {
    const params = new URLSearchParams(window.location.search);
    // Eliminamos parámetros que vamos a recomponer
    params.delete('export');
    // En modo fallback ya viene ?url=admin/estadisticas en baseEstadisticasUrl,
    // así que si lo capturamos desde location.search lo quitamos para no duplicarlo
    params.delete('url');
    params.set('export', tipo);
    const sep = baseEstadisticasUrl.includes('?') ? '&' : '?';
    return baseEstadisticasUrl + sep + params.toString();
  }

  function exportarPDFGraficas() {
    window.open(buildExportUrl('pdf_graficas'), '_blank');
  }

  function exportarPDFDatos() {
    window.open(buildExportUrl('pdf_datos'), '_blank');
  }

  function exportarExcel() {
    window.location.href = buildExportUrl('excel');
  }
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>