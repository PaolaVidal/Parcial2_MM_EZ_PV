<?php
```php
// filepath: c:\xampp\htdocs\CICLO8_Desarrollo_Web_Multiplataforma\Parcial2_MM_EZ_PV\Views\psicologo\cita_scan.php
<h2 class="h5 mb-3">Cita Escaneada</h2>
<?php if($cita): ?>
<ul class="small">
  <li>ID: <?= $cita['id'] ?></li>
  <li>Fecha: <?= htmlspecialchars($cita['fecha_hora']) ?></li>
  <li>Estado: <?= $cita['estado_cita'] ?></li>
  <li>Motivo: <?= htmlspecialchars($cita['motivo_consulta']) ?></li>
</ul>
<?php if($cita['estado_cita']==='pendiente'): ?>
  <a class="btn btn-success btn-sm" href="<?= RUTA ?>cita/marcarPagada/<?= $cita['id'] ?>">Marcar Pagada</a>
<?php else: ?>
  <div class="alert alert-info">Ya procesada.</div>
<?php endif; ?>
<?php endif; ?>
```