<?php /* layout por index */ ?>
<h1 class="h5 mb-3">Pacientes</h1>

<form class="row g-2 mb-4" method="post" action="<?= url('admin','pacientes') ?>">
  <input type="hidden" name="accion" value="crear">
  <div class="col-md-2"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
  <div class="col-md-2"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email"></div>
  <div class="col-md-2"><input name="telefono" maxlength="9" class="form-control form-control-sm" placeholder="Teléfono (####-####)" pattern="^[0-9]{4}-[0-9]{4}$" oninput="maskTel(this)"></div>
  <div class="col-md-2"><input name="dui" maxlength="10" class="form-control form-control-sm" placeholder="DUI (########-#)" pattern="^[0-9]{8}-[0-9]{1}$" oninput="maskDui(this)"></div>
  <div class="col-md-2"><input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="form-control form-control-sm"></div>
  <div class="col-md-2">
    <select name="genero" class="form-select form-select-sm">
      <option value="">Género</option>
      <option value="masculino">Masculino</option>
      <option value="femenino">Femenino</option>
      <option value="otro">Otro</option>
    </select>
  </div>
  <div class="col-md-3"><input name="direccion" class="form-control form-control-sm" placeholder="Dirección"></div>
  <div class="col-md-5"><textarea name="historial_clinico" rows="2" class="form-control form-control-sm" placeholder="Historial clínico"></textarea></div>
  <div class="col-md-2 d-grid"><button class="btn btn-sm btn-primary">Crear</button></div>
</form>

<table class="table table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>DUI</th><th>Nacimiento</th>
      <th>Género</th><th>Código</th><th>Estado</th><th>Dirección</th><th style="min-width:180px">Historial</th><th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($pacientes as $p):
    $nuevoEstado = ($p['estado']??'activo')==='activo'?'inactivo':'activo';
    $labelEstado = ($p['estado']??'activo')==='activo'?'Desactivar':'Activar';
    $clsEstado   = ($p['estado']??'activo')==='activo'?'btn-outline-warning':'btn-outline-secondary';
  ?>
    <tr>
      <form method="post" action="<?= url('admin','pacientes') ?>">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <td><?= $p['id'] ?></td>
        <td><input name="nombre" value="<?= htmlspecialchars($p['nombre']??'') ?>" class="form-control form-control-sm" required></td>
        <td><input name="email" value="<?= htmlspecialchars($p['email']??'') ?>" type="email" class="form-control form-control-sm"></td>
        <td><input name="telefono" value="<?= htmlspecialchars($p['telefono']??'') ?>" maxlength="9" class="form-control form-control-sm" style="width:110px" pattern="^[0-9]{4}-[0-9]{4}$" oninput="maskTel(this)"></td>
        <td><input name="dui" value="<?= htmlspecialchars($p['dui']??'') ?>" maxlength="10" class="form-control form-control-sm" style="width:120px" pattern="^[0-9]{8}-[0-9]{1}$" oninput="maskDui(this)"></td>
        <td><input name="fecha_nacimiento" type="date" value="<?= htmlspecialchars($p['fecha_nacimiento']??'') ?>" class="form-control form-control-sm" style="width:135px"></td>
        <td>
          <select name="genero" class="form-select form-select-sm">
            <option value=""></option>
            <?php foreach(['masculino','femenino','otro'] as $g): ?>
              <option value="<?= $g ?>" <?= ($p['genero']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td class="text-nowrap">
          <button name="accion" value="regen_code" class="btn btn-sm btn-outline-secondary" title="Regenerar">↻</button>
          <span class="badge text-bg-light"><?= htmlspecialchars($p['codigo_acceso']??'') ?></span>
        </td>
        <td>
          <button name="accion" value="estado" class="btn btn-sm <?= $clsEstado ?>" onclick="this.form.estado.value='<?= $nuevoEstado ?>';">
            <?= $labelEstado ?>
          </button>
          <input type="hidden" name="estado" value="<?= $nuevoEstado ?>">
        </td>
        <td><input name="direccion" value="<?= htmlspecialchars($p['direccion']??'') ?>" class="form-control form-control-sm" style="width:140px"></td>
        <td><textarea name="historial_clinico" rows="2" class="form-control form-control-sm"><?= htmlspecialchars($p['historial_clinico']??'') ?></textarea></td>
        <td><button name="accion" value="editar" class="btn btn-sm btn-outline-success">Guardar</button></td>
      </form>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if(empty($pacientes)): ?>
  <div class="alert alert-info">Sin pacientes.</div>
<?php endif; ?>

<script>
(function(){
  const d = document.getElementById('fecha_nacimiento');
  if(d){
    const hoy = new Date();
    const y = hoy.getFullYear();
    d.max = hoy.toISOString().slice(0,10);
    d.min = '1900-01-01';
  }
  
  // Aplicar máscaras antes de enviar CUALQUIER formulario
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
      console.log('🔧 Pre-submit: Formateando campos...');
      
      // Formatear todos los DUIs antes de enviar
      this.querySelectorAll('input[name="dui"]').forEach(input => {
        const original = input.value;
        if(input.value) {
          let v = input.value.replace(/\D/g,'').slice(0,9);
          if(v.length === 9) {
            input.value = v.slice(0,8)+'-'+v.slice(8);
            console.log('✅ DUI formateado:', original, '→', input.value);
          } else {
            console.log('⚠️ DUI no tiene 9 dígitos:', original, '('+v.length+' dígitos)');
          }
        }
      });
      
      // Formatear todos los teléfonos antes de enviar
      this.querySelectorAll('input[name="telefono"]').forEach(input => {
        const original = input.value;
        if(input.value) {
          let v = input.value.replace(/\D/g,'').slice(0,8);
          if(v.length === 8) {
            input.value = v.slice(0,4)+'-'+v.slice(4);
            console.log('✅ Tel formateado:', original, '→', input.value);
          }
        }
      });
    });
  });
})();

function maskTel(el){
  let v = el.value.replace(/\D/g,'').slice(0,8);
  if(v.length > 4) v = v.slice(0,4)+'-'+v.slice(4);
  el.value = v;
}

function maskDui(el){
  let v = el.value.replace(/\D/g,'').slice(0,9);
  if(v.length > 8) v = v.slice(0,8)+'-'+v.slice(8);
  el.value = v;
}
</script>