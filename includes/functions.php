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
    $fechaActual = new DateTime($fechaInicio);
    $fechaRefuerzo = new DateTime($fechaInicioRefuerzo);
    
    // Crear una matriz con la cronología exacta de trabajo
    // La matriz contiene [tipo, contador]
    // Utilizaremos la cronología del documento como base
    $cronologia = [
        // Día 1-2: Trabajo
        ['Trabajo', 0], ['Trabajo', 0],
        // Día 3-4: Descanso
        ['Descanso', 3], ['Descanso', 4],
        // Día 5-7: Trabajo
        ['Trabajo', 0], ['Trabajo', 0], ['Trabajo', 0],
        // Día 8: Refuerzo
        ['Refuerzo', 5],
        // Día 9: Descanso
        ['Descanso', 1],
        // Día 10-11: Trabajo
        ['Trabajo', 0], ['Trabajo', 0],
        // Día 12: Descanso
        ['Descanso', 2],
        // Día 13: No se cuenta (domingo)
        ['Trabajo', 0],
        // Día 14: Descanso
        ['Descanso', 3],
        // Día 15-16: Trabajo
        ['Trabajo', 0], ['Trabajo', 0],
        // Día 17: Descanso
        ['Descanso', 4],
        // Día 18: Refuerzo
        ['Refuerzo', 5],
        // Día 19-20: Trabajo
        ['Trabajo', 0], ['Trabajo', 0],
        // Día 21: Descanso
        ['Descanso', 1],
        // Día 22: Descanso
        ['Descanso', 2],
        // Día 23-24: Trabajo
        ['Trabajo', 0], ['Trabajo', 0],
        // Día 25-26: Descanso
        ['Descanso', 3], ['Descanso', 4],
        // Día 27-28: Trabajo
        ['Trabajo', 0], ['Trabajo', 0],
        // Día 29-30: Descanso
        ['Refuerzo', 5], ['Descanso', 1]
    ];
    
    // Ajustar índice de inicio basado en la fecha de inicio
    $indiceCronologia = 0;
    
    // Preparar la consulta de inserción
    $insertQuery = "INSERT INTO dias_trabajo (ruta_id, fecha, tipo, contador_ciclo) VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);
    
    // Generar calendario para 365 días
    $diasGenerados = 0;
    
    while ($diasGenerados < 365) {
        $fechaStr = $fechaActual->format('Y-m-d');
        $diaSemana = (int)$fechaActual->format('w'); // 0 (domingo) a 6 (sábado)
        
        // Obtener el tipo y contador de la cronología
        $tipo = $cronologia[$indiceCronologia % count($cronologia)][0];
        $contador = $cronologia[$indiceCronologia % count($cronologia)][1];
        
        // Manejar domingos (siempre son trabajo, pero no se cuentan en numeración)
        if ($diaSemana === 0) { // Domingo
            $tipo = 'Trabajo';
            $contador = 0;
            // No avanzamos en la cronología para los domingos
        } else {
            // Avanzar el índice solo para días que no son domingo
            $indiceCronologia++;
        }
        
        // Ajustar refuerzos antes de la fecha de inicio de refuerzo
        if ($fechaActual < $fechaRefuerzo && $tipo === 'Refuerzo') {
            $tipo = 'Trabajo';
            $contador = 0;
        }
        
        // Insertar en la base de datos
        mysqli_stmt_bind_param($insertStmt, "issi", $rutaId, $fechaStr, $tipo, $contador);
        if (!mysqli_stmt_execute($insertStmt)) {
            error_log("Error insertando día: " . mysqli_error($conn));
        }
        
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
    
    // Verificar que el día existe
    $query = "SELECT tipo FROM dias_trabajo WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $diaId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Si es día de descanso, no permitir registrar ingreso
        if ($row['tipo'] == 'Descanso') {
            return [false, "No se puede registrar ingreso en un día de descanso"];
        }
        
        // Para días de trabajo o refuerzo
        if ($monto <= 0 && !empty($observaciones)) {
            // Si solo hay observaciones sin monto
            $updateQuery = "UPDATE dias_trabajo SET monto = NULL, estado_entrega = NULL, observaciones = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "si", $observaciones, $diaId);
        } else {
            // Si hay monto y estado
            $updateQuery = "UPDATE dias_trabajo SET monto = ?, estado_entrega = ?, observaciones = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "dssi", $monto, $estadoEntrega, $observaciones, $diaId);
        }
        
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
?>