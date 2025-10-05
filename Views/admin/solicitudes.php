<h2 class="h5 mb-3">Solicitudes de Cambio Pendientes</h2>
<?php if(!$pendientes): ?>
  <div class="alert alert-info py-2">Sin solicitudes.</div>
<?php else: ?>
<table class="table table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th><th>DUI</th><th>Campo</th><th>Nuevo Valor</th><th>Fecha</th><th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($pendientes as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars($s['dui']) ?></td>
      <td><?= htmlspecialchars($s['campo']) ?></td>
      <td style="max-width:200px"><?= nl2br(htmlspecialchars($s['valor_nuevo'])) ?></td>
      <td><?= $s['fecha'] ?></td>
      <td>
        <form method="post" action="<?= RUTA ?>admin/solicitudProcesar/<?= $s['id'] ?>" class="d-flex gap-1 flex-wrap">
          <input type="hidden" name="id_paciente" value="<?= $s['id_paciente'] ?>">
          <input type="hidden" name="campo_original" value="<?= htmlspecialchars($s['campo']) ?>">
          <input type="hidden" name="valor_nuevo" value="<?= htmlspecialchars($s['valor_nuevo']) ?>">
          <button name="accion" value="aprobar" class="btn btn-success btn-sm"
                  onclick="return confirm('¿Aprobar solicitud #<?= $s['id'] ?>?')">Aprobar</button>
          <button name="accion" value="rechazar" class="btn btn-danger btn-sm"
                  onclick="return confirm('¿Rechazar solicitud #<?= $s['id'] ?>?')">Rechazar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
