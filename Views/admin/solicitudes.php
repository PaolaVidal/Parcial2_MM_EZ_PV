<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0"><i class="fas fa-inbox"></i> Gestión de Solicitudes de Cambio</h2>
</div>

<?php if(isset($_SESSION['msg_solicitud'])): ?>
  <div class="alert alert-<?= $_SESSION['msg_tipo'] ?? 'success' ?> alert-dismissible fade show">
    <i class="fas fa-<?= ($_SESSION['msg_tipo'] ?? 'success') === 'success' ? 'check-circle' : 'info-circle' ?>"></i> 
    <?= htmlspecialchars($_SESSION['msg_solicitud']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['msg_solicitud'], $_SESSION['msg_tipo']); ?>
<?php endif; ?>

<!-- Filtros y Búsqueda -->
<div class="card mb-3 shadow-sm">
  <div class="card-body">
    <form method="get" action="<?= RUTA ?>admin/solicitudes" class="row g-3">
      
      <div class="col-md-3">
        <label class="form-label small fw-bold"><i class="fas fa-filter"></i> Estado</label>
        <select name="estado" class="form-select form-select-sm">
          <option value="todos" <?= ($estadoFiltro === 'todos') ? 'selected' : '' ?>>Todos los estados</option>
          <option value="pendiente" <?= ($estadoFiltro === 'pendiente') ? 'selected' : '' ?>>Pendientes</option>
          <option value="aprobado" <?= ($estadoFiltro === 'aprobado') ? 'selected' : '' ?>>Aprobados</option>
          <option value="rechazado" <?= ($estadoFiltro === 'rechazado') ? 'selected' : '' ?>>Rechazados</option>
        </select>
      </div>
      
      <div class="col-md-3">
        <label class="form-label small fw-bold"><i class="fas fa-list"></i> Campo</label>
        <select name="campo" class="form-select form-select-sm">
          <option value="">Todos los campos</option>
          <option value="nombre" <?= ($campoFiltro === 'nombre') ? 'selected' : '' ?>>Nombre</option>
          <option value="email" <?= ($campoFiltro === 'email') ? 'selected' : '' ?>>Email</option>
          <option value="telefono" <?= ($campoFiltro === 'telefono') ? 'selected' : '' ?>>Teléfono</option>
          <option value="direccion" <?= ($campoFiltro === 'direccion') ? 'selected' : '' ?>>Dirección</option>
          <option value="fecha_nacimiento" <?= ($campoFiltro === 'fecha_nacimiento') ? 'selected' : '' ?>>Fecha Nacimiento</option>
          <option value="genero" <?= ($campoFiltro === 'genero') ? 'selected' : '' ?>>Género</option>
        </select>
      </div>
      
      <div class="col-md-3">
        <label class="form-label small fw-bold"><i class="fas fa-search"></i> Buscar DUI</label>
        <input type="text" name="buscar" class="form-control form-control-sm" 
               placeholder="Ej: 12345678-9" value="<?= htmlspecialchars($buscarDui) ?>">
      </div>
      
      <div class="col-md-2">
        <label class="form-label small fw-bold"><i class="fas fa-sort"></i> Ordenar</label>
        <select name="orden" class="form-select form-select-sm">
          <option value="DESC" <?= ($orden === 'DESC') ? 'selected' : '' ?>>Más recientes</option>
          <option value="ASC" <?= ($orden === 'ASC') ? 'selected' : '' ?>>Más antiguas</option>
        </select>
      </div>
      
      <div class="col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-filter"></i> Filtrar
        </button>
      </div>
    </form>
    
    <?php if($estadoFiltro !== 'pendiente' || !empty($campoFiltro) || !empty($buscarDui) || $orden !== 'DESC'): ?>
      <div class="mt-2">
        <a href="<?= RUTA ?>admin/solicitudes" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-times"></i> Limpiar Filtros
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Estadísticas Rápidas -->
<div class="row g-2 mb-3">
  <div class="col-md-4">
    <div class="card border-warning">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Pendientes</small>
        <h4 class="mb-0 text-warning"><?= count(array_filter($solicitudes, fn($s) => $s['estado'] === 'pendiente')) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-success">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Aprobadas</small>
        <h4 class="mb-0 text-success"><?= count(array_filter($solicitudes, fn($s) => $s['estado'] === 'aprobado')) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-danger">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Rechazadas</small>
        <h4 class="mb-0 text-danger"><?= count(array_filter($solicitudes, fn($s) => $s['estado'] === 'rechazado')) ?></h4>
      </div>
    </div>
  </div>
</div>

<?php if(empty($solicitudes)): ?>
  <div class="alert alert-info py-2"><i class="fas fa-info-circle"></i> No se encontraron solicitudes con los filtros aplicados.</div>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-dark">
      <tr>
        <th width="60">ID</th>
        <th width="150">Paciente</th>
        <th width="120">Campo</th>
        <th>Nuevo Valor</th>
        <th width="140">Fecha</th>
        <th width="100" class="text-center">Estado</th>
        <th width="200" class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($solicitudes as $s): ?>
      <tr class="<?= $s['estado'] === 'pendiente' ? 'table-warning' : ($s['estado'] === 'aprobado' ? 'table-success' : 'table-danger') ?> bg-opacity-10">
        <td><span class="badge bg-secondary">#<?= $s['id'] ?></span></td>
        <td>
          <div><strong><?= htmlspecialchars($s['paciente_nombre'] ?? 'N/A') ?></strong></div>
          <small class="text-muted"><i class="fas fa-id-card"></i> <?= htmlspecialchars($s['dui'] ?? 'N/A') ?></small>
        </td>
        <td>
          <span class="badge bg-info">
            <?php 
            $campoDisplay = match($s['campo']) {
              'nombre' => 'Nombre',
              'email' => 'Email',
              'telefono' => 'Teléfono',
              'direccion' => 'Dirección',
              'fecha_nacimiento' => 'Fecha Nac.',
              'genero' => 'Género',
              default => $s['campo']
            };
            echo htmlspecialchars($campoDisplay);
            ?>
          </span>
        </td>
        <td style="max-width:250px" class="text-break"><?= nl2br(htmlspecialchars($s['valor_nuevo'])) ?></td>
        <td><small class="text-muted"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($s['fecha'])) ?></small></td>
        <td class="text-center">
          <?php if($s['estado'] === 'pendiente'): ?>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendiente</span>
          <?php elseif($s['estado'] === 'aprobado'): ?>
            <span class="badge bg-success"><i class="fas fa-check"></i> Aprobado</span>
          <?php else: ?>
            <span class="badge bg-danger"><i class="fas fa-times"></i> Rechazado</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if($s['estado'] === 'pendiente'): ?>
            <form method="post" action="<?= RUTA ?>admin/procesarSolicitud" class="d-inline">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <input type="hidden" name="id_paciente" value="<?= $s['id_paciente'] ?>">
              <input type="hidden" name="campo_original" value="<?= htmlspecialchars($s['campo']) ?>">
              <input type="hidden" name="valor_nuevo" value="<?= htmlspecialchars($s['valor_nuevo']) ?>">
              <button name="accion" value="aprobar" class="btn btn-success btn-sm"
                      onclick="return confirm('¿Aprobar y aplicar el cambio?\n\nPaciente: <?= htmlspecialchars($s['paciente_nombre']) ?>\nCampo: <?= htmlspecialchars($campoDisplay) ?>\nNuevo valor: <?= htmlspecialchars($s['valor_nuevo']) ?>')">
                <i class="fas fa-check"></i> Aprobar
              </button>
              <button name="accion" value="rechazar" class="btn btn-danger btn-sm ms-1"
                      onclick="return confirm('¿Rechazar esta solicitud #<?= $s['id'] ?>?')">
                <i class="fas fa-times"></i> Rechazar
              </button>
            </form>
          <?php else: ?>
            <small class="text-muted">Ya procesada</small>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="text-muted small mt-2">
  <i class="fas fa-info-circle"></i> Mostrando <?= count($solicitudes) ?> solicitud(es)
</div>
<?php endif; ?>
