<?php /* Gestion de Horarios */ ?>
<h1 class="h5 mb-3">Horarios de Psicólogos</h1>
<?php if(isset($_GET['ok'])): ?><div class="alert alert-success py-1">Operación realizada.</div><?php endif; ?>
<?php if(isset($_GET['err'])): ?><div class="alert alert-danger py-1">Error: <?= htmlspecialchars($_GET['err']) ?></div><?php endif; ?>
<form class="row g-2 mb-3" method="get" action="<?= url('admin','horarios') ?>">
  <div class="col-md-4">
    <select name="ps" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">-- Selecciona Psicólogo --</option>
      <?php foreach($psicologos as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= $idSel==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']??('Psicólogo #'.$p['id'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-center small text-muted">Total: <?= count($horarios) ?></div>
</form>
<?php if($idSel): ?>
<div class="card mb-3">
  <div class="card-header py-2">Agregar horario</div>
  <div class="card-body py-2">
    <form class="row g-2 align-items-end" method="post" action="<?= url('admin','horarios') ?>">
      <input type="hidden" name="accion" value="crear">
      <input type="hidden" name="id_psicologo" value="<?= (int)$idSel ?>">
      <div class="col-md-3">
        <label class="form-label small mb-1">Día</label>
        <select name="dia_semana" class="form-select form-select-sm" required>
          <?php $dias=['lunes','martes','miércoles','jueves','viernes','sábado','domingo']; foreach($dias as $d): ?>
            <option value="<?= $d ?>"><?= ucfirst($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Inicio</label>
        <input name="hora_inicio" type="time" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Fin</label>
        <input name="hora_fin" type="time" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-3">
        <button class="btn btn-sm btn-primary">Agregar</button>
      </div>
    </form>
  </div>
</div>

<table class="table table-sm table-striped">
  <thead><tr><th>Día</th><th>Inicio</th><th>Fin</th><th></th></tr></thead>
  <tbody>
  <?php if(empty($horarios)): ?>
    <tr><td colspan="4" class="text-muted">Sin horarios definidos para este psicólogo.</td></tr>
  <?php else: foreach($horarios as $h): ?>
    <tr>
      <td><?= htmlspecialchars($h['dia_semana']) ?></td>
      <td><?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?></td>
      <td><?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></td>
      <td>
        <form method="post" action="<?= url('admin','horarios') ?>" onsubmit="return confirm('Eliminar horario?');" class="d-inline">
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="id_horario" value="<?= (int)$h['id_horario_psicologo'] ?>">
          <button class="btn btn-sm btn-outline-danger">Eliminar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
<?php else: ?>
<div class="alert alert-info">Seleccione un psicólogo para gestionar sus horarios.</div>
<?php endif; ?>
