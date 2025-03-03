<?php
$pageTitle = "Reporte General";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Obtener el mes y año seleccionados o usar el actual
$mesActual = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
$anioActual = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

// Validar mes y año
if ($mesActual < 1 || $mesActual > 12) {
    $mesActual = intval(date('m'));
}
if ($anioActual < 2020 || $anioActual > 2050) {
    $anioActual = intval(date('Y'));
}

// Nombres de los meses en español
$nombresMeses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

// Construir fechas para el rango del mes
$primerDia = date('Y-m-01', strtotime("$anioActual-$mesActual-01"));
$ultimoDia = date('Y-m-t', strtotime("$anioActual-$mesActual-01"));

// Estadísticas generales
// Total de rutas activas
$queryRutas = "SELECT COUNT(*) as total FROM rutas WHERE estado = 'Activa'";
$resultRutas = mysqli_query($conn, $queryRutas);
$totalRutas = mysqli_fetch_assoc($resultRutas)['total'];

// Total de vehículos activos
$queryVehiculos = "SELECT COUNT(*) as total FROM vehiculos WHERE estado = 'Activo'";
$resultVehiculos = mysqli_query($conn, $queryVehiculos);
$totalVehiculos = mysqli_fetch_assoc($resultVehiculos)['total'];

// Total de motoristas activos
$queryMotoristas = "SELECT COUNT(*) as total FROM motoristas WHERE estado = 'Activo'";
$resultMotoristas = mysqli_query($conn, $queryMotoristas);
$totalMotoristas = mysqli_fetch_assoc($resultMotoristas)['total'];

// Ingresos del mes
$queryIngresos = "SELECT SUM(monto) as total FROM dias_trabajo 
                 WHERE fecha BETWEEN ? AND ? AND estado_entrega = 'Recibido'";
$stmtIngresos = mysqli_prepare($conn, $queryIngresos);
mysqli_stmt_bind_param($stmtIngresos, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtIngresos);
$resultIngresos = mysqli_stmt_get_result($stmtIngresos);
$totalIngresos = mysqli_fetch_assoc($resultIngresos)['total'] ?? 0;

// Pendientes del mes
$queryPendientes = "SELECT SUM(monto) as total FROM dias_trabajo 
                   WHERE fecha BETWEEN ? AND ? AND estado_entrega = 'Pendiente'";
$stmtPendientes = mysqli_prepare($conn, $queryPendientes);
mysqli_stmt_bind_param($stmtPendientes, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtPendientes);
$resultPendientes = mysqli_stmt_get_result($stmtPendientes);
$totalPendientes = mysqli_fetch_assoc($resultPendientes)['total'] ?? 0;

// Número de días con ingresos recibidos en el mes
$queryDiasRecibidos = "SELECT COUNT(*) as total FROM dias_trabajo 
                      WHERE fecha BETWEEN ? AND ? AND estado_entrega = 'Recibido'";
$stmtDiasRecibidos = mysqli_prepare($conn, $queryDiasRecibidos);
mysqli_stmt_bind_param($stmtDiasRecibidos, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtDiasRecibidos);
$resultDiasRecibidos = mysqli_stmt_get_result($stmtDiasRecibidos);
$totalDiasRecibidos = mysqli_fetch_assoc($resultDiasRecibidos)['total'];

// Número de días pendientes en el mes
$queryDiasPendientes = "SELECT COUNT(*) as total FROM dias_trabajo 
                       WHERE fecha BETWEEN ? AND ? AND estado_entrega = 'Pendiente'";
$stmtDiasPendientes = mysqli_prepare($conn, $queryDiasPendientes);
mysqli_stmt_bind_param($stmtDiasPendientes, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtDiasPendientes);
$resultDiasPendientes = mysqli_stmt_get_result($stmtDiasPendientes);
$totalDiasPendientes = mysqli_fetch_assoc($resultDiasPendientes)['total'];

// Ingresos por ruta
$queryRutaIngreso = "SELECT r.numero, r.origen, r.destino, SUM(dt.monto) as total, 
                            COUNT(dt.id) as dias, SUM(dt.monto)/COUNT(dt.id) as promedio
                     FROM dias_trabajo dt
                     JOIN rutas r ON dt.ruta_id = r.id
                     WHERE dt.fecha BETWEEN ? AND ? AND dt.estado_entrega = 'Recibido'
                     GROUP BY r.id
                     ORDER BY total DESC";
$stmtRutaIngreso = mysqli_prepare($conn, $queryRutaIngreso);
mysqli_stmt_bind_param($stmtRutaIngreso, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtRutaIngreso);
$resultRutaIngreso = mysqli_stmt_get_result($stmtRutaIngreso);

// Ingresos por vehículo
$queryVehiculoIngreso = "SELECT v.placa, v.modelo, SUM(dt.monto) as total, 
                                COUNT(dt.id) as dias, SUM(dt.monto)/COUNT(dt.id) as promedio
                         FROM dias_trabajo dt
                         JOIN rutas r ON dt.ruta_id = r.id
                         JOIN vehiculos v ON r.vehiculo_id = v.id
                         WHERE dt.fecha BETWEEN ? AND ? AND dt.estado_entrega = 'Recibido'
                         GROUP BY v.id
                         ORDER BY total DESC";
$stmtVehiculoIngreso = mysqli_prepare($conn, $queryVehiculoIngreso);
mysqli_stmt_bind_param($stmtVehiculoIngreso, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtVehiculoIngreso);
$resultVehiculoIngreso = mysqli_stmt_get_result($stmtVehiculoIngreso);

// Ingresos por motorista
$queryMotoristaIngreso = "SELECT CONCAT(m.nombre, ' ', m.apellido) as nombre, SUM(dt.monto) as total, 
                                COUNT(dt.id) as dias, SUM(dt.monto)/COUNT(dt.id) as promedio
                           FROM dias_trabajo dt
                           JOIN rutas r ON dt.ruta_id = r.id
                           JOIN motoristas m ON r.motorista_id = m.id
                           WHERE dt.fecha BETWEEN ? AND ? AND dt.estado_entrega = 'Recibido'
                           GROUP BY m.id
                           ORDER BY total DESC";
$stmtMotoristaIngreso = mysqli_prepare($conn, $queryMotoristaIngreso);
mysqli_stmt_bind_param($stmtMotoristaIngreso, "ss", $primerDia, $ultimoDia);
mysqli_stmt_execute($stmtMotoristaIngreso);
$resultMotoristaIngreso = mysqli_stmt_get_result($stmtMotoristaIngreso);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Control de Transporte</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/styles.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $baseUrl; ?>inicio.php">
            <i class="bi bi-bus-front"></i> Sistema de Transporte
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>inicio.php"><i class="bi bi-speedometer2"></i> Inicio</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-signpost-2"></i> Rutas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>rutas/lista_rutas.php">Lista de rutas</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>rutas/agregar_ruta.php">Agregar ruta</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-truck"></i> Vehículos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>vehiculos/lista_vehiculos.php">Lista de vehículos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>vehiculos/agregar_vehiculo.php">Agregar vehículo</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-badge"></i> Motoristas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>motoristas/lista_motoristas.php">Lista de motoristas</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>motoristas/agregar_motorista.php">Agregar motorista</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar-check"></i> Ingresos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>ingresos/calendario.php">Calendario</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>ingresos/registrar_ingreso.php">Registrar ingreso</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>reportes/dias_recibidos.php">Días recibidos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>reportes/dias_pendientes.php">Días pendientes</a></li>
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>reportes/general.php">Reporte general</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name'] ?? 'Usuario'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>includes/backup.php"><i class="bi bi-download"></i> Copia de seguridad</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>index.php?logout=1"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php displayAlert(); ?>

    <!-- Encabezado del reporte para imprimir -->
    <div class="print-header d-none d-print-block">
        <h1 class="text-center">Sistema de Control de Transporte</h1>
        <h2 class="text-center"><?php echo $pageTitle; ?></h2>
        <p class="text-center">
            Mes: <?php echo $nombresMeses[$mesActual]; ?> <?php echo $anioActual; ?>
        </p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="no-print"><i class="bi bi-graph-up"></i> <?php echo $pageTitle; ?></h1>
        <div class="no-print">
            <button type="button" id="printReport" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Selector de mes -->
    <div class="card mb-4 no-print">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-month"></i> Seleccionar Período</h5>
        </div>
        <div class="card-body">
            <form method="get" action="general.php" id="filtroMes">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="mes" class="form-label">Mes</label>
                        <select class="form-select" id="mes" name="mes" onchange="document.getElementById('filtroMes').submit()">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($i == $mesActual) ? 'selected' : ''; ?>>
                                    <?php echo $nombresMeses[$i]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="anio" class="form-label">Año</label>
                        <select class="form-select" id="anio" name="anio" onchange="document.getElementById('filtroMes').submit()">
                            <?php for ($i = 2020; $i <= 2050; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($i == $anioActual) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen general -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Resumen General - <?php echo $nombresMeses[$mesActual]; ?> <?php echo $anioActual; ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card border-success h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-cash-stack"></i> Ingresos Recibidos</h5>
                            <h3 class="card-text">$<?php echo number_format($totalIngresos, 2); ?></h3>
                            <p class="text-muted"><?php echo $totalDiasRecibidos; ?> días registrados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-warning h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-hourglass-split"></i> Ingresos Pendientes</h5>
                            <h3 class="card-text">$<?php echo number_format($totalPendientes, 2); ?></h3>
                            <p class="text-muted"><?php echo $totalDiasPendientes; ?> días pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-info h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-calculator"></i> Total</h5>
                            <h3 class="card-text">$<?php echo number_format($totalIngresos + $totalPendientes, 2); ?></h3>
                            <p class="text-muted"><?php echo $totalDiasRecibidos + $totalDiasPendientes; ?> días totales</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de la flota -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-truck"></i> Estadísticas de la Flota</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card border-primary h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-signpost-2"></i> Rutas Activas</h5>
                            <h3 class="card-text"><?php echo $totalRutas; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-primary h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-truck"></i> Vehículos Activos</h5>
                            <h3 class="card-text"><?php echo $totalVehiculos; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-primary h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-person-badge"></i> Motoristas Activos</h5>
                            <h3 class="card-text"><?php echo $totalMotoristas; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ingresos por Ruta -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-signpost-2"></i> Ingresos por Ruta</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Ruta</th>
                            <th>Origen - Destino</th>
                            <th class="text-center">Días</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Promedio/Día</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultRutaIngreso) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($resultRutaIngreso)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['numero']); ?></td>
                                    <td><?php echo htmlspecialchars($row['origen'] . ' - ' . $row['destino']); ?></td>
                                    <td class="text-center"><?php echo $row['dias']; ?></td>
                                    <td class="text-end">$<?php echo number_format($row['total'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($row['promedio'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No hay ingresos registrados para este período</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ingresos por Vehículo -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-truck"></i> Ingresos por Vehículo</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th class="text-center">Días</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Promedio/Día</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultVehiculoIngreso) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($resultVehiculoIngreso)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['placa']); ?></td>
                                    <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                                    <td class="text-center"><?php echo $row['dias']; ?></td>
                                    <td class="text-end">$<?php echo number_format($row['total'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($row['promedio'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No hay ingresos registrados para este período</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ingresos por Motorista -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-person-badge"></i> Ingresos por Motorista</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Motorista</th>
                            <th class="text-center">Días</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Promedio/Día</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultMotoristaIngreso) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($resultMotoristaIngreso)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                    <td class="text-center"><?php echo $row['dias']; ?></td>
                                    <td class="text-end">$<?php echo number_format($row['total'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($row['promedio'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay ingresos registrados para este período</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pie de página para imprimir -->
    <div class="print-footer d-none d-print-block">
        <p>Fecha de impresión: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>Sistema de Control de Transporte</p>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="<?php echo $baseUrl; ?>assets/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="<?php echo $baseUrl; ?>assets/js/scripts.js"></script>
</body>
</html>