
<h2 class="h6 mb-3">Buscar Paciente por DUI</h2>
<?php if(!empty($msg)): ?><div class="alert alert-info py-2"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card card-body p-3" style="max-width:400px">
  <label class="form-label mb-1">DUI</label>
  <input name="dui" class="form-control form-control-sm" required value="<?= htmlspecialchars($dui??'') ?>">
  <button class="btn btn-primary btn-sm mt-2">Continuar</button>
</form>