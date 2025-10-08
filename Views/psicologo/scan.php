<h2 class="mb-3">Escanear Cita</h2>
<p class="text-muted mb-4">Apunta la cámara al código QR (formato <code>CITA:&lt;ID&gt;</code>). Primero verás los datos; luego podrás confirmar la asistencia. También puedes ingresar manualmente.</p>

<div class="row g-3 align-items-end mb-4">
  <div class="col-md-5">
    <label class="form-label small mb-1">Entrada manual</label>
    <div class="input-group">
      <input type="text" id="token" class="form-control" placeholder="CITA:123" autocomplete="off">
      <button class="btn btn-outline-primary" onclick="consultarManual()"><i class="fas fa-search"></i></button>
    </div>
  </div>
  <div class="col-md-7 text-md-end">
    <div class="btn-group mb-2" role="group">
      <button id="btnIniciar" class="btn btn-primary" onclick="iniciarScanner()"><i class="fas fa-camera"></i> Iniciar</button>
      <button id="btnDetener" class="btn btn-outline-secondary" onclick="detenerScanner()" disabled><i class="fas fa-stop"></i> Detener</button>
      <button id="btnReiniciar" class="btn btn-outline-secondary" onclick="reiniciarScanner()" disabled><i class="fas fa-sync"></i></button>
    </div>
    <button id="btnConfirmar" class="btn btn-success ms-md-2 d-none" onclick="confirmar()"><i class="fas fa-check"></i> Confirmar asistencia</button>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div id="scannerContainer" class="border rounded p-2" style="background:#111;min-height:320px; position:relative;">
      <div id="qrReader" style="width:100%;height:100%;"></div>
      <div id="overlayEstado" class="text-white small position-absolute top-0 start-0 p-2" style="background:rgba(0,0,0,.4);border-bottom-right-radius:6px;">Inactivo</div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm mb-3">
      <div class="card-header py-2"><strong>Resultado</strong></div>
      <div id="datos" class="card-body" style="min-height:140px">
        <em class="text-muted">Esperando escaneo...</em>
      </div>
    </div>
    <div id="mensaje" class="alert d-none"></div>
  </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+7FVKyYVJqZ3+6M7BXeYqKq0Mt2rt7NmBGGJ6xMZ8+7+XkG1VA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script>
// Variables globales
const BASE = '<?= RUTA ?>';
let ultimaCita = null;
let html5Qr;
let escaneando = false;
let ultimoToken = '';
let cooldown = false;
let html5QrLibCargada = false;

// Cargar librería html5-qrcode LOCAL (sin depender de internet)
function cargarLibreriaQR(callback) {
  // Si ya está cargada
  if (window.Html5Qrcode) {
    html5QrLibCargada = true;
    callback();
    return;
  }
  
  // Si ya se está cargando, esperar
  if (html5QrLibCargada === 'loading') {
    setTimeout(() => cargarLibreriaQR(callback), 100);
    return;
  }
  
  html5QrLibCargada = 'loading';
  
  // Cargar desde archivo LOCAL
  const script = document.createElement('script');
  script.src = BASE + 'public/js/html5-qrcode.min.js';
  script.async = true;
  
  script.onload = function() {
    html5QrLibCargada = true;
    callback();
  };
  
  script.onerror = function() {
    html5QrLibCargada = false;
    alerta('danger', '❌ Error cargando librería QR local. Verifica que el archivo exista en public/js/');
    console.error('Error cargando html5-qrcode.min.js desde public/js/');
  };
  
  document.head.appendChild(script);
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  actualizarEstado('Cargando librería...');
  cargarLibreriaQR(function() {
    actualizarEstado('Inactivo - Listo para iniciar');
    alerta('success', '✓ Scanner listo. Presiona "Iniciar" para comenzar.');
  });
});

function consultarManual(){
  const token = document.getElementById('token').value.trim();
  if(token) procesarToken(token,false);
  else alerta('warning','Ingresa un código');
}

function procesarToken(token, desdeCamara){
  limpiarAvisos();
  if(!token){ return alerta('warning','Sin código'); }
  if(!token.startsWith('CITA:')){ return alerta('danger','Formato inválido (se esperaba CITA:ID)'); }
  if(token === ultimoToken && desdeCamara){ return; } // evitar repetir mismo frame
  ultimoToken = token;
  if(cooldown && desdeCamara){ return; }
  if(desdeCamara){ // cooldown para no saturar
    cooldown = true; setTimeout(()=>cooldown=false, 1200);
  }
  
  alerta('info','Consultando...');
  
  fetch(BASE+'index.php?url=psicologo/scanConsultar',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'token='+encodeURIComponent(token)
  })
  .then(r=>r.json())
  .then(j=>{
     if(!j.ok){ 
       alerta('danger',j.msg||'Error'); 
       ultimaCita=null; 
       renderDatos(null); 
       return; 
     }
     ultimaCita = j.cita; 
     renderDatos(j.cita);
     if(j.cita.estado_cita==='pendiente'){
       document.getElementById('btnConfirmar').classList.remove('d-none');
       alerta('success','✓ Cita encontrada. Puedes confirmarla.');
     } else {
       alerta('info','Cita ya está '+j.cita.estado_cita);
     }
     if(desdeCamara){ 
       detenerScanner(); 
     }
  })
  .catch(e=>{
    console.error('Error fetch:', e);
    alerta('danger','Fallo red o servidor');
  });
}

function iniciarScanner(){
  if(escaneando) return;
  
  // Verificar que la librería esté cargada
  if(!window.Html5Qrcode){
    alerta('warning', 'Cargando librería QR...');
    cargarLibreriaQR(iniciarScanner);
    return;
  }
  
  const div = document.getElementById('qrReader');
  div.innerHTML='';
  
  // Limpiar instancia previa
  if(html5Qr){
    try {
      html5Qr.clear().catch(()=>{});
    } catch(e) {}
  }
  
  actualizarEstado('Inicializando...');
  alerta('info', 'Solicitando acceso a la cámara...');
  
  html5Qr = new Html5Qrcode('qrReader');
  
  Html5Qrcode.getCameras().then(cams=>{
    if(!cams.length){ 
      alerta('warning','⚠️ No hay cámaras disponibles en este dispositivo'); 
      actualizarEstado('Sin cámara');
      return; 
    }
    
    // Preferir cámara trasera en móviles
    let camId = cams[0].id;
    const backCam = cams.find(c => c.label && c.label.toLowerCase().includes('back'));
    if(backCam) camId = backCam.id;
    
    html5Qr.start(
      camId, 
      {fps:10, qrbox: {width:230, height:230}, aspectRatio: 1.0}, 
      onScan, 
      onError
    )
    .then(()=>{
      escaneando=true; 
      actualizarEstado('✓ Activo - Escaneando'); 
      toggleBotones();
      alerta('success', '✓ Scanner activo. Apunta al código QR de la cita.');
    })
    .catch(e=>{
      const msg = e.toString();
      if(msg.includes('Permission') || msg.includes('NotAllowed')){
        alerta('danger','⚠️ Permiso denegado. Permite el acceso a la cámara en tu navegador.');
      } else if(msg.includes('NotFound')){
        alerta('danger','⚠️ No se encontró ninguna cámara.');
      } else {
        alerta('danger','Error al iniciar: ' + msg.substring(0,60));
      }
      actualizarEstado('Error');
      console.error('Error al iniciar scanner:', e);
    });
  }).catch(e=>{
    alerta('danger','Error enumerando cámaras: '+e);
    actualizarEstado('Error');
    console.error('Error getCameras:', e);
  });
}

function onScan(decoded){ procesarToken(decoded.trim(), true); }
function onError(err){ /* ignorar ruido */ }

function detenerScanner(){
  if(!escaneando || !html5Qr) return;
  html5Qr.stop()
    .then(()=>{ 
      escaneando=false; 
      actualizarEstado('Detenido'); 
      toggleBotones();
      alerta('info','Scanner detenido');
    })
    .catch(e=>{
      console.error('Error deteniendo:', e);
      escaneando=false;
      toggleBotones();
    });
}
function reiniciarScanner(){ 
  detenerScanner(); 
  setTimeout(()=>{
    if(html5Qr){
      html5Qr.clear().catch(()=>{});
    }
    iniciarScanner();
  },500); 
}

function confirmar(){
  if(!ultimaCita){ return alerta('warning','No hay cita cargada'); }
  fetch(BASE+'index.php?url=psicologo/scanConfirmar',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(ultimaCita.id)
  }).then(r=>r.json()).then(j=>{
     if(!j.ok){ alerta('danger',j.msg||'No se pudo confirmar'); return; }
     alerta('success','Cita confirmada');
     ultimaCita.estado_cita='realizada'; renderDatos(ultimaCita); document.getElementById('btnConfirmar').classList.add('d-none');
  }).catch(()=>alerta('danger','Error red confirmación'));
}

function renderDatos(c){
  const div=document.getElementById('datos');
  if(!c){ div.innerHTML='<em class="text-muted">Sin datos</em>'; return; }
  div.innerHTML=`
    <div class="mb-1"><strong>ID:</strong> ${c.id}</div>
    <div class="mb-1"><strong>Paciente:</strong> ${c.id_paciente}</div>
    <div class="mb-1"><strong>Fecha/Hora:</strong> ${c.fecha_hora}</div>
    <div class="mb-1"><strong>Estado:</strong> <span class="badge bg-${c.estado_cita==='pendiente'?'warning text-dark':'success'}">${c.estado_cita}</span></div>
  `;
}
function alerta(tipo,msg){
  const m=document.getElementById('mensaje');
  m.className='alert alert-'+tipo; m.textContent=msg; m.classList.remove('d-none');
}
function limpiarAvisos(){ const m=document.getElementById('mensaje'); m.className='alert d-none'; m.textContent=''; document.getElementById('btnConfirmar').classList.add('d-none'); }
function actualizarEstado(txt){ document.getElementById('overlayEstado').textContent=txt; }
function toggleBotones(){
  document.getElementById('btnIniciar').disabled=escaneando;
  document.getElementById('btnDetener').disabled=!escaneando;
  document.getElementById('btnReiniciar').disabled=!escaneando;
}
</script>