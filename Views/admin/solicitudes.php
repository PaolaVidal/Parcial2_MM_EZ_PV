<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0"><i class="fas fa-inbox"></i> Solicitudes de Cambio Pendientes</h2>
</div>

<?php if(isset($_SESSION['msg_solicitud'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['msg_solicitud']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['msg_solicitud']); ?>
<?php endif; ?>

<?php if(!$pendientes): ?>
  <div class="alert alert-info py-2"><i class="fas fa-info-circle"></i> No hay solicitudes pendientes.</div>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th width="60">ID</th>
        <th width="120">DUI Paciente</th>
        <th width="120">Campo</th>
        <th>Nuevo Valor</th>
        <th width="140">Fecha Solicitud</th>
        <th width="180" class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($pendientes as $s): ?>
      <tr>
        <td><span class="badge bg-secondary">#<?= $s['id'] ?></span></td>
        <td><strong><?= htmlspecialchars($s['dui'] ?? 'N/A') ?></strong></td>
        <td>
          <span class="badge bg-info">
            <?php 
            $campoDisplay = match($s['campo']) {
              'nombre' => 'Nombre',
              'email' => 'Email',
              'telefono' => 'Teléfono',
              'direccion' => 'Dirección',
              'fecha_nacimiento' => 'Fecha Nac.',
              'genero' => 'Género',
              default => $s['campo']
            };
            echo htmlspecialchars($campoDisplay);
            ?>
          </span>
        </td>
        <td style="max-width:250px" class="text-break"><?= nl2br(htmlspecialchars($s['valor_nuevo'])) ?></td>
        <td><small class="text-muted"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($s['fecha'])) ?></small></td>
        <td class="text-center">
          <form method="post" action="<?= url('Solicitud','procesar',['id'=>$s['id']]) ?>" class="d-inline">
            <input type="hidden" name="id_paciente" value="<?= $s['id_paciente'] ?>">
            <input type="hidden" name="campo_original" value="<?= htmlspecialchars($s['campo']) ?>">
            <input type="hidden" name="valor_nuevo" value="<?= htmlspecialchars($s['valor_nuevo']) ?>">
            <button name="accion" value="aprobar" class="btn btn-success btn-sm"
                    onclick="return confirm('¿Aprobar y aplicar el cambio para la solicitud #<?= $s['id'] ?>?')">
              <i class="fas fa-check"></i> Aprobar
            </button>
            <button name="accion" value="rechazar" class="btn btn-danger btn-sm ms-1"
                    onclick="return confirm('¿Rechazar solicitud #<?= $s['id'] ?>?')">
              <i class="fas fa-times"></i> Rechazar
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
