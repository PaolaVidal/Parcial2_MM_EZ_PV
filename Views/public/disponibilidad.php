<?php /** @var array $psicologos */ ?>
<?php /** @var array $especialidades */ ?>
<?php
$q = htmlspecialchars($q ?? '');
$espSel = $esp ?? null;
$isPaciente = isset($_SESSION['usuario']) && (($_SESSION['usuario']['rol'] ?? '') === 'paciente');
$homeHref = $isPaciente ? url('public', 'panel') : RUTA; // destino para botón Home
$backHref = $isPaciente ? url('public', 'panel') : RUTA . 'public/portal'; // botón Volver
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
  <h2 class="h4 mb-0"><i class="fas fa-user-md text-info"></i> Psicólogos Disponibles</h2>
  <div class="d-flex gap-2">
    <a href="<?= $homeHref ?>" class="btn btn-outline-primary btn-sm" title="Inicio">
      <i class="fas fa-home"></i>
    </a>
    <a href="<?= $backHref ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Volver <?= $isPaciente ? 'al Panel' : 'al Inicio' ?>
    </a>
  </div>
</div>

<form class="card card-body shadow-sm mb-4" method="get" action="">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label mb-1"><i class="fas fa-search"></i> Buscar por nombre</label>
      <input type="text" name="q" value="<?= $q ?>" class="form-control" placeholder="Nombre del psicólogo...">
    </div>
    <div class="col-md-4">
      <label class="form-label mb-1"><i class="fas fa-brain"></i> Especialidad</label>
      <select name="esp" class="form-select">
        <option value="">-- Todas --</option>
        <?php foreach ($especialidades as $e): ?>
          <option value="<?= $e['id'] ?>" <?= ($espSel == $e['id'] ? 'selected' : '') ?>>
            <?= htmlspecialchars($e['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
      <a href="<?= RUTA ?>public/disponibilidad" class="btn btn-outline-secondary" title="Limpiar filtros"><i
          class="fas fa-undo"></i></a>
    </div>
  </div>
  <?php if ($q !== '' || $espSel): ?>
    <div class="mt-2 small text-muted">
      <i class="fas fa-info-circle"></i> Mostrando resultados filtrados. Usa el icono de reinicio para limpiar.
    </div>
  <?php endif; ?>
</form>

<?php if (empty($psicologos)): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle"></i> No se encontraron psicólogos con los criterios dados.
  </div>
<?php else: ?>
  <?php foreach ($psicologos as $p):
    $nombre = htmlspecialchars($p['nombre']);
    $especialidad = htmlspecialchars($p['nombre_especialidad'] ?? 'No especificada');
    $experiencia = htmlspecialchars($p['experiencia'] ?? '');
    $horarios = $p['horarios'] ?? [];
    $horariosPorDia = [];
    foreach ($horarios as $h) {
      $dia = $h['dia_semana'];
      $inicio = date('h:i A', strtotime($h['hora_inicio']));
      $fin = date('h:i A', strtotime($h['hora_fin']));
      $horariosPorDia[$dia][] = "$inicio - $fin";
    }
    ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-info text-white">
        <div class="row align-items-center g-2">
          <div class="col-md">
            <h5 class="mb-0"><i class="fas fa-user-circle"></i> <?= $nombre ?></h5>
          </div>
          <div class="col-auto">
            <span class="badge bg-light text-dark"><i class="fas fa-brain"></i> <?= $especialidad ?></span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php if (!empty($experiencia)): ?>
          <div class="alert alert-light mb-3">
            <strong><i class="fas fa-award text-warning"></i> Experiencia:</strong>
            <p class="mb-0 small"><?= nl2br($experiencia) ?></p>
          </div>
        <?php endif; ?>
        <h6 class="mb-3"><i class="fas fa-clock text-primary"></i> Horarios de Atención</h6>
        <?php if (empty($horariosPorDia)): ?>
          <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> No hay horarios configurados para
            este psicólogo.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:150px"><i class="fas fa-calendar-day"></i> Día</th>
                  <th><i class="fas fa-business-time"></i> Horarios</th>
                </tr>
              </thead>
              <tbody>
                <?php $diasSemana = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
                foreach ($diasSemana as $dia):
                  if (isset($horariosPorDia[$dia])): ?>
                    <tr>
                      <td class="fw-bold"><?= ucfirst($dia) ?></td>
                      <td>
                        <?php foreach ($horariosPorDia[$dia] as $rango): ?>
                          <span class="badge bg-success me-1 mb-1"><i class="fas fa-clock"></i> <?= $rango ?></span>
                        <?php endforeach; ?>
                      </td>
                    </tr>
                  <?php endif; endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="text-center mt-4">
  <a href="<?= $homeHref ?>" class="btn btn-secondary"><i class="fas fa-home"></i>
    <?= $isPaciente ? 'Volver al Panel' : 'Inicio' ?></a>
</div>