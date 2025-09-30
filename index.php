<?php 
define("RUTA", "http://localhost/Parcial2_MM_EZ_PV/");

// archivos de configuración
require_once "./config/rutas.php";

// objetos 
$rutas = new Rutas();

?>

<!doctype html>
<html lang="es">
<head>
    <title>Biblioteca MVC</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.3 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous"
    />
</head>

<body>
    <header>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="<?= RUTA; ?>">Clínica Psicológica</a>
                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                    aria-controls="navbarNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= RUTA; ?>autor">Autores</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= RUTA; ?>libro">Libros</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= RUTA; ?>categoria">Categorías</a>
                        </li>
                    </ul>
                    <form class="d-flex">
                        <input class="form-control me-2" type="search" placeholder="Buscar" />
                        <button class="btn btn-outline-light" type="submit">Buscar</button>
                    </form>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container mt-4">
            <?php 
            if (isset($_GET["url"])) {               
                $datos = explode("/", $_GET["url"]);
                $pagina = $datos[0];
                $accion = $datos[1] ?? "index";

                // buscar el controlador desde rutas.php
                require_once $rutas->obtenerRuta($pagina);
                
                $nombreClase = $pagina . "controller";
                if (class_exists($nombreClase)) {
                    $controlador = new $nombreClase();

                    if (method_exists($controlador, $accion)) {
                        if (isset($datos[2])) {
                            $controlador->{$accion}($datos[2]);
                        } else {
                            $controlador->{$accion}();
                        }
                    }
                } else {
                    require_once "vistas/404.php";
                }                                   
            } else {
                require_once "vistas/inicio.php";
            }
            ?>
        </div>
    </main>

    <footer class="bg-dark text-white text-center p-3 mt-4">
        &copy; <?= date("Y"); ?> Clínica Psicológica - Proyecto de Parcial
    </footer>

    <!-- Bootstrap JavaScript -->
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"
    ></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
        crossorigin="anonymous"
    ></script>
</body>
</html>
