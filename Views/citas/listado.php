<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3">Listado de Citas</h1>
  <a href="?controller=Cita&action=crear" class="btn btn-primary">Nueva Cita</a>
</div>
<table class="table table-striped table-bordered align-middle">
  <thead class="table-dark">
    <tr>
  <th>ID</th><th>Paciente</th><th>Psicólogo</th><th>Fecha/Hora</th><th>Motivo</th><th>QR</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($citas as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['id']) ?></td>
  <td><?= htmlspecialchars($c['paciente_nombre'] ?? ('Pac#'.$c['id_paciente'])) ?></td>
        <td><?= htmlspecialchars($c['id_psicologo']) ?></td>
        <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
        <td><?= htmlspecialchars($c['motivo_consulta']) ?></td>
        <td>
          <?php if(!empty($c['qr_code'])): ?>
            <img src="<?= htmlspecialchars($c['qr_code']) ?>" alt="QR" width="80">
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
