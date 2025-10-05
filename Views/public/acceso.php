
<h2 class="h6 mb-3">Validar Código de Acceso</h2>
<?php if(!empty($msg)): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card card-body p-3" style="max-width:420px">
  <div class="mb-2">
    <label class="form-label mb-0">DUI</label>
    <input name="dui" class="form-control form-control-sm" required value="<?= htmlspecialchars($dui) ?>">
  </div>
  <div class="mb-2">
    <label class="form-label mb-0">Código Acceso</label>
    <input name="codigo" class="form-control form-control-sm" required>
  </div>
  <button class="btn btn-success btn-sm">Entrar</button>
</form>