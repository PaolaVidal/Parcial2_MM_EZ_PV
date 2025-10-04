<h1 class="h3 mb-3">Pago #<?= htmlspecialchars($pago['id']) ?></h1>
<div class="row">
  <div class="col-md-6">
    <ul class="list-group mb-3">
      <li class="list-group-item"><strong>ID Cita:</strong> <?= $pago['id_cita'] ?></li>
      <li class="list-group-item"><strong>Monto Base:</strong> $<?= number_format($pago['monto_base'],2) ?></li>
      <li class="list-group-item"><strong>Monto Total:</strong> $<?= number_format($pago['monto_total'],2) ?></li>
      <li class="list-group-item"><strong>Estado Pago:</strong> <?= $pago['estado_pago'] ?></li>
    </ul>
    <?php if($pago['estado_pago'] !== 'pagado'): ?>
    <form method="post" class="card card-body mb-3">
      <h5>Agregar Extra</h5>
      <input type="hidden" name="accion" value="agregar_extra">
      <div class="mb-2">
        <input type="text" name="descripcion" class="form-control" placeholder="Descripción" required>
      </div>
      <div class="mb-2">
        <input type="number" step="0.01" name="monto" class="form-control" placeholder="Monto" required>
      </div>
      <button class="btn btn-sm btn-outline-primary">Agregar</button>
    </form>
    <form method="post" onsubmit="return confirm('¿Marcar como pagado?');">
      <input type="hidden" name="accion" value="marcar_pagado">
      <button class="btn btn-success w-100">Marcar como Pagado y Generar Ticket</button>
    </form>
    <?php endif; ?>
  </div>
  <div class="col-md-6">
    <h5>Extras</h5>
    <ul class="list-group mb-3">
      <?php foreach($extras as $e): ?>
        <li class="list-group-item d-flex justify-content-between"><span><?= htmlspecialchars($e['descripcion']) ?></span><span>$<?= number_format($e['monto'],2) ?></span></li>
      <?php endforeach; ?>
      <?php if(empty($extras)): ?><li class="list-group-item">Sin extras</li><?php endif; ?>
    </ul>

    <?php if($ticket): ?>
      <div class="card p-3 text-center">
        <h5>Ticket Generado</h5>
        <p>Código: <strong><?= htmlspecialchars($ticket['codigo']) ?></strong></p>
  <img src="<?= htmlspecialchars($ticket['qr_code']) ?>" width="180" alt="QR Ticket">
  <p class="mt-2"><a class="btn btn-outline-secondary btn-sm" href="<?= url('Ticket','verPago',['id'=>$pago['id']]) ?>">Ver Detalle</a></p>
      </div>
    <?php endif; ?>
  </div>
</div>
<a href="<?= url('Pago','index') ?>" class="btn btn-secondary mt-3">Volver</a>
