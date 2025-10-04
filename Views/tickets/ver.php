<?php if(!$ticket){ echo '<div class="alert alert-warning">Ticket no encontrado.</div>'; return; } ?>
<h1 class="h3 mb-3">Ticket #<?= htmlspecialchars($ticket['id']) ?></h1>
<ul class="list-group mb-3">
  <li class="list-group-item"><strong>Código:</strong> <?= htmlspecialchars($ticket['codigo']) ?></li>
  <li class="list-group-item"><strong>Número:</strong> <?= htmlspecialchars($ticket['numero_ticket']) ?></li>
  <li class="list-group-item"><strong>Fecha Emisión:</strong> <?= htmlspecialchars($ticket['fecha_emision']) ?></li>
</ul>
<div class="text-center">
  <img src="<?= htmlspecialchars($ticket['qr_code']) ?>" width="220" alt="QR Ticket">
</div>
<a href="<?= url('Pago','index') ?>" class="btn btn-secondary mt-3">Volver a Pagos</a>
