<?php
/** @var array $pagos */
$totalMonto = 0;
$pagados = 0;
$pendientes = 0;

foreach($pagos as $p) {
    $totalMonto += (float)($p['monto_total'] ?? 0);
    if(($p['estado_pago'] ?? '') === 'pagado') $pagados++;
    else $pendientes++;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4 mb-0"><i class="fas fa-file-invoice-dollar text-success"></i> Mis Pagos y Tickets</h2>
  <a href="<?= RUTA ?>public/panel" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Volver al Panel
  </a>
</div>

<!-- Resumen de Pagos -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card text-center border-primary">
      <div class="card-body">
        <h6 class="text-muted mb-1">Total Pagado</h6>
        <h3 class="mb-0 text-primary">$<?= number_format($totalMonto, 2) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center border-success">
      <div class="card-body">
        <h6 class="text-muted mb-1">Pagos Completados</h6>
        <h3 class="mb-0 text-success"><?= $pagados ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center border-warning">
      <div class="card-body">
        <h6 class="text-muted mb-1">Pagos Pendientes</h6>
        <h3 class="mb-0 text-warning"><?= $pendientes ?></h3>
      </div>
    </div>
  </div>
</div>

<!-- Lista de Pagos -->
<div class="card shadow-sm">
  <div class="card-header bg-success text-white">
    <h5 class="mb-0"><i class="fas fa-list"></i> Historial de Pagos</h5>
  </div>
  <div class="card-body">
    <?php if(empty($pagos)): ?>
      <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle"></i> No tienes pagos registrados.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Fecha</th>
              <th>Cita</th>
              <th>Monto</th>
              <th class="text-center">Estado</th>
              <th class="text-center">Ticket</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pagos as $p): 
              $fecha = new DateTime($p['fecha']);
              $monto = (float)($p['monto_total'] ?? 0);
              $estado = $p['estado_pago'] ?? 'pendiente';
              $citaFecha = !empty($p['cita_fecha']) ? (new DateTime($p['cita_fecha']))->format('d/m/Y') : 'N/A';
              $citaMotivo = htmlspecialchars($p['cita_motivo'] ?? 'Sin especificar');
              $tieneTicket = !empty($p['ticket_id']);
              $numeroTicket = htmlspecialchars($p['numero_ticket'] ?? '');
              
              if($estado === 'pagado') {
                $badgeClass = 'success';
                $icon = 'check-circle';
                $texto = 'Pagado';
              } else {
                $badgeClass = 'warning';
                $icon = 'clock';
                $texto = 'Pendiente';
              }
            ?>
              <tr>
                <td>
                  <strong><?= $fecha->format('d/m/Y') ?></strong><br>
                  <small class="text-muted"><?= $fecha->format('h:i A') ?></small>
                </td>
                <td>
                  <div>
                    <i class="fas fa-calendar text-primary"></i> <?= $citaFecha ?>
                  </div>
                  <small class="text-muted"><?= $citaMotivo ?></small>
                </td>
                <td>
                  <strong class="text-primary">$<?= number_format($monto, 2) ?></strong>
                </td>
                <td class="text-center">
                  <span class="badge bg-<?= $badgeClass ?>">
                    <i class="fas fa-<?= $icon ?>"></i> <?= $texto ?>
                  </span>
                </td>
                <td class="text-center">
                  <?php if($tieneTicket): ?>
                    <i class="fas fa-ticket-alt text-success"></i>
                    <small class="d-block text-muted"><?= $numeroTicket ?></small>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if($tieneTicket): ?>
                    <a href="<?= RUTA ?>ticket/ver/<?= (int)$p['ticket_id'] ?>" 
                       class="btn btn-sm btn-primary me-1" title="Ver Ticket">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= RUTA ?>ticket/pdf/<?= (int)$p['ticket_id'] ?>" 
                       class="btn btn-sm btn-danger" target="_blank" title="Descargar PDF">
                      <i class="fas fa-file-pdf"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">Sin ticket</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="alert alert-info mt-3">
  <strong><i class="fas fa-info-circle"></i> Nota:</strong>
  Los tickets de pago son generados automáticamente al completar el pago. 
  Puedes descargarlos en formato PDF para tus registros.
</div>
