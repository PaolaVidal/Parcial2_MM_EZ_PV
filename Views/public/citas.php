<?php
/** @var array $citas */
// Separar citas por estado
$proximas = [];
$historial = [];

foreach($citas as $c) {
    if($c['estado_cita'] === 'pendiente' && strtotime($c['fecha_hora']) >= time()) {
        $proximas[] = $c;
    } else {
        $historial[] = $c;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4 mb-0"><i class="fas fa-calendar-alt text-primary"></i> Mis Citas</h2>
  <a href="<?= RUTA ?>public/panel" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Volver al Panel
  </a>
</div>

<!-- Próximas Citas -->
<div class="card shadow-sm mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0"><i class="fas fa-clock"></i> Próximas Citas (<?= count($proximas) ?>)</h5>
  </div>
  <div class="card-body">
    <?php if(empty($proximas)): ?>
      <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle"></i> No tienes citas programadas próximamente.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Fecha y Hora</th>
              <th>Psicólogo</th>
              <th>Especialidad</th>
              <th>Motivo</th>
              <th class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($proximas as $c): 
              $fecha = new DateTime($c['fecha_hora']);
              $psicologo = htmlspecialchars($c['psicologo_nombre'] ?? 'No asignado');
              $especialidad = htmlspecialchars($c['psicologo_especialidad'] ?? 'N/A');
              $motivo = htmlspecialchars($c['motivo_consulta'] ?? 'Sin especificar');
            ?>
              <tr>
                <td>
                  <strong><?= $fecha->format('d/m/Y') ?></strong><br>
                  <small class="text-muted"><?= $fecha->format('h:i A') ?></small>
                </td>
                <td>
                  <i class="fas fa-user-md text-primary"></i> <?= $psicologo ?>
                </td>
                <td>
                  <span class="badge bg-info"><?= $especialidad ?></span>
                </td>
                <td><?= $motivo ?></td>
                <td class="text-center">
                  <span class="badge bg-warning">
                    <i class="fas fa-hourglass-half"></i> Pendiente
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Historial de Citas -->
<div class="card shadow-sm">
  <div class="card-header bg-secondary text-white">
    <h5 class="mb-0"><i class="fas fa-history"></i> Historial de Citas (<?= count($historial) ?>)</h5>
  </div>
  <div class="card-body">
    <?php if(empty($historial)): ?>
      <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle"></i> No hay historial de citas anteriores.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Fecha y Hora</th>
              <th>Psicólogo</th>
              <th>Motivo</th>
              <th class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($historial as $c): 
              $fecha = new DateTime($c['fecha_hora']);
              $psicologo = htmlspecialchars($c['psicologo_nombre'] ?? 'No asignado');
              $motivo = htmlspecialchars($c['motivo_consulta'] ?? 'Sin especificar');
              $estado = $c['estado_cita'];
              
              if($estado === 'realizada') {
                $badgeClass = 'success';
                $icon = 'check-circle';
                $texto = 'Realizada';
              } else {
                $badgeClass = 'danger';
                $icon = 'times-circle';
                $texto = 'Cancelada';
              }
            ?>
              <tr class="<?= $estado === 'cancelada' ? 'text-muted' : '' ?>">
                <td>
                  <strong><?= $fecha->format('d/m/Y') ?></strong><br>
                  <small class="text-muted"><?= $fecha->format('h:i A') ?></small>
                </td>
                <td>
                  <i class="fas fa-user-md"></i> <?= $psicologo ?>
                </td>
                <td><?= $motivo ?></td>
                <td class="text-center">
                  <span class="badge bg-<?= $badgeClass ?>">
                    <i class="fas fa-<?= $icon ?>"></i> <?= $texto ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>