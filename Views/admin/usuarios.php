
<h1 class="h4 mb-3">Administrar Usuarios</h1>
<?php if(!empty($msg)): ?><div class="alert alert-warning py-2"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="row">
  <div class="col-md-4">
    <div class="card card-body mb-3">
      <h6 class="text-primary">Nuevo Usuario</h6>
      <form method="post">
        <input type="hidden" name="accion" value="crear">
        <div class="mb-2"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
        <div class="mb-2"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email" required></div>
        <div class="mb-2"><select name="rol" class="form-select form-select-sm">
          <option value="paciente">Paciente</option>
          <option value="psicologo">Psicólogo</option>
          <option value="admin">Admin</option>
        </select></div>
        <div class="mb-2"><input name="password" type="password" class="form-control form-control-sm" placeholder="Password" required></div>
        <button class="btn btn-success btn-sm w-100">Guardar</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <table class="table table-sm align-middle">
      <thead class="table-light"><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach($usuarios as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['rol'] ?></td>
            <td><span class="badge bg-<?= $u['estado']==='activo'?'success':'secondary' ?>"><?= $u['estado'] ?></span></td>
            <td class="text-nowrap">
              <form method="post" class="d-inline">
                <input type="hidden" name="accion" value="estado">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="hidden" name="estado" value="<?= $u['estado']==='activo'?'inactivo':'activo' ?>">
                <button class="btn btn-sm btn-outline-warning" title="Toggle" onclick="return confirm('Cambiar estado?')">⚙</button>
              </form>
              <form method="post" class="d-inline">
                <input type="hidden" name="accion" value="reset">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Resetear password a Temp1234?')">🔑</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__.'/../layout/footer.php'; ?>