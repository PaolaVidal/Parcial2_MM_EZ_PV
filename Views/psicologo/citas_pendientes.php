<h2>Citas Pendientes (Vista Legacy)</h2>
<p>Esta es una vista de compatibilidad. Se recomienda usar la nueva pantalla <a href="index.php?controller=Psicologo&action=citas">Mis Citas</a>.</p>
<table class="table table-striped table-sm">
  <thead>
    <tr>
      <th>ID</th>
      <th>Paciente</th>
      <th>Fecha/Hora</th>
      <th>Motivo</th>
      <th>QR</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php if(empty($citas)): ?>
      <tr><td colspan="6" class="text-center text-muted">Sin citas pendientes</td></tr>
    <?php else: foreach($citas as $c): ?>
      <tr>
        <td><?= (int)$c['id'] ?></td>
        <td><?= htmlspecialchars($c['id_paciente']) ?></td>
        <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
        <td><?= htmlspecialchars($c['motivo_consulta']) ?></td>
        <td>
          <?php $img = 'qrcodes/cita_'.$c['qr_code'].'.png'; ?>
          <button class="btn btn-outline-secondary btn-sm" onclick="window.open('<?= htmlspecialchars($img) ?>','_blank')" title="Ver QR">&#128439;</button>
        </td>
        <td>
          <a class="btn btn-sm btn-success" href="index.php?controller=Cita&action=check&token=<?= urlencode($c['qr_code']) ?>">Escanear</a>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
