<?php
// filepath: c:\xampp\htdocs\CICLO8_Desarrollo_Web_Multiplataforma\Parcial2_MM_EZ_PV\Views\auth\login.php
?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <h1 class="h4 mb-3 text-center">Iniciar Sesión</h1>
    <?php if(!empty($error)): ?>
      <div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="card card-body">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100">Entrar</button>
      <div class="text-center mt-2">
        <a href="<?= RUTA ?>auth/registrar">Crear cuenta</a>
      </div>
    </form>
  </div>
</div>
