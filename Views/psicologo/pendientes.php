<?php
```php
// filepath: c:\xampp\htdocs\CICLO8_Desarrollo_Web_Multiplataforma\Parcial2_MM_EZ_PV\Views\psicologo\citas_pendientes.php
<h2 class="h5 mb-3">Citas Pendientes</h2>
<?php if(!$citas): ?>
  <div class="alert alert-info">No hay citas pendientes.</div>
<?php endif; ?>
<table class="table table-sm">
  <thead><tr><th>ID</th><th>Fecha</th><th>Motivo</th><th>Acción</th></tr></thead>
  <tbody>
  <?php foreach($citas as $c): ?>
    <tr>
      <td><?= $c['id'] ?></td>
      <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
      <td><?= htmlspecialchars($c['motivo_consulta']) ?></td>
      <td>
        <a class="btn btn-success btn-sm" href="<?= RUTA ?>cita/marcarPagada/<?= $c['id'] ?>"
           onclick="return confirm('Marcar realizada y pagada?');">Marcar Pagada</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
```