<?php
// Vista de registro con datos básicos de Usuario + datos opcionales de Paciente
$minDate = '1900-01-01';
$maxDate = date('Y-m-d', strtotime('-5 years'));
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <h1 class="h5 mb-3 text-center">Registro de Paciente</h1>

    <?php if(!empty($error)): ?>
      <div class="alert alert-danger p-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body">
      <h6 class="text-primary">Datos de la Cuenta</h6>
      <div class="row">
        <div class="col-md-6 mb-2">
          <label class="form-label mb-0">Nombre</label>
          <input name="nombre" class="form-control form-control-sm" required
                 value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-2">
          <label class="form-label mb-0">Email</label>
          <input type="email" name="email" class="form-control form-control-sm" required
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-2">
          <label class="form-label mb-0">Contraseña</label>
          <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
        </div>
        <div class="col-md-6 mb-2">
          <label class="form-label mb-0">Confirmar Contraseña</label>
          <input type="password" name="password2" class="form-control form-control-sm" required minlength="6">
        </div>
      </div>

      <hr class="my-3">
      <h6 class="text-primary">Datos del Paciente</h6>

      <div class="row">
        <div class="col-md-4 mb-2">
          <label class="form-label mb-0">Fecha Nacimiento</label>
          <input type="date" name="fecha_nacimiento" class="form-control form-control-sm" required
                 min="<?= $minDate ?>" max="<?= $maxDate ?>"
                 value="<?= htmlspecialchars($old['fecha_nacimiento'] ?? '') ?>">
        </div>
        <div class="col-md-4 mb-2">
          <label class="form-label mb-0">Género</label>
          <select name="genero" class="form-select form-select-sm" required>
            <option value="">--</option>
            <?php
              $gsel = $old['genero'] ?? '';
              foreach (['masculino'=>'Masculino','femenino'=>'Femenino','otro'=>'Otro'] as $k=>$v) {
                  $sel = $gsel === $k ? 'selected' : '';
                  echo "<option value=\"$k\" $sel>$v</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-md-4 mb-2">
          <label class="form-label mb-0">Teléfono</label>
          <input name="telefono" class="form-control form-control-sm" required
                 pattern="[0-9+\-\s]{7,20}"
                 title="Sólo dígitos, +, - y espacios. 7-20 caracteres."
                 value="<?= htmlspecialchars($old['telefono'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-2">
        <label class="form-label mb-0">Dirección</label>
        <input name="direccion" class="form-control form-control-sm" required
               value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label mb-0">Historial Clínico (breve)</label>
        <textarea name="historial_clinico" rows="3" class="form-control form-control-sm" required><?= htmlspecialchars($old['historial_clinico'] ?? '') ?></textarea>
      </div>

      <button class="btn btn-success btn-sm w-100">Crear cuenta</button>
      <a href="<?= RUTA ?>auth/login" class="btn btn-secondary btn-sm w-100 mt-2">Volver a Login</a>
    </form>
  </div>
</div>
<script>
  // Protección adicional si el navegador ignora límites
  (function(){
    const input = document.querySelector('input[name="fecha_nacimiento"]');
    if(!input) return;
    const min = input.min;
    const max = input.max;
    input.addEventListener('change', () => {
      if(input.value){
        if(input.value < min) input.value = min;
        if(input.value > max) input.value = max;
      }
    });
  })();
</script>
