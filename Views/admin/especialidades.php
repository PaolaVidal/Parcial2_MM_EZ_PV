<h1 class="h4 mb-3">Gestión de Especialidades</h1>

<?php if(!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2">
    <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if(!empty($success)): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Formulario para crear nueva especialidad -->
<div class="card mb-4">
  <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Crear Nueva Especialidad</div>
  <div class="card-body">
    <form method="post" action="<?= RUTA ?>index.php?url=especialidad/crear" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="nombre" class="form-control" placeholder="Ej: Psicología Clínica" required maxlength="100">
      </div>
      <div class="col-md-6">
        <label class="form-label">Descripción</label>
        <input type="text" name="descripcion" class="form-control" placeholder="Breve descripción de la especialidad" maxlength="255">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-save me-1"></i>Crear
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Tabla de especialidades -->
<div class="card">
  <div class="card-header"><i class="fas fa-list me-1"></i>Lista de Especialidades</div>
  <div class="card-body p-0">
    <?php if(empty($especialidades)): ?>
      <div class="alert alert-info m-3">No hay especialidades registradas.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Psicólogos</th>
              <th>Estado</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($especialidades as $esp): ?>
              <tr>
                <form method="post" action="<?= RUTA ?>index.php?url=especialidad/actualizar" class="d-contents">
                  <input type="hidden" name="id" value="<?= (int)$esp['id'] ?>">
                  
                  <td><?= (int)$esp['id'] ?></td>
                  
                  <td>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($esp['nombre']) ?>" 
                           class="form-control form-control-sm" required maxlength="100">
                  </td>
                  
                  <td>
                    <input type="text" name="descripcion" value="<?= htmlspecialchars($esp['descripcion'] ?? '') ?>" 
                           class="form-control form-control-sm" maxlength="255">
                  </td>
                  
                  <td class="text-center">
                    <span class="badge bg-info">
                      <i class="fas fa-user-md me-1"></i><?= (int)$esp['count_psicologos'] ?>
                    </span>
                  </td>
                  
                  <td>
                    <select name="estado" class="form-select form-select-sm" style="width: auto;">
                      <option value="activo" <?= $esp['estado']==='activo'?'selected':'' ?>>Activo</option>
                      <option value="inactivo" <?= $esp['estado']==='inactivo'?'selected':'' ?>>Inactivo</option>
                    </select>
                  </td>
                  
                  <td class="text-center text-nowrap">
                    <!-- Botón Guardar -->
                    <button type="submit" class="btn btn-sm btn-success" title="Guardar cambios">
                      <i class="fas fa-save"></i>
                    </button>
                </form>
                
                <!-- Botón Eliminar (solo si no tiene psicólogos) -->
                <?php if((int)$esp['count_psicologos'] === 0): ?>
                  <form method="post" action="<?= RUTA ?>index.php?url=especialidad/eliminar" 
                        style="display: inline;" 
                        onsubmit="return confirm('¿Está seguro de eliminar esta especialidad?');">
                    <input type="hidden" name="id" value="<?= (int)$esp['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-sm btn-secondary" disabled title="No se puede eliminar (tiene psicólogos asignados)">
                    <i class="fas fa-trash"></i>
                  </button>
                <?php endif; ?>
                
              </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="alert alert-info mt-3 small">
  <i class="fas fa-info-circle me-1"></i>
  <strong>Nota:</strong> No se pueden eliminar especialidades que tengan psicólogos asignados. 
  Primero desactive la especialidad o reasigne los psicólogos.
</div>
