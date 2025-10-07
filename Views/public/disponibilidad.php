
<?php /** @var array $psicologos */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4 mb-0"><i class="fas fa-user-md text-info"></i> Psicólogos Disponibles</h2>
  <a href="<?= RUTA ?>public/portal" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Volver al Inicio
  </a>
</div>

<?php if(empty($psicologos)): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle"></i> No hay psicólogos activos en este momento.
  </div>
<?php else: ?>
  <?php foreach($psicologos as $p): 
    $nombre = htmlspecialchars($p['nombre']);
    $especialidad = htmlspecialchars($p['especialidad'] ?? 'No especificada');
    $experiencia = htmlspecialchars($p['experiencia'] ?? '');
    $horarios = $p['horarios'] ?? [];
    
    // Agrupar horarios por día
    $horariosPorDia = [];
    foreach($horarios as $h) {
      $dia = $h['dia_semana'];
      $inicio = date('h:i A', strtotime($h['hora_inicio']));
      $fin = date('h:i A', strtotime($h['hora_fin']));
      $horariosPorDia[$dia][] = "$inicio - $fin";
    }
  ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-info text-white">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">
              <i class="fas fa-user-circle"></i> <?= $nombre ?>
            </h5>
          </div>
          <div class="col-auto">
            <span class="badge bg-light text-dark">
              <i class="fas fa-brain"></i> <?= $especialidad ?>
            </span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php if(!empty($experiencia)): ?>
          <div class="alert alert-light mb-3">
            <strong><i class="fas fa-award text-warning"></i> Experiencia:</strong>
            <p class="mb-0 small"><?= nl2br($experiencia) ?></p>
          </div>
        <?php endif; ?>
        
        <h6 class="mb-3"><i class="fas fa-clock text-primary"></i> Horarios de Atención</h6>
        
        <?php if(empty($horariosPorDia)): ?>
          <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-triangle"></i> No hay horarios configurados para este psicólogo.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 150px"><i class="fas fa-calendar-day"></i> Día</th>
                  <th><i class="fas fa-business-time"></i> Horarios</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $diasSemana = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
                foreach($diasSemana as $dia): 
                  if(isset($horariosPorDia[$dia])): 
                ?>
                  <tr>
                    <td class="fw-bold"><?= ucfirst($dia) ?></td>
                    <td>
                      <?php foreach($horariosPorDia[$dia] as $rango): ?>
                        <span class="badge bg-success me-1 mb-1">
                          <i class="fas fa-clock"></i> <?= $rango ?>
                        </span>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                <?php 
                  endif; 
                endforeach; 
                ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="text-center mt-4">
  <a href="<?= RUTA ?>public/portal" class="btn btn-secondary">
    <i class="fas fa-home"></i> Volver al Portal
  </a>
</div>

