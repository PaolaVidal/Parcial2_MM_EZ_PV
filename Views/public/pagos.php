<?php
<h2 class="h6 mb-3">Disponibilidad de Psicólogos</h2>
<?php if(!$psicologos): ?>
  <div class="alert alert-info">No hay psicólogos activos.</div>
<?php else: ?>
<ul class="list-group small" style="max-width:420px">
  <?php foreach($psicologos as $p): ?>
    <li class="list-group-item d-flex justify-content-between">
      <span><?= htmlspecialchars($p['nombre']) ?></span>
      <span class="text-muted"><?= htmlspecialchars($p['especialidad'] ?? '') ?></span>
    </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<a href="<?= RUTA ?>public/portal" class="btn btn-secondary btn-sm mt-3">Volver</a>
