<?php
$pageTitle = "Agregar Vehículo";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $placa = sanitize($_POST['placa']);
    $modelo = sanitize($_POST['modelo']);
    $capacidad = sanitize($_POST['capacidad']);
    
    // Validar campos
    $errors = [];
    
    if (empty($placa)) {
        $errors[] = "La placa es obligatoria";
    }
    
    // Verificar si la placa ya existe
    $checkPlaca = "SELECT id FROM vehiculos WHERE placa = ?";
    $stmtPlaca = mysqli_prepare($conn, $checkPlaca);
    mysqli_stmt_bind_param($stmtPlaca, "s", $placa);
    mysqli_stmt_execute($stmtPlaca);
    mysqli_stmt_store_result($stmtPlaca);
    
    if (mysqli_stmt_num_rows($stmtPlaca) > 0) {
        $errors[] = "La placa ya está registrada en el sistema";
    }
    
    // Si no hay errores, guardar el vehículo
    if (empty($errors)) {
        $query = "INSERT INTO vehiculos (placa, modelo, capacidad) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $placa, $modelo, $capacidad);
        
        if (mysqli_stmt_execute($stmt)) {
            showAlert("Vehículo agregado correctamente", "success");
            header("Location: lista_vehiculos.php");
            exit;
        } else {
            showAlert("Error al agregar el vehículo: " . mysqli_error($conn), "danger");
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
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-truck"></i> Vehículos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>vehiculos/lista_vehiculos.php">Lista de vehículos</a></li>
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>vehiculos/agregar_vehiculo.php">Agregar vehículo</a></li>
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
        <h1><i class="bi bi-plus-circle"></i> Agregar Vehículo</h1>
        <a href="lista_vehiculos.php" class="btn btn-secondary">
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

    <!-- Formulario de agregar vehículo -->
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="placa" class="form-label">Placa <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                            <input type="text" class="form-control" id="placa" name="placa" value="<?php echo isset($_POST['placa']) ? htmlspecialchars($_POST['placa']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-truck-front"></i></span>
                            <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="capacidad" class="form-label">Capacidad (personas)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-people"></i></span>
                            <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" value="<?php echo isset($_POST['capacidad']) ? htmlspecialchars($_POST['capacidad']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Vehículo
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