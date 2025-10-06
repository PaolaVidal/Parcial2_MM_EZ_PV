<?php
<?php /* layout por index */ ?>
<h1 class="h5 mb-3">Pacientes</h1>

<form class="row g-2 mb-3" method="post" action="<?= url('admin','pacientes') ?>">
  <input type="hidden" name="accion" value="crear">
  <div class="col-md-2"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
  <div class="col-md-2"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email" required></div>
  <div class="col-md-2"><input name="password" type="text" class="form-control form-control-sm" placeholder="Password" required></div>
  <div class="col-md-2"><input name="telefono" class="form-control form-control-sm" placeholder="Teléfono"></div>
  <div class="col-md-3"><input name="direccion" class="form-control form-control-sm" placeholder="Dirección"></div>
  <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Crear</button></div>
</form>

<table class="table table-sm align-middle">
  <thead class="table-light">
    <tr><th>ID</th><th>Nombre / Email</th><th>Teléfono</th><th>Dirección</th><th>Estado</th><th>Nueva Password</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach($pacientes as $p): ?>
    <tr>
      <form method="post" action="<?= url('admin','pacientes') ?>">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="id_usuario" value="<?= $p['id_usuario'] ?>">
        <td><?= $p['id'] ?></td>
        <td style="min-width:220px">
          <input name="nombre" value="<?= htmlspecialchars($p['nombre']??'') ?>" class="form-control form-control-sm mb-1">
          <input name="email" value="<?= htmlspecialchars($p['email']??'') ?>" class="form-control form-control-sm">
        </td>
        <td><input name="telefono" value="<?= htmlspecialchars($p['telefono']??'') ?>" class="form-control form-control-sm"></td>
        <td><input name="direccion" value="<?= htmlspecialchars($p['direccion']??'') ?>" class="form-control form-control-sm"></td>
        <td>
          <form method="post" action="<?= url('admin','pacientes') ?>" class="d-inline">
            <input type="hidden" name="accion" value="estado">
            <input type="hidden" name="id_usuario" value="<?= $p['id_usuario'] ?>">
            <input type="hidden" name="estado" value="<?= ($p['estado']??'activo')==='activo'?'inactivo':'activo' ?>">
            <button class="btn btn-sm <?= ($p['estado']??'')==='activo'?'btn-outline-warning':'btn-outline-secondary' ?>">
              <?= ($p['estado']??'')==='activo'?'Desactivar':'Activar' ?>
            </button>
          </form>
        </td>
        <td><input name="new_password" type="text" placeholder="Nuevo pass" class="form-control form-control-sm"></td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-outline-success mb-1">Guardar</button>
          <form method="post" action="<?= url('admin','pacientes') ?>" class="d-inline" onsubmit="return confirm('Eliminar?')">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">X</button>
          </form>
        </td>
      </form>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php if(empty($pacientes)): ?><div class="alert alert-info">Sin pacientes.</div><?php endif; ?>