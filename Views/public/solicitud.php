<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-edit"></i> Solicitar Cambio en Mis Datos</h5>
      </div>
      <div class="card-body">
        <?php if(!empty($msg)): ?>
          <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>">
            <i class="fas fa-<?= $ok ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= htmlspecialchars($msg) ?>
          </div>
          <?php if($ok): ?>
            <div class="d-grid gap-2">
              <a href="<?= RUTA ?>public/panel" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver al Panel
              </a>
            </div>
            <?php return; ?>
          <?php endif; ?>
        <?php endif; ?>
        
        <div class="alert alert-info">
          <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Información Importante</h6>
          <ul class="mb-0 small">
            <li>Las solicitudes de cambio deben ser aprobadas por un administrador</li>
            <li>Solo puedes solicitar cambios en: <strong>Nombre, Email, Teléfono, Dirección y Fecha de Nacimiento</strong></li>
            <li>El DUI no puede ser modificado por razones de seguridad</li>
            <li>Recibirás confirmación una vez que tu solicitud sea procesada</li>
          </ul>
        </div>
        
        <form method="post">
          <div class="mb-3">
            <label class="form-label"><i class="fas fa-list"></i> Campo a Modificar <span class="text-danger">*</span></label>
            <select name="campo" class="form-select" required>
              <option value="">-- Selecciona un campo --</option>
              <option value="nombre">Nombre Completo</option>
              <option value="email">Correo Electrónico</option>
              <option value="telefono">Teléfono</option>
              <option value="direccion">Dirección</option>
              <option value="fecha_nacimiento">Fecha de Nacimiento</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><i class="fas fa-pen"></i> Nuevo Valor <span class="text-danger">*</span></label>
            <input type="text" name="valor" class="form-control" placeholder="Ingresa el nuevo valor" required maxlength="200">
            <small class="text-muted">Escribe el nuevo valor que deseas para el campo seleccionado</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><i class="fas fa-comment"></i> Motivo del Cambio (Opcional)</label>
            <textarea name="motivo" class="form-control" rows="3" placeholder="Explica brevemente por qué necesitas este cambio" maxlength="500"></textarea>
            <small class="text-muted">Este campo puede ayudar a acelerar la aprobación de tu solicitud</small>
          </div>
          
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning text-dark">
              <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
            <a href="<?= RUTA ?>public/panel" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left"></i> Cancelar
            </a>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Historial de Solicitudes -->
    <?php if(!empty($historial)): ?>
    <div class="card mt-4 shadow-sm">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-history"></i> Historial de Mis Solicitudes</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th width="100">Fecha</th>
                <th>Campo</th>
                <th>Valor Solicitado</th>
                <th width="100" class="text-center">Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($historial as $h): ?>
                <tr>
                  <td class="small text-muted"><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                  <td>
                    <strong>
                      <?php 
                      echo match($h['campo']) {
                        'nombre' => 'Nombre',
                        'email' => 'Email',
                        'telefono' => 'Teléfono',
                        'direccion' => 'Dirección',
                        'fecha_nacimiento' => 'Fecha Nacimiento',
                        'genero' => 'Género',
                        default => htmlspecialchars($h['campo'])
                      };
                      ?>
                    </strong>
                  </td>
                  <td class="small"><?= htmlspecialchars($h['valor_nuevo']) ?></td>
                  <td class="text-center">
                    <?php if($h['estado'] === 'pendiente'): ?>
                      <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendiente</span>
                    <?php elseif($h['estado'] === 'aprobado'): ?>
                      <span class="badge bg-success"><i class="fas fa-check"></i> Aprobado</span>
                    <?php else: ?>
                      <span class="badge bg-danger"><i class="fas fa-times"></i> Rechazado</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="card mt-4 shadow-sm border-secondary">
      <div class="card-header bg-light">
        <h6 class="mb-0 text-muted"><i class="fas fa-shield-alt"></i> Seguridad y Privacidad</h6>
      </div>
      <div class="card-body">
        <p class="small mb-2"><strong>¿Por qué requiere aprobación?</strong></p>
        <p class="small text-muted mb-0">
          Para proteger tu información personal y mantener la integridad de tu historial clínico, 
          todos los cambios en datos personales deben ser verificados y aprobados por el personal administrativo. 
          Esto previene accesos no autorizados y modificaciones fraudulentas.
        </p>
      </div>
    </div>
  </div>
</div>

<style>
.card {
  border-radius: 10px;
}
.card-header {
  border-radius: 10px 10px 0 0 !important;
}
.form-select:focus,
.form-control:focus {
  border-color: #ffc107;
  box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}
</style>
