<?php
$pageTitle = "Calendario de Trabajo";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Obtener la ruta seleccionada
$rutaId = isset($_GET['ruta']) ? intval($_GET['ruta']) : 0;

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

// Obtener todas las rutas activas
$queryRutas = "SELECT id, numero, origen, destino FROM rutas WHERE estado = 'Activa' ORDER BY numero ASC";
$resultRutas = mysqli_query($conn, $queryRutas);

// Si no hay ruta seleccionada y hay rutas disponibles, usar la primera
if ($rutaId == 0 && mysqli_num_rows($resultRutas) > 0) {
    $rowRuta = mysqli_fetch_assoc($resultRutas);
    $rutaId = $rowRuta['id'];
    // Reiniciar el puntero del resultado
    mysqli_data_seek($resultRutas, 0);
}

// Obtener información de la ruta seleccionada
$infoRuta = null;
if ($rutaId > 0) {
    $queryInfoRuta = "SELECT r.*, v.placa, CONCAT(m.nombre, ' ', m.apellido) as motorista 
                      FROM rutas r 
                      JOIN vehiculos v ON r.vehiculo_id = v.id 
                      JOIN motoristas m ON r.motorista_id = m.id 
                      WHERE r.id = ?";
    $stmtInfoRuta = mysqli_prepare($conn, $queryInfoRuta);
    mysqli_stmt_bind_param($stmtInfoRuta, "i", $rutaId);
    mysqli_stmt_execute($stmtInfoRuta);
    $resultInfoRuta = mysqli_stmt_get_result($stmtInfoRuta);
    $infoRuta = mysqli_fetch_assoc($resultInfoRuta);
}

// Obtener los días del mes seleccionado para la ruta seleccionada
$diasTrabajo = [];
if ($rutaId > 0) {
    $diasTrabajo = obtenerCalendarioTrabajo($rutaId, $mesActual, $anioActual);
}

// Nombres de los meses en español
$nombresMeses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

// Nombres de los días de la semana en español
$nombresDias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];

// Primer día del mes
$primerDia = mktime(0, 0, 0, $mesActual, 1, $anioActual);
$numeroDias = date("t", $primerDia);
$diaSemanaInicio = date("w", $primerDia); // 0 (domingo) a 6 (sábado)

// Crear un arreglo con los datos del calendario
$calendario = [];
$diaActual = 1;

// Si hay rutas pero la ruta seleccionada no existe o está inactiva
if (mysqli_num_rows($resultRutas) > 0 && (!$infoRuta || $infoRuta['estado'] == 'Inactiva')) {
    // Seleccionar la primera ruta activa
    mysqli_data_seek($resultRutas, 0);
    $rowRuta = mysqli_fetch_assoc($resultRutas);
    $rutaId = $rowRuta['id'];
    header("Location: calendario.php?ruta=$rutaId&mes=$mesActual&anio=$anioActual");
    exit;
}

// Organizar los días de trabajo por fecha
$diasPorFecha = [];
foreach ($diasTrabajo as $dia) {
    $fecha = date('j', strtotime($dia['fecha'])); // Día del mes sin ceros iniciales
    $diasPorFecha[$fecha] = $dia;
}
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
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar-check"></i> Ingresos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>ingresos/calendario.php">Calendario</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>ingresos/registrar_ingreso.php">Registrar ingreso</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>reportes/dias_recibidos.php">Días recibidos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>reportes/dias_pendientes.php">Días pendientes</a></li>
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-calendar-week"></i> Calendario de Trabajo</h1>
    </div>

    <!-- Filtros y selección de ruta -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="ruta" class="form-label">Seleccionar Ruta:</label>
                    <select class="form-select" id="ruta" name="ruta" onchange="cambiarRuta(this.value)">
                        <?php if (mysqli_num_rows($resultRutas) == 0): ?>
                            <option value="0">No hay rutas disponibles</option>
                        <?php else: ?>
                            <?php while ($ruta = mysqli_fetch_assoc($resultRutas)): ?>
                                <option value="<?php echo $ruta['id']; ?>" <?php echo ($ruta['id'] == $rutaId) ? 'selected' : ''; ?>>
                                    Ruta <?php echo $ruta['numero']; ?> - <?php echo $ruta['origen']; ?> a <?php echo $ruta['destino']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <?php if ($infoRuta): ?>
                <div class="col-md-6">
                    <form id="filtroCalendario" method="get" action="calendario.php">
                        <input type="hidden" name="ruta" value="<?php echo $rutaId; ?>">
                        <div class="row">
                            <div class="col-6">
                                <label for="mes" class="form-label">Mes:</label>
                                <select class="form-select" id="mes" name="mes">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($i == $mesActual) ? 'selected' : ''; ?>>
                                            <?php echo $nombresMeses[$i]; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="anio" class="form-label">Año:</label>
                                <select class="form-select" id="anio" name="anio">
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($infoRuta): ?>
    <!-- Información de la ruta -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Ruta</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <strong>Ruta:</strong> <?php echo htmlspecialchars($infoRuta['numero']); ?>
                </div>
                <div class="col-md-3 mb-2">
                    <strong>Origen-Destino:</strong> <?php echo htmlspecialchars($infoRuta['origen'] . ' - ' . $infoRuta['destino']); ?>
                </div>
                <div class="col-md-3 mb-2">
                    <strong>Vehículo:</strong> <?php echo htmlspecialchars($infoRuta['placa']); ?>
                </div>
                <div class="col-md-3 mb-2">
                    <strong>Motorista:</strong> <?php echo htmlspecialchars($infoRuta['motorista']); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <strong>Fecha Inicio:</strong> <?php echo date('d/m/Y', strtotime($infoRuta['fecha_inicio'])); ?>
                </div>
                <div class="col-md-4 mb-2">
                    <strong>Fecha Inicio Refuerzo:</strong> <?php echo date('d/m/Y', strtotime($infoRuta['fecha_inicio_refuerzo'])); ?>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-between">
                        <div class="me-3"><span class="badge bg-success">Trabajo</span></div>
                        <div class="me-3"><span class="badge bg-danger">Descanso</span></div>
                        <div><span class="badge bg-warning text-dark">Refuerzo</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendario -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-center">
                <a href="?ruta=<?php echo $rutaId; ?>&mes=<?php echo ($mesActual == 1) ? 12 : $mesActual - 1; ?>&anio=<?php echo ($mesActual == 1) ? $anioActual - 1 : $anioActual; ?>" class="btn btn-sm btn-light me-2">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <?php echo $nombresMeses[$mesActual]; ?> <?php echo $anioActual; ?>
                <a href="?ruta=<?php echo $rutaId; ?>&mes=<?php echo ($mesActual == 12) ? 1 : $mesActual + 1; ?>&anio=<?php echo ($mesActual == 12) ? $anioActual + 1 : $anioActual; ?>" class="btn btn-sm btn-light ms-2">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered calendar-table">
                    <thead>
                        <tr>
                            <?php foreach ($nombresDias as $dia): ?>
                                <th><?php echo $dia; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php
                            // Celdas vacías hasta el día de inicio
                            for ($i = 0; $i < $diaSemanaInicio; $i++) {
                                echo '<td class="calendar-day"></td>';
                            }
                            
                            // Rellenar con los días del mes
                            $diaColumna = $diaSemanaInicio;
                            for ($dia = 1; $dia <= $numeroDias; $dia++) {
                                // Si es el primer día de la semana, iniciar una nueva fila
                                if ($diaColumna == 0 && $dia > 1) {
                                    echo '</tr><tr>';
                                }
                                
                                // Determinar si hay información para este día
                                $claseDia = "";
                                $contenidoDia = "<div class='calendar-day-header'>$dia</div>";
                                
                                if (isset($diasPorFecha[$dia])) {
                                    $diaTrabajo = $diasPorFecha[$dia];
                                    
                                    // Determinar clase según tipo de día
                                    if ($diaTrabajo['tipo'] == 'Trabajo') {
                                        $claseDia = "day-trabajo";
                                    } elseif ($diaTrabajo['tipo'] == 'Descanso') {
                                        $claseDia = "day-descanso";
                                    } elseif ($diaTrabajo['tipo'] == 'Refuerzo') {
                                        $claseDia = "day-refuerzo";
                                    }
                                    
                                    // Agregar clase adicional según estado de entrega
                                    if ($diaTrabajo['estado_entrega'] == 'Pendiente') {
                                        $claseDia .= " day-pending";
                                    } elseif ($diaTrabajo['estado_entrega'] == 'Recibido') {
                                        $claseDia .= " day-received";
                                    }
                                    
                                    // Agregar tipo y contador (si existe)
                                    $contenidoDia .= "<div><strong>" . $diaTrabajo['tipo'] . "</strong>";
                                    if ($diaTrabajo['contador_ciclo']) {
                                        $contenidoDia .= " (" . $diaTrabajo['contador_ciclo'] . ")";
                                    }
                                    $contenidoDia .= "</div>";
                                    
                                    // Agregar monto y estado si existe
                                    if ($diaTrabajo['monto']) {
                                        $contenidoDia .= "<div class='day-amount'>$" . number_format($diaTrabajo['monto'], 2) . "</div>";
                                        $contenidoDia .= "<div class='badge " . ($diaTrabajo['estado_entrega'] == 'Recibido' ? 'bg-success' : 'bg-warning text-dark') . " mt-1'>" . $diaTrabajo['estado_entrega'] . "</div>";
                                    }
                                    
                                    // Agregar enlace para registrar ingreso
                                    if ($diaTrabajo['tipo'] != 'Descanso') {
                                        $contenidoDia .= "<div class='mt-1'>";
                                        if ($diaTrabajo['monto']) {
                                            $contenidoDia .= "<a href='registrar_ingreso.php?id=" . $diaTrabajo['id'] . "' class='btn btn-sm btn-outline-primary'>Editar</a>";
                                        } else {
                                            $contenidoDia .= "<a href='registrar_ingreso.php?id=" . $diaTrabajo['id'] . "' class='btn btn-sm btn-primary'>Registrar</a>";
                                        }
                                        $contenidoDia .= "</div>";
                                    }
                                }
                                
                                echo "<td class='calendar-day $claseDia'>$contenidoDia</td>";
                                
                                $diaColumna = ($diaColumna + 1) % 7;
                            }
                            
                            // Completar la última fila con celdas vacías si es necesario
                            while ($diaColumna > 0 && $diaColumna < 7) {
                                echo '<td class="calendar-day"></td>';
                                $diaColumna++;
                            }
                            ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Mensaje si no hay rutas -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No hay rutas disponibles. Por favor, <a href="<?php echo $baseUrl; ?>rutas/agregar_ruta.php" class="alert-link">agregue una ruta</a> para ver el calendario de trabajo.
    </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="<?php echo $baseUrl; ?>assets/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="<?php echo $baseUrl; ?>assets/js/scripts.js"></script>
<script>
    function cambiarRuta(rutaId) {
        window.location.href = "calendario.php?ruta=" + rutaId + "&mes=<?php echo $mesActual; ?>&anio=<?php echo $anioActual; ?>";
    }
</script>
</body>
</html>