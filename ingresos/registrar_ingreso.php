<?php
$pageTitle = "Registrar Ingreso";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Verificar si se proporciona un ID válido
$diaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($diaId == 0) {
    // Si no se proporciona un ID, mostrar selector de ruta
    $rutaSeleccionada = false;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ruta_id'])) {
        $rutaId = intval($_POST['ruta_id']);
        $fecha = sanitize($_POST['fecha']);
        
        // Validar que la fecha y ruta son válidas
        if (empty($rutaId) || empty($fecha)) {
            $error = "Debe seleccionar una ruta y una fecha válida";
        } else {
            // Buscar el día de trabajo correspondiente
            $query = "SELECT id FROM dias_trabajo WHERE ruta_id = ? AND fecha = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "is", $rutaId, $fecha);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Redirigir al formulario de ingreso con el ID encontrado
                header("Location: registrar_ingreso.php?id=" . $row['id']);
                exit;
            } else {
                $error = "No se encontró un día de trabajo para la fecha y ruta seleccionadas";
            }
        }
    }
    
    // Obtener todas las rutas activas
    $queryRutas = "SELECT id, numero, origen, destino FROM rutas WHERE estado = 'Activa' ORDER BY numero ASC";
    $resultRutas = mysqli_query($conn, $queryRutas);
} else {
    // Si se proporciona un ID, obtener información del día
    $rutaSeleccionada = true;
    
    $query = "SELECT dt.*, r.numero as ruta_numero, r.origen, r.destino, v.placa, CONCAT(m.nombre, ' ', m.apellido) as motorista_nombre
              FROM dias_trabajo dt
              JOIN rutas r ON dt.ruta_id = r.id
              JOIN vehiculos v ON r.vehiculo_id = v.id
              JOIN motoristas m ON r.motorista_id = m.id
              WHERE dt.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $diaId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$row = mysqli_fetch_assoc($result)) {
        showAlert("Día de trabajo no encontrado", "danger");
        header("Location: calendario.php");
        exit;
    }
    
    // Si es un día de descanso, no permitir registrar ingreso
    if ($row['tipo'] == 'Descanso') {
        showAlert("No se puede registrar ingreso en un día de descanso", "danger");
        header("Location: calendario.php");
        exit;
    }
    
// Procesar el formulario de registro de ingreso
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $monto = isset($_POST['monto']) && !empty($_POST['monto']) ? floatval($_POST['monto']) : null;
    $estado = isset($_POST['estado_entrega']) ? sanitize($_POST['estado_entrega']) : null;
    $observaciones = isset($_POST['observaciones']) ? sanitize($_POST['observaciones']) : '';
    
    // Validar campos
    if (empty($monto) && empty($observaciones)) {
        $error = "Debe ingresar un monto o una observación";
    } elseif (!empty($monto) && empty($estado)) {
        $error = "Debe seleccionar un estado de entrega si ingresa un monto";
    } else {
        // Registrar el ingreso o la observación
        list($success, $message) = registrarIngreso($diaId, $monto, $estado, $observaciones);
        
        if ($success) {
            showAlert($message, "success");
            header("Location: calendario.php?ruta=" . $row['ruta_id']);
            exit;
        } else {
            $error = $message;
        }
    }
}
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
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>ingresos/calendario.php">Calendario</a></li>
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>ingresos/registrar_ingreso.php">Registrar ingreso</a></li>
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $rutaSeleccionada ? '<i class="bi bi-cash"></i> Registrar Ingreso' : '<i class="bi bi-search"></i> Buscar Día de Trabajo'; ?></h1>
        <a href="calendario.php<?php echo $rutaSeleccionada ? '?ruta=' . $row['ruta_id'] : ''; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Calendario
        </a>
    </div>

    <!-- Mostrar error si existe -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (!$rutaSeleccionada): ?>
    <!-- Selector de ruta y fecha -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-search"></i> Buscar Día de Trabajo</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ruta_id" class="form-label">Ruta <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-signpost-2"></i></span>
                            <select class="form-select" id="ruta_id" name="ruta_id" required>
                                <option value="">Seleccione una ruta</option>
                                <?php if (mysqli_num_rows($resultRutas) > 0): ?>
                                    <?php while ($ruta = mysqli_fetch_assoc($resultRutas)): ?>
                                        <option value="<?php echo $ruta['id']; ?>">
                                            Ruta <?php echo $ruta['numero']; ?> - <?php echo $ruta['origen']; ?> a <?php echo $ruta['destino']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No hay rutas disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Seleccione una ruta y una fecha para buscar el día de trabajo correspondiente. Solo podrá registrar ingresos en días de trabajo y refuerzo.
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar Día de Trabajo
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Formulario de registro de ingreso -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-cash"></i> Registrar Ingreso</h5>
        </div>
        <div class="card-body">
            <!-- Información del día -->
            <div class="alert <?php echo $row['tipo'] == 'Refuerzo' ? 'alert-warning' : 'alert-success'; ?> mb-4">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <strong>Ruta:</strong> <?php echo htmlspecialchars($row['ruta_numero']); ?> (<?php echo htmlspecialchars($row['origen']); ?> - <?php echo htmlspecialchars($row['destino']); ?>)
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($row['fecha'])); ?> 
                        <span class="badge <?php echo $row['tipo'] == 'Refuerzo' ? 'bg-warning text-dark' : 'bg-success'; ?> ms-2">
                            <?php echo $row['tipo']; ?>
                            <?php if ($row['contador_ciclo']): ?>
                                (<?php echo $row['contador_ciclo']; ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Vehículo:</strong> <?php echo htmlspecialchars($row['placa']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Motorista:</strong> <?php echo htmlspecialchars($row['motorista_nombre']); ?>
                    </div>
                </div>
            </div>
            
            <form id="ingresoForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $diaId); ?>">
                <input type="hidden" name="tipo_dia" id="tipo_dia" value="<?php echo $row['tipo']; ?>">
                
                <div class="row">
    <div class="col-md-6 mb-3">
        <label for="monto" class="form-label">Monto ($)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
            <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0.01" value="<?php echo $row['monto'] ? $row['monto'] : ''; ?>">
        </div>
        <small class="form-text text-muted">Deje en blanco si no hubo ingreso</small>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="estado_entrega" class="form-label">Estado</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
            <select class="form-select" id="estado_entrega" name="estado_entrega">
                <option value="">Seleccione un estado</option>
                <option value="Pendiente" <?php echo ($row['estado_entrega'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="Recibido" <?php echo ($row['estado_entrega'] == 'Recibido') ? 'selected' : ''; ?>>Recibido</option>
            </select>
        </div>
        <small class="form-text text-muted">Requerido solo si ingresa un monto</small>
    </div>
</div>

<div class="mb-3">
    <label for="observaciones" class="form-label">Observaciones</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-card-text"></i></span>
        <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Ingrese observaciones o razón por la que no se trabajó"><?php echo htmlspecialchars($row['observaciones'] ?? ''); ?></textarea>
    </div>
</div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $row['monto'] ? 'Actualizar Ingreso' : 'Registrar Ingreso'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const montoInput = document.getElementById('monto');
    const estadoSelect = document.getElementById('estado_entrega');
    
    function checkMonto() {
        if (montoInput.value && parseFloat(montoInput.value) > 0) {
            estadoSelect.setAttribute('required', 'required');
        } else {
            estadoSelect.removeAttribute('required');
        }
    }
    
    // Verificar al cargar
    checkMonto();
    
    // Verificar cuando cambia el monto
    montoInput.addEventListener('input', checkMonto);
});
</script>

<!-- Bootstrap JS -->
<script src="<?php echo $baseUrl; ?>assets/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="<?php echo $baseUrl; ?>assets/js/scripts.js"></script>
</body>
</html>