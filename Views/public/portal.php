
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="text-center mb-5">
      <h1 class="display-5 mb-3">
        <i class="fas fa-brain text-primary"></i> Portal del Paciente
      </h1>
      <p class="lead text-muted">Accede a tu información médica y gestiona tus citas</p>
    </div>
    
    <div class="row g-4">
      <div class="col-md-6">
        <div class="card h-100 shadow-sm hover-shadow">
          <div class="card-body text-center p-4">
            <div class="mb-3">
              <i class="fas fa-sign-in-alt fa-3x text-primary"></i>
            </div>
            <h3 class="h5 mb-3">Acceder a Mi Panel</h3>
            <p class="text-muted">Ingresa con tu DUI y código de acceso para ver tus citas, pagos y más.</p>
            <a href="<?= RUTA ?>public/acceso" class="btn btn-primary btn-lg w-100">
              <i class="fas fa-user-lock"></i> Iniciar Sesión
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100 shadow-sm hover-shadow">
          <div class="card-body text-center p-4">
            <div class="mb-3">
              <i class="fas fa-user-md fa-3x text-info"></i>
            </div>
            <h3 class="h5 mb-3">Psicólogos Disponibles</h3>
            <p class="text-muted">Consulta nuestros psicólogos disponibles y sus horarios de atención.</p>
            <a href="<?= RUTA ?>public/disponibilidad" class="btn btn-info btn-lg w-100">
              <i class="fas fa-clock"></i> Ver Horarios
            </a>
          </div>
        </div>
      </div>
    </div>
    
    <div class="alert alert-info mt-5">
      <h6 class="alert-heading"><i class="fas fa-question-circle"></i> ¿No tienes código de acceso?</h6>
      <p class="mb-0">El código de acceso se genera automáticamente al registrarte como paciente. Consulta con el personal administrativo o tu psicólogo asignado.</p>
    </div>
  </div>
</div>

<style>
.hover-shadow {
  transition: all 0.3s ease;
}
.hover-shadow:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15)!important;
}
</style>
