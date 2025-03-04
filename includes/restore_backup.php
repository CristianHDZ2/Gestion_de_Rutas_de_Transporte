<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar que el usuario está logueado y es administrador
checkLogin();

// Solo los administradores deberían poder restaurar copias
if (!isAdmin()) {
    showAlert("Solo los administradores pueden restaurar copias de seguridad", "danger");
    header("Location: ../inicio.php");
    exit;
}

$mensaje = "";
$tipoMensaje = "";

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['backup_file'])) {
    $archivo = $_FILES['backup_file'];
    
    // Verificar que es un archivo JSON
    $tipoArchivo = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    if (strtolower($tipoArchivo) != 'json') {
        $mensaje = "El archivo debe ser de tipo JSON";
        $tipoMensaje = "danger";
    } 
    // Verificar que no hubo errores en la subida
    else if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensaje = "Error al subir el archivo: " . obtenerErrorSubida($archivo['error']);
        $tipoMensaje = "danger";
    } 
    // Continuar con la restauración
    else {
        // Leer el contenido del archivo
        $contenidoJson = file_get_contents($archivo['tmp_name']);
        $datos = json_decode($contenidoJson, true);
        
        // Verificar que el JSON es válido y tiene la estructura correcta
        if ($datos === null) {
            $mensaje = "El archivo JSON no es válido";
            $tipoMensaje = "danger";
        } 
        else if (!validarEstructuraBackup($datos)) {
            $mensaje = "El archivo no tiene la estructura correcta de un backup";
            $tipoMensaje = "danger";
        } 
        else {
            // Iniciar una transacción para asegurar la integridad
            mysqli_begin_transaction($conn);
            
            try {
                // Restaurar datos
                if (restaurarDatos($datos, $conn)) {
                    mysqli_commit($conn);
                    $mensaje = "La copia de seguridad ha sido restaurada correctamente";
                    $tipoMensaje = "success";
                } else {
                    throw new Exception("Error al restaurar los datos");
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $mensaje = "Error en la restauración: " . $e->getMessage();
                $tipoMensaje = "danger";
            }
        }
    }
    
    // Guardar mensaje para mostrarlo tras redirección
    if (!empty($mensaje)) {
        showAlert($mensaje, $tipoMensaje);
    }
    
    // Si fue exitoso, redirigir al inicio
    if ($tipoMensaje === "success") {
        header("Location: ../inicio.php");
        exit;
    }
}

// Función para validar la estructura del backup
function validarEstructuraBackup($datos) {
    // Verificar que existen las tablas principales
    $tablasNecesarias = ['vehiculos', 'motoristas', 'rutas', 'dias_trabajo', 'usuarios'];
    foreach ($tablasNecesarias as $tabla) {
        if (!isset($datos[$tabla]) || !is_array($datos[$tabla])) {
            return false;
        }
    }
    return true;
}

// Función para restaurar los datos
function restaurarDatos($datos, $conn) {
    // Orden de restauración para respetar las referencias
    $ordenTablas = ['usuarios', 'vehiculos', 'motoristas', 'rutas', 'dias_trabajo'];
    
    // Eliminar datos actuales en orden inverso para evitar restricciones de FK
    foreach (array_reverse($ordenTablas) as $tabla) {
        $query = "DELETE FROM $tabla";
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al limpiar la tabla $tabla: " . mysqli_error($conn));
        }
    }
    
    // Restaurar datos en el orden correcto
    foreach ($ordenTablas as $tabla) {
        if (!empty($datos[$tabla])) {
            foreach ($datos[$tabla] as $fila) {
                // Construir query de inserción
                $campos = array_keys($fila);
                $valores = array_map(function($valor) use ($conn) {
                    if ($valor === null) {
                        return "NULL";
                    } else {
                        return "'" . mysqli_real_escape_string($conn, $valor) . "'";
                    }
                }, array_values($fila));
                
                $query = "INSERT INTO $tabla (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $valores) . ")";
                
                if (!mysqli_query($conn, $query)) {
                    throw new Exception("Error al insertar en $tabla: " . mysqli_error($conn));
                }
            }
        }
    }
    
    return true;
}

// Función para obtener mensajes de error de subida
function obtenerErrorSubida($codigo) {
    switch ($codigo) {
        case UPLOAD_ERR_INI_SIZE:
            return "El archivo excede el tamaño máximo permitido por PHP";
        case UPLOAD_ERR_FORM_SIZE:
            return "El archivo excede el tamaño máximo permitido por el formulario";
        case UPLOAD_ERR_PARTIAL:
            return "El archivo se subió parcialmente";
        case UPLOAD_ERR_NO_FILE:
            return "No se subió ningún archivo";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Falta una carpeta temporal";
        case UPLOAD_ERR_CANT_WRITE:
            return "Error al escribir el archivo en el disco";
        case UPLOAD_ERR_EXTENSION:
            return "Una extensión de PHP detuvo la subida";
        default:
            return "Error desconocido";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Copia de Seguridad - Sistema de Control de Transporte</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="../inicio.php">
            <i class="bi bi-bus-front"></i> Sistema de Transporte
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../inicio.php"><i class="bi bi-speedometer2"></i> Inicio</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-signpost-2"></i> Rutas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../rutas/lista_rutas.php">Lista de rutas</a></li>
                        <li><a class="dropdown-item" href="../rutas/agregar_ruta.php">Agregar ruta</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-truck"></i> Vehículos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../vehiculos/lista_vehiculos.php">Lista de vehículos</a></li>
                        <li><a class="dropdown-item" href="../vehiculos/agregar_vehiculo.php">Agregar vehículo</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-badge"></i> Motoristas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../motoristas/lista_motoristas.php">Lista de motoristas</a></li>
                        <li><a class="dropdown-item" href="../motoristas/agregar_motorista.php">Agregar motorista</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar-check"></i> Ingresos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../ingresos/calendario.php">Calendario</a></li>
                        <li><a class="dropdown-item" href="../ingresos/registrar_ingreso.php">Registrar ingreso</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../reportes/dias_recibidos.php">Días recibidos</a></li>
                        <li><a class="dropdown-item" href="../reportes/dias_pendientes.php">Días pendientes</a></li>
                        <li><a class="dropdown-item" href="../reportes/general.php">Reporte general</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name'] ?? 'Usuario'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item active" href="backup.php"><i class="bi bi-download"></i> Copia de seguridad</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../force_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php displayAlert(); ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-upload"></i> Restaurar Copia de Seguridad</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Advertencia:</strong> Restaurar una copia de seguridad sobrescribirá completamente los datos actuales. Esta acción no se puede deshacer.
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="backup_file" class="form-label">Archivo de copia de seguridad (JSON)</label>
                    <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".json" required>
                    <div class="form-text">Seleccione el archivo JSON de copia de seguridad generado previamente.</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="confirm_restore" required>
                    <label class="form-check-label" for="confirm_restore">Confirmo que quiero restaurar la base de datos con esta copia de seguridad y entiendo que se perderán todos los datos actuales.</label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="../inicio.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-upload"></i> Restaurar Copia de Seguridad
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>