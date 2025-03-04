<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Verificar si el usuario está logueado (excepto en la página de login)
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'index.php') {
    checkLogin();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control de Transporte</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php if ($current_page != 'index.php'): ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="inicio.php">
                <i class="bi bi-bus-front"></i> Sistema de Transporte
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="inicio.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-signpost-2"></i> Rutas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="rutas/lista_rutas.php">Lista de rutas</a></li>
                            <li><a class="dropdown-item" href="rutas/agregar_ruta.php">Agregar ruta</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-truck"></i> Vehículos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="vehiculos/lista_vehiculos.php">Lista de vehículos</a></li>
                            <li><a class="dropdown-item" href="vehiculos/agregar_vehiculo.php">Agregar vehículo</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-badge"></i> Motoristas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="motoristas/lista_motoristas.php">Lista de motoristas</a></li>
                            <li><a class="dropdown-item" href="motoristas/agregar_motorista.php">Agregar motorista</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar-check"></i> Ingresos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="ingresos/calendario.php">Calendario</a></li>
                            <li><a class="dropdown-item" href="ingresos/registrar_ingreso.php">Registrar ingreso</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="reportes/dias_recibidos.php">Días recibidos</a></li>
                            <li><a class="dropdown-item" href="reportes/dias_pendientes.php">Días pendientes</a></li>
                            <li><a class="dropdown-item" href="reportes/general.php">Reporte general</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name'] ?? 'Usuario'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="includes/backup.php"><i class="bi bi-download"></i> Copia de seguridad</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <a class="dropdown-item" href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : dirname($_SERVER['PHP_SELF']); ?>/force_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container mt-4">
        <?php displayAlert(); ?>
