<?php
$nombre = htmlspecialchars($paciente['nombre'] ?? 'Paciente');
$dui = htmlspecialchars($paciente['dui'] ?? 'N/A');
$email = htmlspecialchars($paciente['email'] ?? 'No registrado');
$telefono = htmlspecialchars($paciente['telefono'] ?? 'No registrado');
$direccion = htmlspecialchars($paciente['direccion'] ?? 'No registrada');
$fechaRaw = $paciente['fecha_nacimiento'] ?? $paciente['fechaNacimiento'] ?? '';
if ($fechaRaw && preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaRaw)) {
  try {
    $fechaNac = (new DateTime(substr($fechaRaw, 0, 10)))->format('d/m/Y');
  } catch (Throwable $e) {
    $fechaNac = htmlspecialchars($fechaRaw);
  }
} else {
  $fechaNac = 'No registrada';
}
?>

<div class="card shadow-sm mb-4">
  <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
    <div>
      <h2 class="h5 mb-2"><i class="fas fa-user-circle text-primary"></i> Bienvenido, <?= $nombre ?></h2>
      <div class="small text-muted">
        <div><i class="fas fa-id-card"></i> <strong>DUI:</strong> <?= $dui ?></div>
        <div><i class="fas fa-envelope"></i> <strong>Email:</strong> <?= $email ?></div>
        <div><i class="fas fa-phone"></i> <strong>Teléfono:</strong> <?= $telefono ?></div>
        <div><i class="fas fa-home"></i> <strong>Dirección:</strong> <?= $direccion ?></div>
        <div><i class="fas fa-birthday-cake"></i> <strong>Fecha de Nacimiento:</strong> <?= $fechaNac ?></div>
      </div>
    </div>
    <div class="text-md-end">
      <a href="<?= RUTA ?>public/salir" class="btn btn-outline-danger btn-sm">
        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
      </a>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Mis Citas -->
  <div class="col-md-6">
    <div class="card h-100 shadow-sm border-primary">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Mis Citas</h5>
      </div>
      <div class="card-body">
        <p class="card-text">Consulta tu historial de citas realizadas y próximas citas programadas.</p>
        <a href="<?= RUTA ?>public/citas" class="btn btn-primary w-100">
          <i class="fas fa-eye"></i> Ver Mis Citas
        </a>
      </div>
    </div>
  </div>

  <!-- Mis Pagos -->
  <div class="col-md-6">
    <div class="card h-100 shadow-sm border-success">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Mis Pagos</h5>
      </div>
      <div class="card-body">
        <p class="card-text">Revisa tus pagos realizados y descarga tus tickets de pago.</p>
        <a href="<?= RUTA ?>public/pagos" class="btn btn-success w-100">
          <i class="fas fa-receipt"></i> Ver Mis Pagos
        </a>
      </div>
    </div>
  </div>

  <!-- Psicólogos Disponibles -->
  <div class="col-md-6">
    <div class="card h-100 shadow-sm border-info">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-user-md"></i> Psicólogos</h5>
      </div>
      <div class="card-body">
        <p class="card-text">Consulta los psicólogos disponibles y sus horarios de atención.</p>
        <a href="<?= RUTA ?>public/disponibilidad" class="btn btn-info w-100">
          <i class="fas fa-clock"></i> Ver Disponibilidad
        </a>
      </div>
    </div>
  </div>

  <!-- Solicitar Cambios -->
  <div class="col-md-6">
    <div class="card h-100 shadow-sm border-warning">
      <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-edit"></i> Mis Datos</h5>
      </div>
      <div class="card-body">
        <p class="card-text">Solicita cambios en tu información personal (sujeto a aprobación).</p>
        <a href="<?= RUTA ?>public/solicitud" class="btn btn-warning w-100">
          <i class="fas fa-paper-plane"></i> Solicitar Cambio
        </a>
      </div>
    </div>
  </div>
</div>

<div class="alert alert-light border mt-4">
  <h6 class="alert-heading"><i class="fas fa-info-circle text-info"></i> Información Importante</h6>
  <ul class="mb-0 small">
    <li>Mantén seguro tu código de acceso</li>
    <li>Para agendar nuevas citas, consulta con tu psicólogo asignado</li>
    <li>Guarda tus tickets de pago para futuras referencias</li>
  </ul>
</div>

<?php if (isset($historialRealizadas)): ?>
  <hr>
  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <h3 class="h5 mb-0"><i class="fas fa-history text-primary"></i> Historial de Citas Realizadas</h3>
    <div class="btn-group btn-group-sm">
      <a class="btn btn-outline-danger" href="<?= RUTA ?>public/panel?export=pdf_historial"><i
          class="fas fa-file-pdf"></i> PDF Historial</a>
      <a class="btn btn-outline-success" href="<?= RUTA ?>public/panel?export=excel_historial"><i
          class="fas fa-file-excel"></i> Excel Historial</a>
      <a class="btn btn-outline-warning" target="_blank" href="<?= RUTA ?>public/panel?export=pdf_grafica"><i
          class="fas fa-chart-bar"></i> PDF Gráfico</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php if (empty($historialRealizadas)): ?>
            <p class="text-muted mb-0">Aún no tienes citas marcadas como realizadas.</p>
          <?php else: ?>
            <div class="table-responsive" style="max-height:380px;overflow:auto;">
              <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Fecha/Hora</th>
                    <th>Psicólogo</th>
                    <th>Especialidad</th>
                    <th>Motivo</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($historialRealizadas as $h): ?>
                    <tr>
                      <td><?= htmlspecialchars(substr($h['fecha_hora'], 0, 16)) ?></td>
                      <td><?= htmlspecialchars($h['psicologo'] ?: 'N/D') ?></td>
                      <td><?= htmlspecialchars($h['especialidad'] ?: 'N/D') ?></td>
                      <td><?= htmlspecialchars(mb_strimwidth($h['motivo_consulta'] ?? '', 0, 50, '…', 'UTF-8')) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="fw-semibold mb-3"><i class="fas fa-chart-line text-success"></i> Citas por Mes</h6>
          <canvas id="chartCitasMesPaciente" height="260"></canvas>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php if (isset($citasPorMesPaciente)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    (function () {
      const serie = <?= json_encode($citasPorMesPaciente ?? []) ?>;
      if (!serie.length) return;
      const ctx = document.getElementById('chartCitasMesPaciente');
      if (!ctx) return;
      const labels = serie.map(r => r.mes);
      const data = serie.map(r => parseInt(r.total));
      new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: { labels, datasets: [{ label: 'Citas realizadas', data, borderColor: '#4e73df', backgroundColor: 'rgba(78,115,223,.15)', tension: .25, fill: true }] },
        options: { responsive: true, plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
      });
    })();
  </script>
<?php endif; ?>