
<h1 class="h4 mb-3">Dashboard Administrador</h1>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card card-body">
      <h6 class="text-muted mb-1">Usuarios</h6>
      <p class="mb-0">Activos: <strong><?= $usuariosCounts['activo'] ?? 0 ?></strong></p>
      <p class="mb-0">Inactivos: <strong><?= $usuariosCounts['inactivo'] ?? 0 ?></strong></p>
      <canvas id="chartUsuarios" height="130" class="mt-2"></canvas>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-body">
      <h6 class="text-muted mb-1">Citas por Estado</h6>
      <canvas id="chartCitas" height="160"></canvas>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-body">
      <h6 class="text-muted mb-1">Ingresos (Mes Actual Año)</h6>
      <canvas id="chartIngresosMes" height="160"></canvas>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(async function(){
  const r1 = await fetch('<?= url('Admin','jsonUsuariosActivos') ?>').then(r=>r.json());
  const r2 = await fetch('<?= url('Admin','jsonCitasEstados') ?>').then(r=>r.json());
  const r3 = await fetch('<?= url('Admin','jsonIngresosMes') ?>').then(r=>r.json());

  new Chart(document.getElementById('chartUsuarios'),{type:'doughnut', data:{labels:Object.keys(r1), datasets:[{data:Object.values(r1), backgroundColor:['#198754','#dc3545']}]} });
  new Chart(document.getElementById('chartCitas'),{type:'bar', data:{labels:r2.map(o=>o.estado), datasets:[{label:'Citas', data:r2.map(o=>o.total), backgroundColor:'#0d6efd'}]} });
  new Chart(document.getElementById('chartIngresosMes'),{type:'line', data:{labels:r3.map(o=>o.mes), datasets:[{label:'Ingresos', data:r3.map(o=>o.total), borderColor:'#6610f2', fill:false}]} });
})();
</script>
<?php require __DIR__.'/../layout/footer.php'; ?>