<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-user-lock"></i> Acceso para Pacientes</h5>
      </div>
      <div class="card-body">
        <?php if(!empty($msg)): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg) ?>
          </div>
        <?php endif; ?>
        
        <form method="post">
          <div class="mb-3">
            <label class="form-label"><i class="fas fa-id-card"></i> DUI</label>
            <input name="dui" id="inputDui" class="form-control" placeholder="00000000-0" required 
                   value="<?= htmlspecialchars($dui ?? '') ?>" pattern="^[0-9]{8}-[0-9]{1}$" maxlength="10"
                   oninput="maskDuiPublic(this)">
            <small class="text-muted">Formato: 12345678-9</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><i class="fas fa-key"></i> Código de Acceso</label>
            <input name="codigo" class="form-control" placeholder="Código de 8 caracteres" 
                   required maxlength="8" value="<?= htmlspecialchars($codigo ?? '') ?>">
            <small class="text-muted">Proporcionado al registrarte</small>
          </div>
          
          <div class="d-grid">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-sign-in-alt"></i> Entrar a mi Panel
            </button>
          </div>
        </form>
        
        <hr class="my-3">
        <div class="text-center">
          <a href="<?= RUTA ?>public/portal" class="text-muted">
            <i class="fas fa-arrow-left"></i> Volver al inicio
          </a>
        </div>
      </div>
    </div>
    
    <div class="alert alert-info mt-3">
      <strong><i class="fas fa-info-circle"></i> ¿No tienes código de acceso?</strong><br>
      <small>El código se genera automáticamente al registrarte como paciente. Consulta con tu psicólogo.</small>
    </div>
  </div>
</div>

<script>
function maskDuiPublic(el) {
  let v = el.value.replace(/\D/g,'').slice(0,9);
  if(v.length > 8) v = v.slice(0,8)+'-'+v.slice(8);
  el.value = v;
}

// Asegurar que el formato se aplica antes de enviar
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('form');
  const duiInput = document.getElementById('inputDui');
  
  if(form && duiInput) {
    form.addEventListener('submit', function(e) {
      // Formatear DUI antes de enviar
      let v = duiInput.value.replace(/\D/g,'').slice(0,9);
      if(v.length === 9) {
        duiInput.value = v.slice(0,8)+'-'+v.slice(8);
      }
    });
  }
});
</script>