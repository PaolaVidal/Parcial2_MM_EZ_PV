<h2 class="h5 mb-3">Mi Panel</h2>
<p>DUI: <strong><?= htmlspecialchars($paciente['dui']) ?></strong></p>
<ul>
  <li><a href="<?= RUTA ?>public/citas">Historial de Citas</a></li>
  <li><a href="<?= RUTA ?>public/pagos">Pagos y Tickets</a></li>
  <li><a href="<?= RUTA ?>public/solicitud">Solicitar cambio de información</a></li>
  <li><a href="<?= RUTA ?>public/salir">Salir</a></li>
</ul>