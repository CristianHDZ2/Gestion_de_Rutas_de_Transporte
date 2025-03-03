<?php
$pageTitle = "Editar Motorista";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Verificar si se proporciona un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    showAlert("ID de motorista no válido", "danger");
    header("Location: lista_motoristas.php");
    exit;
}

$id = $_GET['id'];

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $nombre = sanitize($_POST['nombre']);
    $apellido = sanitize($_POST['apellido']);
    $dui = sanitize($_POST['dui']);
    $telefono = sanitize($_POST['telefono']);
    $licencia = sanitize($_POST['licencia']);
    $estado = sanitize($_POST['estado']);
    
    // Validar campos
    $errors = [];
    
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($apellido)) {
        $errors[] = "El apellido es obligatorio";
    }
    
    // Verificar si el DUI ya existe (excluyendo el motorista actual)
    if (!empty($dui)) {
        $checkDui = "SELECT id FROM motoristas WHERE dui = ? AND id != ?";
        $stmtDui = mysqli_prepare($conn, $checkDui);
        mysqli_stmt_bind_param($stmtDui, "si", $dui, $id);
        mysqli_stmt_execute($stmtDui);
        mysqli_stmt_store_result($stmtDui);
        
        if (mysqli_stmt_num_rows($stmtDui) > 0) {
            $errors[] = "El DUI ya está registrado en el sistema";
        }
    }
    
    // Si no hay errores, actualizar el motorista
    if (empty($errors)) {
        $query = "UPDATE motoristas SET nombre = ?, apellido = ?, dui = ?, telefono = ?, licencia = ?, estado = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $nombre, $apellido, $dui, $telefono, $licencia, $estado, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            showAlert("Motorista actualizado correctamente", "success");
            header("Location: lista_motoristas.php");
            exit;
        } else {
            showAlert("Error al actualizar el motorista: " . mysqli_error($conn), "danger");
        }
    }
}

// Obtener datos del motorista
$query = "SELECT * FROM motoristas WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    showAlert("Motorista no encontrado", "danger");
    header("Location: lista_motoristas.php");
    exit;
}

$motorista = mysqli_fetch_assoc($result);
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
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
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
        <h1><i class="bi bi-pencil-square"></i> Editar Motorista</h1>
        <a href="lista_motoristas.php" class="btn btn-secondary">
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

    <!-- Formulario de editar motorista -->
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($motorista['nombre']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($motorista['apellido']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="dui" class="form-label">DUI</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                            <input type="text" class="form-control" id="dui" name="dui" value="<?php echo htmlspecialchars($motorista['dui']); ?>" placeholder="00000000-0">
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($motorista['telefono']); ?>" placeholder="0000-0000">
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="licencia" class="form-label">Licencia</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-card-list"></i></span>
                            <input type="text" class="form-control" id="licencia" name="licencia" value="<?php echo htmlspecialchars($motorista['licencia']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="Activo" <?php echo ($motorista['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo ($motorista['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Registro</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($motorista['fecha_registro'])); ?>" disabled>
                        </div>
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