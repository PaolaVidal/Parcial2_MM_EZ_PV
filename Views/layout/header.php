<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Psicología - MVC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
  <a class="navbar-brand" href="index.php">Psicología</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if(isset($_SESSION['usuario'])): ?>
          <!-- Navegación principal de la clínica -->
          <li class="nav-item"><a class="nav-link" href="<?= url('Cita','index') ?>">Citas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('Pago','index') ?>">Pagos</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('Ticket','verPago',['id'=>1]) ?>" title="Ejemplo de Ticket">Tickets</a></li>
          <?php if($_SESSION['usuario']['rol']==='admin'): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= url('admin','dashboard') ?>">Dashboard</a></li>
                <li><a class="dropdown-item" href="<?= url('admin','usuarios') ?>">Usuarios</a></li>
                <li><a class="dropdown-item" href="<?= url('admin','pacientes') ?>">Pacientes</a></li>
                <li><a class="dropdown-item" href="<?= url('admin','psicologos') ?>">Psicólogos</a></li>
                <li><a class="dropdown-item" href="<?= url('admin','citas') ?>">Citas</a></li>
                <li><a class="dropdown-item" href="<?= url('admin','pagos') ?>">Pagos</a></li>
                <li><a class="dropdown-item" href="<?= url('admin','solicitudes') ?>">Solicitudes</a></li>
              </ul>
            </li>
          <?php endif; ?>
          <!-- Si quieres condicionar por rol:
          <?php /* if($_SESSION['usuario']['rol']==='admin'){ echo '<li class=\'nav-item\'><a class=\'nav-link\' href=?controller=Admin&action=dashboard>Admin</a></li>'; } */ ?>
          -->
        <?php else: ?>
          <li class="nav-item"><span class="nav-link disabled">(Inicia sesión para navegar)</span></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if(isset($_SESSION['usuario'])): ?>
          <li class="nav-item"><span class="nav-link">Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('Auth','logout') ?>">Salir</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('Auth','login') ?>">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
