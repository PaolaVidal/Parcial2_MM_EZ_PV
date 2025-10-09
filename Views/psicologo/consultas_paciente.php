<?php
/** @var string $q
 *  @var array $resultados
 *  @var array|null $paciente
 *  @var string $historialClinico
 *  @var array $citas
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="fas fa-user-md me-2"></i>Consultas de Pacientes</h2>
    <a href="<?= RUTA ?>psicologo/dashboard" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="<?= RUTA ?>index.php" class="row g-2 align-items-end">
            <input type="hidden" name="url" value="psicologo/consultarPaciente">
            <?php if(!empty($paciente)): ?>
                <input type="hidden" name="id" value="<?= (int)$paciente['id'] ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <label class="form-label">Buscar por nombre o DUI</label>
                <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" class="form-control"
                    placeholder="Ej: Juan Perez o 01234567-8">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <?php $estSel = $estadoFiltro ?? ($_GET['estado'] ?? 'all'); ?>
                    <option value="all" <?= ($estSel === 'all') ? 'selected' : '' ?>>Todos</option>
                    <option value="pendiente" <?= ($estSel === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                    <option value="realizada" <?= ($estSel === 'realizada') ? 'selected' : '' ?>>Realizada</option>
                    <option value="cancelada" <?= ($estSel === 'cancelada') ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Inicio</label>
                <input type="date" name="inicio" value="<?= htmlspecialchars($inicioFiltro ?? ($_GET['inicio'] ?? '')) ?>" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fin</label>
                <input type="date" name="fin" value="<?= htmlspecialchars($finFiltro ?? ($_GET['fin'] ?? '')) ?>" class="form-control">
            </div>
            <div class="col-md-2 text-end">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100">Aplicar</button>
            </div>
    </div>
</div>

<?php if (!empty($resultados)): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h6>Resultados</h6>
            <div class="list-group">
                <?php foreach ($resultados as $r): ?>
                    <a href="<?= RUTA ?>index.php?url=psicologo/consultarPaciente&id=<?= (int) $r['id'] ?>"
                        class="list-group-item list-group-item-action">
                        <strong><?= htmlspecialchars($r['nombre']) ?></strong>
                        <div class="small text-muted">DUI: <?= htmlspecialchars($r['dui'] ?? '') ?> - ID: <?= (int) $r['id'] ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($paciente): ?>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-1"><?= htmlspecialchars($paciente['nombre'] ?? 'Paciente') ?></h5>
                    <p class="small mb-1">DUI: <?= htmlspecialchars($paciente['dui'] ?? 'N/D') ?></p>
                    <p class="small mb-1">Email: <?= htmlspecialchars($paciente['email'] ?? 'N/D') ?></p>
                    <p class="small mb-1">Tel: <?= htmlspecialchars($paciente['telefono'] ?? 'N/D') ?></p>
                    <?php
                        // Fecha de nacimiento puede venir en diferentes claves
                        $fnRaw = $paciente['fecha_nacimiento'] ?? $paciente['fechaNacimiento'] ?? $paciente['nacimiento'] ?? '';
                        $fnFmt = 'No registrada';
                        if ($fnRaw && preg_match('/^\d{4}-\d{2}-\d{2}/', $fnRaw)) {
                            try { $fnFmt = (new DateTime(substr($fnRaw,0,10)))->format('d/m/Y'); } catch (Throwable $e) { $fnFmt = htmlspecialchars($fnRaw); }
                        } elseif ($fnRaw) {
                            $fnFmt = htmlspecialchars($fnRaw);
                        }
                        $sexo = $paciente['genero'] ?? $paciente['sexo'] ?? '';
                        $direccion = $paciente['direccion'] ?? $paciente['dir'] ?? '';
                    ?>
                    <p class="small mb-1">Fecha de Nacimiento: <?= $fnFmt ?></p>
                    <?php if(!empty($sexo)): ?><p class="small mb-1">Sexo: <?= htmlspecialchars($sexo) ?></p><?php endif; ?>
                    <?php if(!empty($direccion)): ?><p class="small mb-1">Dirección: <?= htmlspecialchars($direccion) ?></p><?php endif; ?>
                    <hr>
                    <h6 class="mb-1">Historial Clínico</h6>
                    <div class="small text-muted" style="white-space:pre-wrap;">
                        <?= nl2br(htmlspecialchars($historialClinico ?? 'No registrado')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Citas (todas)</h6>
                    <?php if (empty($citas)): ?>
                        <p class="text-muted">No hay citas registradas para este paciente.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Psicólogo</th>
                                        <th>Especialidad</th>
                                        <th>Estado</th>
                                        <th>Evaluaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($citas as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($c['fecha_hora'], 0, 16)) ?></td>
                                            <td><?= htmlspecialchars($c['psicologo_nombre'] ?? $c['psicologo'] ?? 'N/D') ?></td>
                                            <td><?= htmlspecialchars($c['psicologo_especialidad'] ?? $c['especialidad'] ?? 'N/D') ?>
                                            </td>
                                            <td><?= htmlspecialchars($c['estado_cita'] ?? $c['estado']) ?></td>
                                            <td>
                                                <?php if (!empty($c['evaluaciones'])): ?>
                                                    <ul class="mb-0 small">
                                                        <?php foreach ($c['evaluaciones'] as $ev): ?>
                                                            <li class="mb-1">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-primary me-1 view-eval-btn"
                                                                    data-ev-id="<?= (int) $ev['id'] ?>">Ver #<?= (int) $ev['id'] ?></button>
                                                                <span class="small text-muted">Estado:
                                                                    <?= htmlspecialchars($ev['estado_emocional']) ?></span>
                                                                <div id="ev-detail-<?= (int) $ev['id'] ?>" style="display:none">
                                                                    <h6>Evaluación #<?= (int) $ev['id'] ?></h6>
                                                                    <p><strong>Estado emocional:</strong>
                                                                        <?= htmlspecialchars($ev['estado_emocional']) ?></p>
                                                                    <p><strong>Comentarios:</strong></p>
                                                                    <div style="white-space:pre-wrap;">
                                                                        <?= nl2br(htmlspecialchars($ev['comentarios'] ?? '')) ?></div>
                                                                    <?php if (!empty($ev['created_at'])): ?>
                                                                        <p class="small text-muted mt-2">Registrada:
                                                                            <?= htmlspecialchars($ev['created_at']) ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="small text-muted">-</span>
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
        </div>
    </div>
<?php endif; ?>

<!-- Modal para mostrar detalle de evaluación -->
<div class="modal fade" id="evalDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Evaluación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="evalDetailBody">
                <!-- contenido inyectado -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('evalDetailModal');
        const modalBody = document.getElementById('evalDetailBody');
        const bootstrapModal = modalEl ? new bootstrap.Modal(modalEl) : null;
        document.querySelectorAll('.view-eval-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                const id = this.dataset.evId;
                const container = document.getElementById('ev-detail-' + id);
                if (!container) return;
                modalBody.innerHTML = container.innerHTML;
                if (bootstrapModal) bootstrapModal.show();
            });
        });
    });
</script>