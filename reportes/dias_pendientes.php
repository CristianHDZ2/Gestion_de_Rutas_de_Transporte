<?php
$pageTitle = "Reporte de Días Pendientes";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Obtener los filtros
$rutaId = isset($_GET['ruta']) ? intval($_GET['ruta']) : 0;
$vehiculoId = isset($_GET['vehiculo']) ? intval($_GET['vehiculo']) : 0;
$motoristaId = isset($_GET['motorista']) ? intval($_GET['motorista']) : 0;

// Obtener las rutas, vehículos y motoristas para los filtros
$queryRutas = "SELECT id, numero, origen, destino FROM rutas ORDER BY numero ASC";
$resultRutas = mysqli_query($conn, $queryRutas);

$queryVehiculos = "SELECT id, placa FROM vehiculos ORDER BY placa ASC";
$resultVehiculos = mysqli_query($conn, $queryVehiculos);

$queryMotoristas = "SELECT id, nombre, apellido FROM motoristas ORDER BY nombre, apellido ASC";
$resultMotoristas = mysqli_query($conn, $queryMotoristas);

// Construir la consulta para obtener los días pendientes
$query = "SELECT dt.id, dt.fecha, dt.tipo, dt.monto, dt.observaciones, 
                 r.numero as ruta_numero, r.origen, r.destino, 
                 v.placa, CONCAT(m.nombre, ' ', m.apellido) as motorista
          FROM dias_trabajo dt
          JOIN rutas r ON dt.ruta_id = r.id
          JOIN vehiculos v ON r.vehiculo_id = v.id
          JOIN motoristas m ON r.motorista_id = m.id
          WHERE dt.estado_entrega = 'Pendiente'";

$params = [];
$types = "";

if ($rutaId > 0) {
    $query .= " AND dt.ruta_id = ?";
    $params[] = $rutaId;
    $types .= "i";
}

if ($vehiculoId > 0) {
    $query .= " AND r.vehiculo_id = ?";
    $params[] = $vehiculoId;
    $types .= "i";
}

if ($motoristaId > 0) {
    $query .= " AND r.motorista_id = ?";
    $params[] = $motoristaId;
    $types .= "i";
}

$query .= " ORDER BY dt.fecha ASC";

// Ejecutar la consulta
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Calcular el total pendiente
$totalPendiente = 0;
$dias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $totalPendiente += $row['monto'];
    $dias[] = $row;
}

// Volver a ejecutar la consulta para recorrer los resultados
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>reportes/dias_pendientes.php">Días pendientes</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>reportes/general.php">Reporte general</a></li>
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
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>includes/restore_backup.php"><i class="bi bi-upload"></i> Restaurar copia</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>force_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
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
            Fecha de impresión: <?php echo date('d/m/Y'); ?>
        </p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="no-print"><i class="bi bi-hourglass-split"></i> <?php echo $pageTitle; ?></h1>
        <div class="no-print">
            <button type="button" id="printReport" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Filtros del reporte -->
    <div class="card mb-4 no-print">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros del Reporte</h5>
        </div>
        <div class="card-body">
            <form method="get" action="dias_pendientes.php">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="ruta" class="form-label">Ruta</label>
                        <select class="form-select" id="ruta" name="ruta">
                            <option value="0">Todas</option>
                            <?php mysqli_data_seek($resultRutas, 0); ?>
                            <?php while ($ruta = mysqli_fetch_assoc($resultRutas)): ?>
                                <option value="<?php echo $ruta['id']; ?>" <?php echo ($rutaId == $ruta['id']) ? 'selected' : ''; ?>>
                                    <?php echo $ruta['numero']; ?> - <?php echo $ruta['origen']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="vehiculo" class="form-label">Vehículo</label>
                        <select class="form-select" id="vehiculo" name="vehiculo">
                            <option value="0">Todos</option>
                            <?php mysqli_data_seek($resultVehiculos, 0); ?>
                            <?php while ($vehiculo = mysqli_fetch_assoc($resultVehiculos)): ?>
                                <option value="<?php echo $vehiculo['id']; ?>" <?php echo ($vehiculoId == $vehiculo['id']) ? 'selected' : ''; ?>>
                                    <?php echo $vehiculo['placa']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="motorista" class="form-label">Motorista</label>
                        <select class="form-select" id="motorista" name="motorista">
                            <option value="0">Todos</option>
                            <?php mysqli_data_seek($resultMotoristas, 0); ?>
                            <?php while ($motorista = mysqli_fetch_assoc($resultMotoristas)): ?>
                                <option value="<?php echo $motorista['id']; ?>" <?php echo ($motoristaId == $motorista['id']) ? 'selected' : ''; ?>>
                                    <?php echo $motorista['nombre'] . ' ' . $motorista['apellido']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen del reporte -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Resumen de Pendientes</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h6 class="card-title">Total Pendiente</h6>
                            <h3 class="card-text">$<?php echo number_format($totalPendiente, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h6 class="card-title">Días Pendientes</h6>
                            <h3 class="card-text"><?php echo count($dias); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h6 class="card-title">Días Más Antiguos</h6>
                            <h3 class="card-text">
                                <?php
                                if (count($dias) > 0) {
                                    $fechaAntigua = date('Y-m-d');
                                    foreach ($dias as $dia) {
                                        if ($dia['fecha'] < $fechaAntigua) {
                                            $fechaAntigua = $dia['fecha'];
                                        }
                                    }
                                    echo date('d/m/Y', strtotime($fechaAntigua));
                                } else {
                                    echo "N/A";
                                }
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de días pendientes -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-table"></i> Detalle de Días Pendientes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Ruta</th>
                            <th>Vehículo</th>
                            <th>Motorista</th>
                            <th>Tipo</th>
                            <th class="text-end">Monto</th>
                            <th>Observaciones</th>
                            <th class="no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['ruta_numero'] . ' - ' . $row['origen']); ?></td>
                                    <td><?php echo htmlspecialchars($row['placa']); ?></td>
                                    <td><?php echo htmlspecialchars($row['motorista']); ?></td>
                                    <td>
                                        <?php if ($row['tipo'] == 'Trabajo'): ?>
                                            <span class="badge bg-success">Trabajo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Refuerzo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">$<?php echo number_format($row['monto'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['observaciones'] ?? ''); ?></td>
                                    <td class="no-print">
                                        <a href="<?php echo $baseUrl; ?>ingresos/registrar_ingreso.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <tr class="table-warning">
                                <td colspan="5" class="fw-bold text-end">Total Pendiente:</td>
                                <td class="fw-bold text-end">$<?php echo number_format($totalPendiente, 2); ?></td>
                                <td></td>
                                <td class="no-print"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay días pendientes</td>
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