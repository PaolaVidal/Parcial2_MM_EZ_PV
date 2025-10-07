<?php if(!$ticket){ echo '<div class="alert alert-warning">Ticket no encontrado.</div>'; return; } ?>
<?php
  // Normalizar y validar ruta del QR. Si la BD almacena solo nombre, anteponer qrcodes/
  $qr = trim($ticket['qr_code'] ?? '');
  $qr = ltrim($qr,'/');
  $qr = preg_replace('#^public/#','',$qr);
  if($qr && !str_starts_with($qr,'qrcodes/')){
    // Si solo viene el nombre del archivo
    if(!str_contains($qr,'/')) $qr = 'qrcodes/' . $qr;
  }
  $qrRel = $qr;
  // Comprobar físicamente si existe; si no, intentar reconstruir a partir de id_pago
  $fsBase = __DIR__ . '/../../public/';
  $exists = false;
  if($qrRel){
    $fsCandidate = realpath($fsBase . $qrRel) ?: ($fsBase . $qrRel);
    if(is_file($fsCandidate)) $exists = true;
  }
  if(!$exists && !empty($ticket['id_pago'])){
    $alt = 'qrcodes/ticket_' . (int)$ticket['id_pago'] . '.png';
    $fsCandidateAlt = realpath($fsBase . $alt) ?: ($fsBase . $alt);
    if(is_file($fsCandidateAlt)){
      $qrRel = $alt; $exists = true;
    }
  }
  if(!$exists){
    // Último intento: listar qrcodes y buscar por id_pago en nombre
    $dirQR = $fsBase . 'qrcodes/';
    if(is_dir($dirQR)){
      $idPago = (int)($ticket['id_pago'] ?? 0);
      $cands = glob($dirQR . '*'.$idPago.'*.png');
      if($cands){
        $first = basename($cands[0]);
        $qrRel = 'qrcodes/' . $first; $exists = true;
      }
    }
  }
  $qrFull = $qrRel ? RUTA . $qrRel : '';
?>
<style>
  .ticket-wrapper{max-width:420px;margin:0 auto;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,sans-serif;}
  .ticket{background:#fff;position:relative;padding:1.2rem 1.4rem 1.4rem;border:2px solid #222;border-radius:14px;}
  .ticket:before,.ticket:after{content:"";position:absolute;left:50%;width:70%;height:10px;border-top:2px dashed #888;transform:translateX(-50%);} 
  .ticket:before{top:56%;}
  .ticket:after{display:none;} /* solo una línea perforada central */
  .ticket-header{text-align:center;margin-bottom:0.5rem;}
  .ticket-brand{font-weight:600;font-size:1.05rem;letter-spacing:.5px;}
  .ticket-number{font-family:monospace;font-size:.95rem;background:#f1f3f5;padding:.25rem .55rem;border-radius:6px;display:inline-block;margin-top:.35rem;}
  .ticket-section{margin:0 0 .9rem;}
  .ticket-section h6{font-size:.75rem;text-transform:uppercase;letter-spacing:1px;color:#555;margin:0 0 .35rem;font-weight:600;}
  .kv{display:flex;justify-content:space-between;font-size:.85rem;padding:.35rem .55rem;background:#fafafa;border:1px solid #ececec;border-radius:6px;margin-bottom:.4rem;gap:.75rem;}
  .kv span:first-child{color:#555;font-weight:500;}
  .status-badge{padding:.25rem .55rem;border-radius:20px;font-size:.7rem;font-weight:600;letter-spacing:.5px;text-transform:uppercase;}
  .status-pagado{background:#198754;color:#fff;}
  .status-pendiente{background:#ffc107;color:#222;}
  .qr-box{text-align:center;margin:1rem 0 .3rem;}
  .qr-box img{background:#fff;border:1px solid #ddd;padding:.4rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.08);}
  .divider-label{text-align:center;font-size:.6rem;letter-spacing:1.5px;color:#6c757d;margin:-.2rem 0 .6rem;}
  .ticket-footer{text-align:center;font-size:.6rem;color:#777;margin-top:.75rem;}
  .actions{text-align:center;margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center;}
  .actions .btn{font-size:.8rem;padding:.45rem .85rem;}
  
  /* Estilos de impresión mejorados */
  @media print {
    /* Resetear página */
    @page {
      size: auto;
      margin: 10mm;
    }
    
    /* Ocultar todo excepto el ticket */
    body * {
      visibility: hidden;
    }
    
    /* Mostrar solo el contenido del ticket */
    #ticketPrint,
    #ticketPrint * {
      visibility: visible;
    }
    
    #ticketPrint {
      position: absolute;
      left: 50%;
      top: 0;
      transform: translateX(-50%);
      width: auto;
      margin: 0;
      padding: 0;
    }
    
    /* Ocultar botones y elementos no necesarios */
    .no-print,
    .actions,
    nav,
    header,
    footer,
    .navbar,
    .btn {
      display: none !important;
      visibility: hidden !important;
    }
    
    /* Ajustar estilos del ticket para impresión */
    .ticket-wrapper {
      max-width: 100%;
      margin: 0;
      padding: 0;
    }
    
    .ticket {
      box-shadow: none !important;
      border-color: #000 !important;
      page-break-inside: avoid;
    }
    
    /* Asegurar que el QR se imprima */
    .qr-box img {
      max-width: 180px;
      height: auto;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    
    /* Asegurar colores de badges */
    .status-badge {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    
    /* Ajustar backgrounds para impresión */
    .kv {
      background: #f5f5f5 !important;
      border: 1px solid #ccc !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }
</style>
<div id="ticketPrint" class="ticket-wrapper">
  <div class="ticket shadow-sm">
    <div class="ticket-header">
      <div class="ticket-brand">Plataforma Psicología</div>
      <div class="text-muted" style="font-size:.65rem;letter-spacing:1px;">COMPROBANTE DE PAGO</div>
      <div class="ticket-number">TICKET #<?= (int)$ticket['id'] ?></div>
    </div>
    <div class="ticket-section">
      <h6>Identificación</h6>
      <div class="kv"><span>Código</span><span><?= htmlspecialchars($ticket['codigo']) ?></span></div>
      <div class="kv"><span>Número</span><span><?= htmlspecialchars($ticket['numero_ticket']) ?></span></div>
      <div class="kv"><span>Emisión</span><span><?= htmlspecialchars($ticket['fecha_emision']) ?></span></div>
    </div>
    <?php if(!empty($pago)): $cls = $pago['estado_pago']==='pagado'?'status-pagado':'status-pendiente'; ?>
    <div class="ticket-section">
      <h6>Pago</h6>
      <div class="kv"><span>ID Pago</span><span>#<?= (int)$pago['id'] ?></span></div>
      <div class="kv"><span>Monto Base</span><span>$<?= number_format((float)$pago['monto_base'],2) ?></span></div>
      <div class="kv"><span>Monto Total</span><strong>$<?= number_format((float)$pago['monto_total'],2) ?></strong></div>
      <div class="kv"><span>Estado</span><span class="status-badge <?= $cls ?>"><?= strtoupper(htmlspecialchars($pago['estado_pago'])) ?></span></div>
    </div>
    <?php endif; ?>
    <div class="divider-label">AUTENTICACIÓN</div>
    <div class="qr-box">
      <?php $proxy = RUTA.'ticket/qr/'.(int)$ticket['id']; ?>
      <img id="qrTicketImg" src="<?= htmlspecialchars($proxy) ?>" width="180" height="180" alt="QR Ticket">
      <div class="small text-muted mt-1" style="font-size:.55rem;">DATA: PAGO:<?= htmlspecialchars($ticket['id_pago'] ?? '') ?></div>
    </div>
    <div class="ticket-footer">Este ticket es válido solo dentro de la plataforma. Conserve para su registro.</div>
  </div>
  <div class="actions no-print mt-3">
    <a class="btn btn-primary" href="<?= RUTA ?>ticket/pdf/<?= (int)$ticket['id'] ?>" target="_blank">
      <i class="fas fa-file-pdf me-1"></i>Descargar PDF
    </a>
    <button class="btn btn-outline-primary" onclick="imprimirTicket()">
      <i class="fas fa-print me-1"></i>Imprimir (Navegador)
    </button>
    <a class="btn btn-outline-secondary" id="btnDescargarQR" href="<?= htmlspecialchars($proxy) ?>?dl=1">
      <i class="fas fa-qrcode me-1"></i>Descargar QR
    </a>
    <a href="javascript:history.back()" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
  </div>
</div>

<script>
// Intentar recargar QR sin cache si falla
const img = document.getElementById('qrTicketImg');
if(img){
  img.addEventListener('error', ()=>{
    if(!img.dataset.retry){
      img.dataset.retry='1';
      img.src = img.src.split('?')[0]+'?t='+Date.now();
    }
  });
}

// Función mejorada de impresión
function imprimirTicket() {
  // Asegurar que la imagen QR esté cargada antes de imprimir
  const qrImg = document.getElementById('qrTicketImg');
  
  if (qrImg && !qrImg.complete) {
    // Si la imagen aún no está cargada, esperar
    qrImg.onload = function() {
      setTimeout(() => window.print(), 150);
    };
    qrImg.onerror = function() {
      const confirmPrint = confirm('Advertencia: El código QR no se pudo cargar completamente.\n\n¿Desea continuar con la impresión de todas formas?');
      if (confirmPrint) {
        setTimeout(() => window.print(), 100);
      }
    };
  } else {
    // Imagen ya cargada, imprimir inmediatamente
    window.print();
  }
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
  // Ctrl+P para imprimir
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    e.preventDefault();
    imprimirTicket();
  }
  
  // Ctrl+D para descargar PDF
  if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
    e.preventDefault();
    window.location.href = '<?= RUTA ?>ticket/pdf/<?= (int)$ticket['id'] ?>';
  }
});

// Mensaje después de imprimir (opcional)
window.addEventListener('afterprint', function() {
  console.log('Ticket impreso exitosamente');
});
</script>
