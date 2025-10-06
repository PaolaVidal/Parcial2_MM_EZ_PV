
<h1 class="h4 mb-3">Gestión de Psicólogos</h1>
<div class="row">
  <div class="col-md-4">
    <div class="card card-body mb-3">
      <h6 class="text-primary">Registrar Psicólogo</h6>
      <form method="post">
        <input type="hidden" name="accion" value="crear">
        <div class="mb-2"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
        <div class="mb-2"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email" required></div>
        <div class="mb-2"><input name="password" type="password" class="form-control form-control-sm" placeholder="Password" required></div>
        <div class="mb-2"><input name="especialidad" class="form-control form-control-sm" placeholder="Especialidad"></div>
        <button class="btn btn-success btn-sm w-100">Guardar</button>
      </form>
    </div>
    <div class="card card-body">
      <h6 class="text-muted">Más Solicitados</h6>
      <ol class="small mb-0">
        <?php foreach($masSolic as $m): ?>
          <li>Psicólogo ID <?= $m['id_psicologo'] ?> (<?= $m['total'] ?> citas)</li>
        <?php endforeach; ?>
        <?php if(empty($masSolic)): ?><li>Sin datos</li><?php endif; ?>
      </ol>
    </div>
  </div>
  <div class="col-md-8">
    <table class="table table-sm align-middle">
      <thead class="table-light"><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Especialidad</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach($psicologos as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td><?= htmlspecialchars($p['email']) ?></td>
          <td><?= htmlspecialchars($p['especialidad']) ?></td>
          <td><?= $p['estado'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__.'/../layout/footer.php'; ?>