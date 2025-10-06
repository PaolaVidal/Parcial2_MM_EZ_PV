<h2>Escanear / Confirmar Cita</h2>
<p>Ingresa el código leído del QR. Formato aceptado: <code>CITA:123</code> o solo <code>123</code>.</p>
<div class="row g-3 mb-3">
  <div class="col-md-6">
  <input type="text" id="token" class="form-control" placeholder="CITA:ID o ID">
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary" onclick="procesar()">Confirmar</button>
  </div>
</div>
<pre id="resultado" class="p-3 bg-light border" style="min-height:120px"></pre>
<script>
const BASE = '<?= RUTA ?>';
function procesar(){
  const token = document.getElementById('token').value.trim();
  if(!token){ alert('Ingrese código'); return; }
  fetch(BASE+'index.php?url=psicologo/scanProcesar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'token='+encodeURIComponent(token)})
    .then(r=>r.json())
    .then(j=>{
      document.getElementById('resultado').textContent = JSON.stringify(j,null,2);
    })
    .catch(e=>{document.getElementById('resultado').textContent='Error: '+e;});
}
</script>