<?php 
/** @var array $citasPorMes, $citasPorEstado, $ingresosPorMes, $pacientesFrecuentes, $horariosPopulares */
/** @var float $tasaCancelacion, $promedioIngresoDiario, $ingresoTotal */
/** @var int $totalCitas, $totalPacientes */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas Detalladas</h2>
    <div>
        <a href="<?= RUTA ?>psicologo/dashboard" class="btn btn-outline-secondary btn-sm me-2">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
        <a href="<?= RUTA ?>psicologo/exportarEstadisticasExcel" class="btn btn-success btn-sm me-2" title="Descarga archivo Excel profesional con formato">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
        <a href="<?= RUTA ?>psicologo/exportarEstadisticasPDF" class="btn btn-danger btn-sm" target="_blank" title="Abre PDF en nueva pestaña">
            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
        </a>
    </div>
</div>

<!-- Resumen de Métricas Clave -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x text-primary mb-3"></i>
                <h3 class="mb-1"><?= $totalCitas ?></h3>
                <p class="text-muted small mb-0">Total de Citas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-users fa-3x text-success mb-3"></i>
                <h3 class="mb-1"><?= $totalPacientes ?></h3>
                <p class="text-muted small mb-0">Pacientes Únicos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-dollar-sign fa-3x text-info mb-3"></i>
                <h3 class="mb-1">$<?= number_format($ingresoTotal, 2) ?></h3>
                <p class="text-muted small mb-0">Ingresos Totales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-calendar-day fa-3x text-warning mb-3"></i>
                <h3 class="mb-1">$<?= number_format($promedioIngresoDiario, 2) ?></h3>
                <p class="text-muted small mb-0">Promedio Diario</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Gráfico de Citas por Mes -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Citas por Mes (Últimos 12 meses)</h5>
            </div>
            <div class="card-body">
                <canvas id="chartCitasMes" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de Citas por Estado -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribución por Estado</h5>
            </div>
            <div class="card-body">
                <canvas id="chartEstados" style="max-height: 300px;"></canvas>
                <div class="mt-3 text-center">
                    <p class="mb-0 text-muted small">Tasa de Cancelación</p>
                    <h4 class="mb-0 <?= $tasaCancelacion > 20 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($tasaCancelacion, 1) ?>%
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Gráfico de Ingresos por Mes -->
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Ingresos por Mes</h5>
            </div>
            <div class="card-body">
                <canvas id="chartIngresos" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Pacientes Más Frecuentes -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Top 10 Pacientes Frecuentes</h5>
            </div>
            <div class="card-body">
                <?php if(empty($pacientesFrecuentes)): ?>
                    <p class="text-muted text-center">No hay datos disponibles</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th class="text-center">Citas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $pos = 1; foreach($pacientesFrecuentes as $pac): ?>
                                <tr>
                                    <td><?= $pos++ ?></td>
                                    <td><?= htmlspecialchars($pac['nombre'] ?: 'Paciente #'.$pac['id_paciente']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $pac['total_citas'] ?></span>
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

    <!-- Horarios Más Populares -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Horarios Más Solicitados</h5>
            </div>
            <div class="card-body">
                <?php if(empty($horariosPopulares)): ?>
                    <p class="text-muted text-center">No hay datos disponibles</p>
                <?php else: ?>
                    <canvas id="chartHorarios" style="max-height: 300px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Preparar datos PHP para JavaScript
const citasPorMes = <?= json_encode($citasPorMes) ?>;
const citasPorEstado = <?= json_encode($citasPorEstado) ?>;
const ingresosPorMes = <?= json_encode($ingresosPorMes) ?>;
const horariosPopulares = <?= json_encode($horariosPopulares) ?>;

// Gráfico de Citas por Mes (Línea)
const ctxCitasMes = document.getElementById('chartCitasMes').getContext('2d');
new Chart(ctxCitasMes, {
    type: 'line',
    data: {
        labels: citasPorMes.map(d => d.mes),
        datasets: [{
            label: 'Citas Realizadas',
            data: citasPorMes.map(d => d.total),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Gráfico de Estados (Pastel)
const ctxEstados = document.getElementById('chartEstados').getContext('2d');
new Chart(ctxEstados, {
    type: 'pie',
    data: {
        labels: citasPorEstado.map(d => d.estado_cita.charAt(0).toUpperCase() + d.estado_cita.slice(1)),
        datasets: [{
            data: citasPorEstado.map(d => d.total),
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',   // Realizada - verde
                'rgba(255, 193, 7, 0.8)',   // Pendiente - amarillo
                'rgba(220, 53, 69, 0.8)',   // Cancelada - rojo
                'rgba(108, 117, 125, 0.8)'  // Otro - gris
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const value = context.parsed;
                        const percentage = ((value / total) * 100).toFixed(1);
                        return context.label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Gráfico de Ingresos (Barras)
const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
new Chart(ctxIngresos, {
    type: 'bar',
    data: {
        labels: ingresosPorMes.map(d => d.mes),
        datasets: [{
            label: 'Ingresos ($)',
            data: ingresosPorMes.map(d => parseFloat(d.total)),
            backgroundColor: 'rgba(23, 162, 184, 0.6)',
            borderColor: 'rgb(23, 162, 184)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Ingresos: $' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, ticks: { callback: function(value) { return '$' + value; } } }
        }
    }
});

// Gráfico de Horarios (Barras horizontales)
<?php if(!empty($horariosPopulares)): ?>
const ctxHorarios = document.getElementById('chartHorarios').getContext('2d');
new Chart(ctxHorarios, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(function($h){ return $h['hora'].':00'; }, $horariosPopulares)) ?>,
        datasets: [{
            label: 'Citas Agendadas',
            data: <?= json_encode(array_map(function($h){ return $h['total']; }, $horariosPopulares)) ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.6)',
            borderColor: 'rgb(255, 159, 64)',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
<?php endif; ?>
</script>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15)!important;
}
canvas {
    width: 100% !important;
    height: auto !important;
}
</style>
