<?php
$pageTitle = "Lista de Rutas";
$relativePath = "../";
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Ruta relativa para los archivos
$baseUrl = "../";

// Procesar la activación/desactivación de ruta
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = $_GET['toggle'];
    
    // Obtener el estado actual de la ruta
    $queryEstado = "SELECT estado FROM rutas WHERE id = ?";
    $stmtEstado = mysqli_prepare($conn, $queryEstado);
    mysqli_stmt_bind_param($stmtEstado, "i", $id);
    mysqli_stmt_execute($stmtEstado);
    $resultEstado = mysqli_stmt_get_result($stmtEstado);
    $rowEstado = mysqli_fetch_assoc($resultEstado);
    
    if ($rowEstado) {
        // Cambiar el estado (activar/desactivar)
        $nuevoEstado = ($rowEstado['estado'] == 'Activa') ? 'Inactiva' : 'Activa';
        $queryToggle = "UPDATE rutas SET estado = ? WHERE id = ?";
        $stmtToggle = mysqli_prepare($conn, $queryToggle);
        mysqli_stmt_bind_param($stmtToggle, "si", $nuevoEstado, $id);
        
        if (mysqli_stmt_execute($stmtToggle)) {
            $mensaje = ($nuevoEstado == 'Activa') ? "La ruta ha sido activada correctamente" : "La ruta ha sido desactivada correctamente";
            $tipo = ($nuevoEstado == 'Activa') ? "success" : "warning";
            showAlert($mensaje, $tipo);
        } else {
            showAlert("Error al cambiar el estado de la ruta: " . mysqli_error($conn), "danger");
        }
    }
}

// Procesar la eliminación de ruta
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Verificar si hay registros de ingresos
    $checkQuery = "SELECT COUNT(*) as count FROM dias_trabajo WHERE ruta_id = ? AND monto IS NOT NULL";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $checkRow = mysqli_fetch_assoc($checkResult);
    
    // Forzar eliminación si se pasa el parámetro force=1
    $forzarEliminacion = isset($_GET['force']) && $_GET['force'] == 1;
    
    if ($checkRow['count'] > 0 && !$forzarEliminacion) {
        // Si hay registros y no se fuerza la eliminación, mostrar alerta con opción para forzar
        showAlert("Esta ruta tiene registros de ingresos asociados. Si realmente desea eliminarla, <a href='lista_rutas.php?delete=" . $id . "&force=1' class='alert-link'>haga clic aquí</a> para forzar la eliminación.", "warning");
    } else {
        // Primero eliminar los días de trabajo asociados
        $queryDias = "DELETE FROM dias_trabajo WHERE ruta_id = ?";
        $stmtDias = mysqli_prepare($conn, $queryDias);
        mysqli_stmt_bind_param($stmtDias, "i", $id);
        
        if (mysqli_stmt_execute($stmtDias)) {
            // Ahora sí eliminamos la ruta
            $queryEliminar = "DELETE FROM rutas WHERE id = ?";
            $stmtEliminar = mysqli_prepare($conn, $queryEliminar);
            mysqli_stmt_bind_param($stmtEliminar, "i", $id);
            
            if (mysqli_stmt_execute($stmtEliminar)) {
                showAlert("La ruta ha sido eliminada correctamente", "success");
            } else {
                showAlert("Error al eliminar la ruta: " . mysqli_error($conn), "danger");
            }
        } else {
            showAlert("Error al eliminar los días de trabajo asociados: " . mysqli_error($conn), "danger");
        }
    }
}

// Consultar todas las rutas
$query = "SELECT r.*, v.placa as vehiculo_placa, CONCAT(m.nombre, ' ', m.apellido) as motorista_nombre
          FROM rutas r
          JOIN vehiculos v ON r.vehiculo_id = v.id
          JOIN motoristas m ON r.motorista_id = m.id
          ORDER BY r.numero ASC";
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
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-signpost-2"></i> Rutas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item active" href="<?php echo $baseUrl; ?>rutas/lista_rutas.php">Lista de rutas</a></li>
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
        <h1><i class="bi bi-signpost-2"></i> Lista de Rutas</h1>
        <a href="agregar_ruta.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Ruta
        </a>
    </div>

    <!-- Buscador -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchTable" class="form-control" placeholder="Buscar ruta...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de rutas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Número</th>
                            <th>Ruta</th>
                            <th>Vehículo</th>
                            <th>Motorista</th>
                            <th>Fecha Inicio</th>
                            <th>Estado</th>
                            <th width="200">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['numero']); ?></td>
                            <td><?php echo htmlspecialchars($row['origen']); ?> - <?php echo htmlspecialchars($row['destino']); ?></td>
                            <td><?php echo htmlspecialchars($row['vehiculo_placa']); ?></td>
                            <td><?php echo htmlspecialchars($row['motorista_nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?></td>
                            <td>
                                <?php if ($row['estado'] == 'Activa'): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="editar_ruta.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?php echo $baseUrl; ?>ingresos/calendario.php?ruta=<?php echo $row['id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="Ver calendario">
                                        <i class="bi bi-calendar-week"></i>
                                    </a>
                                    <a href="lista_rutas.php?toggle=<?php echo $row['id']; ?>" class="btn btn-sm <?php echo ($row['estado'] == 'Activa') ? 'btn-warning' : 'btn-success'; ?>" data-bs-toggle="tooltip" title="<?php echo ($row['estado'] == 'Activa') ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="bi <?php echo ($row['estado'] == 'Activa') ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i>
                                    </a>
                                    <a href="#" onclick="confirmarEliminacion(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay rutas registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>¿Está seguro que desea eliminar esta ruta? Esta acción eliminará permanentemente la ruta y todos sus registros asociados.</p>
        <p class="text-danger"><strong>Advertencia:</strong> Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmDelete" class="btn btn-danger">Eliminar Definitivamente</a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="<?php echo $baseUrl; ?>assets/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="<?php echo $baseUrl; ?>assets/js/scripts.js"></script>
<script>
// Script para confirmación de eliminación
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función para mostrar el modal de confirmación
function confirmarEliminacion(id) {
    var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    document.getElementById('btnConfirmDelete').href = 'lista_rutas.php?delete=' + id + '&force=1';
    modal.show();
}
</script>
</body>
</html>