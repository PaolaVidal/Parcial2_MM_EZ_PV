<?php
$nombre = htmlspecialchars($paciente['nombre'] ?? 'Paciente');
$dui = htmlspecialchars($paciente['dui'] ?? 'N/A');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h4 mb-1"><i class="fas fa-user-circle text-primary"></i> Bienvenido, <?= $nombre ?></h2>
    <p class="text-muted mb-0"><i class="fas fa-id-card"></i> DUI: <?= $dui ?></p>
  </div>
  <a href="<?= RUTA ?>public/salir" class="btn btn-outline-danger btn-sm">
    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
  </a>
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