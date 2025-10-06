<?php
/* Ajusta mapeo si tus columnas difieren */
$map = [
  'id'         => 'id',
  'fecha'      => 'fecha',          // o fecha_pago
  'monto'      => 'monto',          // o total
  'estado'     => 'estado',         // o estado_pago
  'referencia' => 'referencia',     // o ref_pago
  'metodo'     => 'metodo',         // o metodo_pago
  'descripcion'=> 'descripcion',    // o concepto
  'ticket'     => 'ticket'          // o archivo_ticket
];

$norm = function(array $r) use($map){
  $o=[];
  foreach($map as $k=>$col){ $o[$k] = $r[$col] ?? null; }
  return $o;
};

$pagos = array_map($norm, $pagos ?? []);
$total = 0;
$estadosCount=[];
foreach($pagos as $p){
  $total += (float)$p['monto'];
  $e = $p['estado'] ?: 'desconocido';
  $estadosCount[$e] = ($estadosCount[$e]??0)+1;
}
ksort($estadosCount);
?>
<h2 class="h6 mb-3">Historial de Pagos</h2>

<div class="row g-2 mb-3">
  <div class="col-auto">
    <strong>Total:</strong> $<?= number_format($total,2) ?>
  </div>
  <?php foreach($estadosCount as $est=>$cnt): ?>
    <div class="col-auto">
      <span class="badge text-bg-<?=
        $est==='pagado'?'success':($est==='pendiente'?'warning':($est==='anulado'?'secondary':'light'))
      ?>"><?= htmlspecialchars(ucfirst($est)) ?>: <?= $cnt ?></span>
    </div>
  <?php endforeach; ?>
</div>

<form class="row g-2 align-items-end mb-3" id="filtroForm" onsubmit="return false;">
  <div class="col-sm-3">
    <label class="form-label mb-1 small">Desde</label>
    <input type="date" class="form-control form-control-sm" id="fDesde">
  </div>
  <div class="col-sm-3">
    <label class="form-label mb-1 small">Hasta</label>
    <input type="date" class="form-control form-control-sm" id="fHasta">
  </div>
  <div class="col-sm-3">
    <label class="form-label mb-1 small">Estado</label>
    <select id="fEstado" class="form-select form-select-sm">
      <option value="">(Todos)</option>
      <?php foreach(array_keys($estadosCount) as $e): ?>
        <option value="<?= htmlspecialchars($e) ?>"><?= ucfirst($e) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-3">
    <label class="form-label mb-1 small">Buscar ref/desc</label>
    <input type="text" class="form-control form-control-sm" id="fTexto" placeholder="Texto...">
  </div>
</form>

<table class="table table-sm table-striped" id="tablaPagos">
  <thead class="table-light">
    <tr>
      <th>ID</th><th>Fecha</th><th>Monto</th><th>Estado</th>
      <th>Referencia</th><th>Método</th><th>Descripción</th><th>Ticket</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($pagos as $p):
    $estado = $p['estado'] ?? '';
    $badgeClass = $estado==='pagado'?'success':($estado==='pendiente'?'warning':($estado==='anulado'?'secondary':'light'));
  ?>
    <tr data-fecha="<?= substr($p['fecha']??'',0,10) ?>"
        data-estado="<?= htmlspecialchars($estado) ?>"
        data-texto="<?= strtolower(($p['referencia']??'').' '.($p['descripcion']??'').' '.($p['metodo']??'')) ?>">
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['fecha']??'') ?></td>
      <td>$<?= number_format((float)($p['monto']??0),2) ?></td>
      <td><span class="badge text-bg-<?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($estado)) ?></span></td>
      <td><?= htmlspecialchars($p['referencia']??'') ?></td>
      <td><?= htmlspecialchars($p['metodo']??'') ?></td>
      <td><?= htmlspecialchars($p['descripcion']??'') ?></td>
      <td>
        <?php if(!empty($p['ticket'])): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= RUTA ?>public/ticket?id=<?= $p['id'] ?>" target="_blank">Ver</a>
        <?php else: ?><span class="text-muted small">N/A</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if(empty($pagos)): ?>
    <tr><td colspan="8" class="text-center text-muted">Sin pagos registrados.</td></tr>
  <?php endif; ?>
  </tbody>
  <tfoot>
    <tr>
      <th colspan="2" class="text-end">Total visible:</th>
      <th id="totalVisible">$0.00</th>
      <th colspan="5"></th>
    </tr>
  </tfoot>
</table>

<div class="d-flex gap-2">
  <button class="btn btn-sm btn-outline-secondary" onclick="exportCSV()">Exportar CSV</button>
  <a href="<?= RUTA ?>public/panel" class="btn btn-secondary btn-sm">Volver</a>
</div>

<script>
(function(){
  const fDesde = document.getElementById('fDesde');
  const fHasta = document.getElementById('fHasta');
  const fEstado= document.getElementById('fEstado');
  const fTexto = document.getElementById('fTexto');
  const rows = Array.from(document.querySelectorAll('#tablaPagos tbody tr'));
  const totalVisibleEl = document.getElementById('totalVisible');

  function filtrar(){
    const d1 = fDesde.value || '0000-00-00';
    const d2 = fHasta.value || '9999-12-31';
    const est = fEstado.value.toLowerCase();
    const txt = fTexto.value.trim().toLowerCase();
    let total = 0;
    rows.forEach(r=>{
      const fecha = r.dataset.fecha || '';
      const estado = (r.dataset.estado||'').toLowerCase();
      const texto = r.dataset.texto || '';
      let ok = true;
      if(fecha < d1 || fecha > d2) ok = false;
      if(est && estado !== est) ok = false;
      if(txt && !texto.includes(txt)) ok = false;
      if(ok){
        r.style.display='';
        const m = parseFloat(r.children[2].textContent.replace(/[^0-9.]/g,''))||0;
        total += m;
      } else r.style.display='none';
    });
    totalVisibleEl.textContent = '$'+total.toFixed(2);
  }
  [fDesde,fHasta,fEstado,fTexto].forEach(el=>el.addEventListener('input', filtrar));
  filtrar();
})();

function exportCSV(){
  const rows=[...document.querySelectorAll('#tablaPagos tbody tr')].filter(r=>r.style.display!=='none');
  if(!rows.length){ alert('Nada que exportar'); return; }
  const header=['ID','Fecha','Monto','Estado','Referencia','Método','Descripción'];
  const data = rows.map(r=>[...r.children].slice(0,7).map(td=>`"${td.textContent.trim().replace(/"/g,'""')}"`).join(','));
  const csv = [header.join(','), ...data].join('\n');
  const blob = new Blob([csv],{type:'text/csv;charset=utf-8;'});
  const a=document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download='pagos.csv';
  document.body.appendChild(a); a.click(); a.remove();
}
</script>
