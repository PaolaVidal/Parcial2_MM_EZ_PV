<?php /** @var array $proximasCitas, $estadisticas */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard</h2>
</div>

<!-- Tarjetas de estadísticas principales -->
<div class="row g-3 mb-4">
    <!-- Pendientes Hoy -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Pendientes Hoy</p>
                        <h3 class="mb-0 fw-bold"><?= (int)$pendHoy ?></h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Realizadas Hoy -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Realizadas Hoy</p>
                        <h3 class="mb-0 fw-bold text-success"><?= (int)$realHoy ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Citas del Mes -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Citas Este Mes</p>
                        <h3 class="mb-0 fw-bold text-primary"><?= (int)$citasMes ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ingresos -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Ingresos Totales</p>
                        <h3 class="mb-0 fw-bold text-info">$<?= number_format($ingresos,2) ?></h3>
                        <small class="text-muted">Este mes: $<?= number_format($ingresosMes,2) ?></small>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="fas fa-dollar-sign fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda fila de métricas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-ban fa-2x text-danger mb-2"></i>
                <h4 class="mb-0"><?= (int)$cancelHoy ?></h4>
                <p class="text-muted small mb-0">Canceladas Hoy</p>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="fas fa-chart-pie me-2"></i>Últimos 30 días</h6>
                <div class="row text-center">
                    <?php foreach($estadisticas as $est): ?>
                    <div class="col">
                        <div class="mb-1">
                            <?php 
                            $badgeClass = 'secondary';
                            if($est['estado_cita']==='realizada') $badgeClass='success';
                            elseif($est['estado_cita']==='pendiente') $badgeClass='warning';
                            elseif($est['estado_cita']==='cancelada') $badgeClass='danger';
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($est['estado_cita']) ?></span>
                        </div>
                        <h5 class="mb-0"><?= (int)$est['total'] ?></h5>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Próximas Citas -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Próximas Citas</h5>
            </div>
            <div class="card-body">
                <?php if(empty($proximasCitas)): ?>
                    <p class="text-muted text-center py-4">No hay citas pendientes próximas</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($proximasCitas as $cita): ?>
                        <div class="list-group-item px-0 border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($cita['paciente_nombre'] ?: 'Paciente #'.$cita['id_paciente']) ?></h6>
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($cita['fecha_hora'])) ?>
                                    </p>
                                    <?php if(!empty($cita['motivo_consulta'])): ?>
                                    <p class="mb-0 small text-muted mt-1">
                                        <i class="fas fa-comment me-1"></i>
                                        <?= htmlspecialchars(substr($cita['motivo_consulta'],0,50)) ?><?= strlen($cita['motivo_consulta'])>50?'...':'' ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= RUTA ?>psicologo/citas" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="<?= RUTA ?>psicologo/citas" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-1"></i> Ver Todas las Citas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slots Disponibles y Accesos Rápidos -->
    <div class="col-md-6">
        <!-- Slots Disponibles -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-hourglass-half me-2"></i>Próximos Slots Libres</h5>
            </div>
            <div class="card-body">
                <?php if(empty($slots)): ?>
                    <p class="text-muted text-center">No hay slots disponibles</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($slots as $slot): ?>
                        <span class="badge bg-light text-dark border px-3 py-2">
                            <i class="fas fa-calendar-day me-1"></i>
                            <?= date('d/m H:i', strtotime($slot)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Accesos Rápidos</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= RUTA ?>psicologo/citas" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-check me-2"></i> Gestionar Citas
                    </a>
                    <a href="<?= RUTA ?>ticket" class="btn btn-outline-success">
                        <i class="fas fa-ticket me-2"></i> Ver Tickets
                    </a>
                    <a href="<?= RUTA ?>psicologo/estadisticas" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar me-2"></i> Estadísticas Completas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15)!important;
}
</style>
