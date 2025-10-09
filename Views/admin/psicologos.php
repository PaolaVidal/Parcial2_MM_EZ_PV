
<?php /* layout por index */ ?>
<h1 class="h5 mb-3">Psicólogos</h1>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-1 mb-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($ok)): ?>
  <div class="alert alert-success py-1 mb-2"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form class="row g-2 mb-3" method="post" action="<?= url('admin', 'psicologos') ?>">
  <input type="hidden" name="accion" value="crear">
  <div class="col-md-2"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
  <div class="col-md-2"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email"
      required></div>
  <div class="col-md-2"><input name="password" type="text" class="form-control form-control-sm" placeholder="Password"
      required></div>
  <div class="col-md-2">
    <select name="id_especialidad" class="form-select form-select-sm" required>
      <option value="">Especialidad</option>
      <?php foreach ($especialidades as $esp): ?>
        <option value="<?= (int) $esp['id'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><input name="experiencia" class="form-control form-control-sm" placeholder="Experiencia"></div>
  <div class="col-12 mt-2"><button class="btn btn-sm btn-primary">Crear</button></div>
</form>

<table class="table table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Email</th>
      <th>Especialidad</th>
      <th>Experiencia</th>
      <th>Estado</th>
      <th>Nuevo Password</th>
      <th>Acciones</th>
      <th>Horarios</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($psicologos as $p):
      $nuevoEstado = ($p['estado'] ?? 'activo') === 'activo' ? 'inactivo' : 'activo';
      $labelEstado = ($p['estado'] ?? 'activo') === 'activo' ? 'Desactivar' : 'Activar';
      $btnEstadoCls = ($p['estado'] ?? 'activo') === 'activo' ? 'btn-outline-warning' : 'btn-outline-secondary';
      ?>
      <tr>
        <form method="post" action="<?= url('admin', 'psicologos') ?>">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <input type="hidden" name="id_usuario" value="<?= $p['id_usuario'] ?>">
          <td><?= $p['id'] ?></td>
          <td><input name="nombre" value="<?= htmlspecialchars($p['nombre'] ?? '') ?>"
              class="form-control form-control-sm">
          </td>
          <td><input name="email" value="<?= htmlspecialchars($p['email'] ?? '') ?>" class="form-control form-control-sm">
          </td>
          <td>
            <select name="id_especialidad" class="form-select form-select-sm" required>
              <option value="">Seleccionar</option>
              <?php foreach ($especialidades as $esp): ?>
                <option value="<?= (int) $esp['id'] ?>" <?= (int) $p['id_especialidad'] === (int) $esp['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($esp['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input name="experiencia" value="<?= htmlspecialchars($p['experiencia'] ?? '') ?>"
              class="form-control form-control-sm"></td>
          <td>
            <button name="accion" value="estado" class="btn btn-sm <?= $btnEstadoCls ?>"
              onclick="this.form.estado.value='<?= $nuevoEstado ?>';">
              <?= $labelEstado ?>
            </button>
            <input type="hidden" name="estado" value="<?= $nuevoEstado ?>">
          </td>
          <td><input name="new_password" type="text" placeholder="Nuevo" class="form-control form-control-sm"></td>
          <td class="text-nowrap">
            <button name="accion" value="editar" class="btn btn-sm btn-outline-success">Guardar</button>
          </td>
          <td><a class="btn btn-sm btn-outline-primary" href="<?= url('admin', 'horarios') ?>?ps=<?= (int) $p['id'] ?>"
              title="Gestionar horarios">Gestionar</a></td>
        </form>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php if (empty($psicologos)): ?>
  <div class="alert alert-info">Sin psicólogos.</div><?php endif; ?>

<h2 class="h6 mt-4">Más solicitados</h2>
<table class="table table-sm">
  <thead>
    <tr>
      <th>Psicólogo</th>
      <th>Citas</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($masSolic as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['nombre'] ?? ($m['id_psicologo'] ?? '')) ?></td>
        <td><?= $m['total'] ?? $m['citas'] ?? '' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>