<h1 class="h3 mb-3">Detalle Cita #<?= htmlspecialchars($cita['id']) ?></h1>
<div class="row">
  <div class="col-md-6">
    <ul class="list-group mb-3">
  <li class="list-group-item"><strong>Paciente:</strong> <?= htmlspecialchars($cita['paciente_nombre'] ?? ('Pac#'.$cita['id_paciente'])) ?></li>
      <li class="list-group-item"><strong>Psicólogo:</strong> <?= htmlspecialchars($cita['id_psicologo']) ?></li>
      <li class="list-group-item"><strong>Fecha:</strong> <?= htmlspecialchars($cita['fecha_hora']) ?></li>
      <li class="list-group-item"><strong>Motivo:</strong> <?= htmlspecialchars($cita['motivo_consulta']) ?></li>
    </ul>
  </div>
  <div class="col-md-6 text-center">
    <h5>QR de la Cita</h5>
    <?php if($cita['qr_code']): ?>
  <img src="<?= htmlspecialchars($cita['qr_code']) ?>" width="200" alt="QR Cita">
    <?php endif; ?>
  </div>
</div>
<a href="<?= url('Cita','index') ?>" class="btn btn-secondary mt-3">Volver</a>
