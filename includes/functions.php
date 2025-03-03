<?php
// Función para sanitizar entradas
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Función para mostrar mensajes de alerta
function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Función para mostrar la alerta y limpiarla
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['alert']);
    }
}

// Función para generar el calendario de trabajo
function generarCalendarioTrabajo($rutaId, $fechaInicio, $fechaInicioRefuerzo) {
    global $conn;
    
    // Verificar si ya existe un calendario para esta ruta
    $query = "SELECT COUNT(*) as count FROM dias_trabajo WHERE ruta_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $rutaId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    // Si ya existe un calendario, no hacer nada
    if ($row['count'] > 0) {
        return false;
    }
    
    // Convertir fechas a objetos DateTime
    $fecha = new DateTime($fechaInicio);
    $fechaRefuerzo = new DateTime($fechaInicioRefuerzo);
    
    // Ciclo de trabajo (0-basado para facilitar cálculos)
    $cicloTrabajo = [
        // [tipo, contador_ciclo]
        ['Trabajo', null],  // 0 - Trabajo regular
        ['Descanso', 1],    // 1 - Descanso 1
        ['Descanso', 2],    // 2 - Descanso 2
        ['Descanso', 3],    // 3 - Descanso 3
        ['Descanso', 4],    // 4 - Descanso 4
        ['Refuerzo', 5],    // 5 - Refuerzo (Descanso 5)
    ];
    
    // Determinar posición inicial en el ciclo (para fechaInicio)
    $posicionInicial = 0; // Comenzamos con día de trabajo
    
    // Determinar posición inicial del refuerzo
    $posicionInicialRefuerzo = 5; // Posición del refuerzo
    
    // Generar calendario para 365 días
    $diasGenerados = 0;
    $posicion = $posicionInicial;
    $posicionRefuerzo = $posicionInicialRefuerzo;
    $fechaActual = clone $fecha;
    
    // Preparar la consulta de inserción
    $insertQuery = "INSERT INTO dias_trabajo (ruta_id, fecha, tipo, contador_ciclo) VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);
    
    while ($diasGenerados < 365) {
        $diaSemana = $fechaActual->format('w'); // 0 (domingo) a 6 (sábado)
        
        // Si es domingo (0), no se cuenta en el ciclo pero se registra como trabajo/descanso según corresponda
        if ($diaSemana != 0) {
            // Para días que no son domingo
            $tipo = $cicloTrabajo[$posicion][0];
            $contador = $cicloTrabajo[$posicion][1];
            
            // Si es día de refuerzo, verificamos si coincide con la fecha de refuerzo
            if ($tipo == 'Refuerzo') {
                // Si la fecha actual es anterior a la fecha de inicio de refuerzo, lo tratamos como día normal de trabajo
                if ($fechaActual < $fechaRefuerzo) {
                    $tipo = 'Trabajo';
                    $contador = null;
                }
            }
            
            // Avanzar posición para el próximo día
            $posicion = ($posicion + 1) % count($cicloTrabajo);
        } else {
            // Para domingos - siempre es día de trabajo pero no se cuenta en el ciclo
            $tipo = 'Trabajo';
            $contador = null; // No se cuenta en el ciclo
        }
        
        // Insertar en la base de datos
        mysqli_stmt_bind_param($insertStmt, "issi", $rutaId, $fechaActual->format('Y-m-d'), $tipo, $contador);
        mysqli_stmt_execute($insertStmt);
        
        // Avanzar al siguiente día
        $fechaActual->modify('+1 day');
        $diasGenerados++;
    }
    
    mysqli_stmt_close($insertStmt);
    return true;
}

// Función para obtener el calendario de trabajo de una ruta
function obtenerCalendarioTrabajo($rutaId, $mes = null, $anio = null) {
    global $conn;
    
    // Si no se especifica mes y año, usar el mes actual
    if ($mes === null || $anio === null) {
        $mes = date('m');
        $anio = date('Y');
    }
    
    $primerDia = "$anio-$mes-01";
    $ultimoDia = date('Y-m-t', strtotime($primerDia));
    
    $query = "SELECT dt.*, r.numero as ruta_numero, v.placa, m.nombre as motorista_nombre, m.apellido as motorista_apellido
              FROM dias_trabajo dt
              JOIN rutas r ON dt.ruta_id = r.id
              JOIN vehiculos v ON r.vehiculo_id = v.id
              JOIN motoristas m ON r.motorista_id = m.id
              WHERE dt.ruta_id = ? AND dt.fecha BETWEEN ? AND ?
              ORDER BY dt.fecha ASC";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $rutaId, $primerDia, $ultimoDia);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $calendario = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $calendario[] = $row;
    }
    
    return $calendario;
}

// Función para registrar ingreso en un día específico
function registrarIngreso($diaId, $monto, $estadoEntrega, $observaciones) {
    global $conn;
    
    // Verificar que el día existe y es día de trabajo o refuerzo
    $query = "SELECT tipo FROM dias_trabajo WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $diaId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['tipo'] == 'Descanso') {
            return [false, "No se puede registrar ingreso en un día de descanso"];
        }
        
        // Actualizar el registro
        $updateQuery = "UPDATE dias_trabajo SET monto = ?, estado_entrega = ?, observaciones = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "dssi", $monto, $estadoEntrega, $observaciones, $diaId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            return [true, "Ingreso registrado correctamente"];
        } else {
            return [false, "Error al registrar el ingreso: " . mysqli_error($conn)];
        }
    } else {
        return [false, "El día especificado no existe"];
    }
}

// Función para generar la copia de seguridad
function generarBackup() {
    global $conn;
    
    $tablas = ['vehiculos', 'motoristas', 'rutas', 'dias_trabajo', 'usuarios'];
    $backup = [];
    
    foreach ($tablas as $tabla) {
        $query = "SELECT * FROM $tabla";
        $result = mysqli_query($conn, $query);
        
        $backup[$tabla] = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $backup[$tabla][] = $row;
        }
    }
    
    return json_encode($backup);
}