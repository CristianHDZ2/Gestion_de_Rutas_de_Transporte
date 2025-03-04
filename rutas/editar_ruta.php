<?php
$pageTitle = "Editar Ruta";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Verificar si se proporciona un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    showAlert("ID de ruta no válido", "danger");
    header("Location: lista_rutas.php");
    exit;
}

$id = $_GET['id'];

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $numero = sanitize($_POST['numero']);
    $origen = sanitize($_POST['origen']);
    $destino = sanitize($_POST['destino']);
    $vehiculo_id = sanitize($_POST['vehiculo_id']);
    $motorista_id = sanitize($_POST['motorista_id']);
    $estado = sanitize($_POST['estado']);
    $fecha_inicio_refuerzo = sanitize($_POST['fecha_inicio_refuerzo']);
    
    // Validar campos
    $errors = [];
    
    if (empty($numero)) {
        $errors[] = "El número de ruta es obligatorio";
    }
    
    if (empty($origen)) {
        $errors[] = "El origen es obligatorio";
    }
    
    if (empty($destino)) {
        $errors[] = "El destino es obligatorio";
    }
    
    if (empty($vehiculo_id)) {
        $errors[] = "Debe seleccionar un vehículo";
    }
    
    if (empty($motorista_id)) {
        $errors[] = "Debe seleccionar un motorista";
    }
    
    if (empty($fecha_inicio_refuerzo)) {
        $errors[] = "La fecha de inicio de refuerzo es obligatoria";
    }
    
    // Si no hay errores, actualizar la ruta
    if (empty($errors)) {
        // Obtener la fecha de inicio de refuerzo actual
        $queryFecha = "SELECT fecha_inicio_refuerzo FROM rutas WHERE id = ?";
        $stmtFecha = mysqli_prepare($conn, $queryFecha);
        mysqli_stmt_bind_param($stmtFecha, "i", $id);
        mysqli_stmt_execute($stmtFecha);
        $resultFecha = mysqli_stmt_get_result($stmtFecha);
        $rowFecha = mysqli_fetch_assoc($resultFecha);
        $fechaRefuerzoAnterior = $rowFecha['fecha_inicio_refuerzo'];
        
        // Actualizar la información de la ruta
        $query = "UPDATE rutas SET numero = ?, origen = ?, destino = ?, vehiculo_id = ?, motorista_id = ?, estado = ?, fecha_inicio_refuerzo = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssssi", $numero, $origen, $destino, $vehiculo_id, $motorista_id, $estado, $fecha_inicio_refuerzo, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Verificar si la fecha de inicio de refuerzo ha cambiado
            if ($fechaRefuerzoAnterior != $fecha_inicio_refuerzo) {
                // Regenerar el calendario de trabajo a partir de la nueva fecha
                $resultado = regenerarCalendarioTrabajo($id, $fecha_inicio_refuerzo);
                
                if ($resultado) {
                    showAlert("Ruta y calendario actualizados correctamente", "success");
                } else {
                    showAlert("Ruta actualizada, pero hubo problemas al actualizar el calendario", "warning");
                }
            } else {
                showAlert("Ruta actualizada correctamente", "success");
            }
            
            header("Location: lista_rutas.php");
            exit;
        } else {
            showAlert("Error al actualizar la ruta: " . mysqli_error($conn), "danger");
        }
    }
}

// Obtener datos de la ruta
$query = "SELECT * FROM rutas WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    showAlert("Ruta no encontrada", "danger");
    header("Location: lista_rutas.php");
    exit;
}

$ruta = mysqli_fetch_assoc($result);

// Obtener vehículos activos
$queryVehiculos = "SELECT id, placa, modelo FROM vehiculos WHERE estado = 'Activo' OR id = ? ORDER BY placa";
$stmtVehiculos = mysqli_prepare($conn, $queryVehiculos);
mysqli_stmt_bind_param($stmtVehiculos, "i", $ruta['vehiculo_id']);
mysqli_stmt_execute($stmtVehiculos);
$resultVehiculos = mysqli_stmt_get_result($stmtVehiculos);

// Obtener motoristas activos
$queryMotoristas = "SELECT id, nombre, apellido FROM motoristas WHERE estado = 'Activo' OR id = ? ORDER BY nombre, apellido";
$stmtMotoristas = mysqli_prepare($conn, $queryMotoristas);
mysqli_stmt_bind_param($stmtMotoristas, "i", $ruta['motorista_id']);
mysqli_stmt_execute($stmtMotoristas);
$resultMotoristas = mysqli_stmt_get_result($stmtMotoristas);
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
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
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
        <h1><i class="bi bi-pencil-square"></i> Editar Ruta</h1>
        <a href="lista_rutas.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <!-- Mostrar errores si existen -->
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Formulario de editar ruta -->
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="numero" class="form-label">Número de Ruta <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-123"></i></span>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($ruta['numero']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="origen" class="form-label">Origen <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" class="form-control" id="origen" name="origen" value="<?php echo htmlspecialchars($ruta['origen']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="destino" class="form-label">Destino <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                            <input type="text" class="form-control" id="destino" name="destino" value="<?php echo htmlspecialchars($ruta['destino']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="vehiculo_id" class="form-label">Vehículo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-truck"></i></span>
                            <select class="form-select" id="vehiculo_id" name="vehiculo_id" required>
                                <option value="">Seleccione un vehículo</option>
                                <?php while ($vehiculo = mysqli_fetch_assoc($resultVehiculos)): ?>
                                    <option value="<?php echo $vehiculo['id']; ?>" <?php echo ($ruta['vehiculo_id'] == $vehiculo['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vehiculo['placa'] . ' - ' . $vehiculo['modelo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="motorista_id" class="form-label">Motorista <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <select class="form-select" id="motorista_id" name="motorista_id" required>
                                <option value="">Seleccione un motorista</option>
                                <?php while ($motorista = mysqli_fetch_assoc($resultMotoristas)): ?>
                                    <option value="<?php echo $motorista['id']; ?>" <?php echo ($ruta['motorista_id'] == $motorista['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($motorista['nombre'] . ' ' . $motorista['apellido']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="Activa" <?php echo ($ruta['estado'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                <option value="Inactiva" <?php echo ($ruta['estado'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Inicio</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                            <input type="date" class="form-control" value="<?php echo htmlspecialchars($ruta['fecha_inicio']); ?>" disabled>
                        </div>
                        <div class="form-text">La fecha de inicio no se puede modificar</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="fecha_inicio_refuerzo" class="form-label">Fecha de Inicio de Refuerzo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-plus"></i></span>
                            <input type="date" class="form-control" id="fecha_inicio_refuerzo" name="fecha_inicio_refuerzo" value="<?php echo htmlspecialchars($ruta['fecha_inicio_refuerzo']); ?>" required>
                        </div>
                        <div class="form-text">Esta fecha determina cuándo inician los días de refuerzo. Al cambiarla, solo se afectarán los días futuros.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Nota: Si modifica la fecha de inicio de refuerzo, se actualizará el calendario a partir de esa fecha. Los días de trabajo pasados no se modificarán.
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="<?php echo $baseUrl; ?>assets/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="<?php echo $baseUrl; ?>assets/js/scripts.js"></script>
</body>
</html>