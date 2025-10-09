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
      <?php
      // Logo redirige al dashboard según el rol
      $homeUrl = 'index.php';
      if (isset($_SESSION['usuario'])) {
        $rolActual = $_SESSION['usuario']['rol'] ?? '';
        if ($rolActual === 'admin') {
          $homeUrl = url('admin', 'dashboard');
        } elseif ($rolActual === 'psicologo') {
          $homeUrl = url('psicologo', 'dashboard');
        } elseif ($rolActual === 'paciente') {
          // Panel de paciente (login por código)
          $homeUrl = url('public', 'panel');
        }
      }
      ?>
      <a class="navbar-brand" href="<?= $homeUrl ?>">Psicología</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav"
        aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php if (isset($_SESSION['usuario'])): ?>
            <?php $rol = $_SESSION['usuario']['rol'] ?? ''; ?>
            <?php if ($rol === 'paciente'): ?>
              <li class="nav-item"><a class="nav-link" href="<?= url('public', 'panel') ?>"><i
                    class="fas fa-home me-1"></i>Panel</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= url('public', 'citas') ?>"><i
                    class="fas fa-calendar-alt me-1"></i>Mis Citas</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= url('public', 'pagos') ?>"><i
                    class="fas fa-file-invoice-dollar me-1"></i>Mis Pagos</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= url('public', 'disponibilidad') ?>"><i
                    class="fas fa-user-md me-1"></i>Psicólogos</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= url('public', 'solicitud') ?>"><i
                    class="fas fa-edit me-1"></i>Mis Datos</a></li>
            <?php elseif ($rol === 'admin'): ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><i
                    class="fas fa-tools me-1"></i>Admin</a>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="<?= url('admin', 'dashboard') ?>"><i
                        class="fas fa-gauge me-2"></i>Dashboard</a></li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'estadisticas') ?>"><i
                        class="fas fa-chart-bar me-2"></i>Estadísticas</a></li>
                  <li>
                    <hr class="dropdown-divider" />
                  </li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'usuarios') ?>"><i
                        class="fas fa-users me-2"></i>Usuarios</a></li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'pacientes') ?>"><i
                        class="fas fa-user-injured me-2"></i>Pacientes</a></li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'psicologos') ?>"><i
                        class="fas fa-user-md me-2"></i>Psicólogos</a></li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'citas') ?>"><i
                        class="fas fa-calendar me-2"></i>Citas</a></li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'solicitudes') ?>"><i
                        class="fas fa-inbox me-2"></i>Solicitudes</a></li>
                  <li><a class="dropdown-item" href="<?= url('admin', 'horarios') ?>"><i
                        class="fas fa-clock me-2"></i>Horarios</a></li>
                </ul>
              </li>
            <?php elseif ($rol === 'psicologo'): ?>
              <li class="nav-item"><a class="nav-link" href="<?= url('psicologo', 'dashboard') ?>"><i
                    class="fas fa-gauge me-1"></i>Dashboard</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= url('psicologo', 'citas') ?>"><i
                    class="fas fa-list me-1"></i>Citas</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= url('ticket') ?>"><i
                    class="fas fa-ticket me-1"></i>Tickets</a></li>
            <?php endif; ?>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('public', 'disponibilidad') ?>"><i
                  class="fas fa-user-md me-1"></i>Psicólogos</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('public', 'acceso') ?>"><i
                  class="fas fa-user-circle me-1"></i>Portal Paciente</a></li>
          <?php endif; ?>
        </ul>
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <?php if (isset($_SESSION['usuario'])): ?>
            <li class="nav-item"><span class="nav-link">Hola,
                <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span></li>
            <?php if (($_SESSION['usuario']['rol'] ?? '') === 'paciente'): ?>
              <li class="nav-item"><a class="nav-link" href="<?= url('public', 'salir') ?>">Salir</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="<?= url('Auth', 'logout') ?>">Salir</a></li>
            <?php endif; ?>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('Auth', 'login') ?>">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container">