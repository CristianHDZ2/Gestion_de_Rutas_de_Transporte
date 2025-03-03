<?php
$pageTitle = "Lista de Motoristas";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Procesar la eliminación de motorista
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Verificar si hay rutas asociadas a este motorista
    $checkQuery = "SELECT COUNT(*) as count FROM rutas WHERE motorista_id = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $checkRow = mysqli_fetch_assoc($checkResult);
    
    if ($checkRow['count'] > 0) {
        // Si hay rutas asociadas, solo inactivar
        $queryInactivar = "UPDATE motoristas SET estado = 'Inactivo' WHERE id = ?";
        $stmtInactivar = mysqli_prepare($conn, $queryInactivar);
        mysqli_stmt_bind_param($stmtInactivar, "i", $id);
        
        if (mysqli_stmt_execute($stmtInactivar)) {
            showAlert("El motorista ha sido inactivado correctamente ya que tiene rutas asociadas", "warning");
        } else {
            showAlert("Error al inactivar el motorista: " . mysqli_error($conn), "danger");
        }
    } else {
        // Si no hay rutas asociadas, eliminar
        $queryEliminar = "DELETE FROM motoristas WHERE id = ?";
        $stmtEliminar = mysqli_prepare($conn, $queryEliminar);
        mysqli_stmt_bind_param($stmtEliminar, "i", $id);
        
        if (mysqli_stmt_execute($stmtEliminar)) {
            showAlert("El motorista ha sido eliminado correctamente", "success");
        } else {
            showAlert("Error al eliminar el motorista: " . mysqli_error($conn), "danger");
        }
    }
}

// Consultar todos los motoristas
$query = "SELECT * FROM motoristas ORDER BY nombre, apellido ASC";
$result = mysqli_query($conn, $query);
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
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>motoristas/lista_motoristas.php">Lista de motoristas</a></li>
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
        <h1><i class="bi bi-person-badge"></i> Lista de Motoristas</h1>
        <a href="agregar_motorista.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Motorista
        </a>
    </div>

    <!-- Buscador -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchTable" class="form-control" placeholder="Buscar motorista...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de motoristas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>DUI</th>
                            <th>Teléfono</th>
                            <th>Licencia</th>
                            <th>Estado</th>
                            <th>Fecha de Registro</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($row['dui']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($row['licencia']); ?></td>
                            <td>
                                <?php if ($row['estado'] == 'Activo'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="editar_motorista.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="lista_motoristas.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay motoristas registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="<?php echo $baseUrl; ?>assets/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="<?php echo $baseUrl; ?>assets/js/scripts.js"></script>
</body>
</html>