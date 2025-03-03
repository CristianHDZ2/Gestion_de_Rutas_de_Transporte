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
    
    // Usar la cronología exacta del documento CRONOLOGIA DE TRABAJO
    // Para el mes de marzo 2025
    // Formato: [día, mes, año, tipo, contador]
    $cronologiaBase = [
        [1, 3, 2025, 'Refuerzo', 5], // Sábado 1: Refuerzo (Descanso) (5)
        [2, 3, 2025, 'Descanso', 0], // Domingo 2: Descanso (no se cuenta)
        [3, 3, 2025, 'Descanso', 2], // Lunes 3: Descanso (2)
        [4, 3, 2025, 'Trabajo', 0],  // Martes 4: Trabajo
        [5, 3, 2025, 'Trabajo', 0],  // Miércoles 5: Trabajo
        [6, 3, 2025, 'Descanso', 3], // Jueves 6: Descanso (3)
        [7, 3, 2025, 'Descanso', 4], // Viernes 7: Descanso (4)
        [8, 3, 2025, 'Trabajo', 0],  // Sábado 8: Trabajo
        [9, 3, 2025, 'Trabajo', 0],  // Domingo 9: Trabajo (no se cuenta)
        [10, 3, 2025, 'Trabajo', 0], // Lunes 10: Trabajo
        [11, 3, 2025, 'Refuerzo', 5], // Martes 11: Refuerzo (Descanso) (5)
        [12, 3, 2025, 'Descanso', 1], // Miércoles 12: Descanso (1)
        [13, 3, 2025, 'Trabajo', 0], // Jueves 13: Trabajo
        [14, 3, 2025, 'Trabajo', 0], // Viernes 14: Trabajo
        [15, 3, 2025, 'Descanso', 2], // Sábado 15: Descanso (2)
        [16, 3, 2025, 'Descanso', 0], // Domingo 16: Descanso (no se cuenta)
        [17, 3, 2025, 'Descanso', 3], // Lunes 17: Descanso (3)
        [18, 3, 2025, 'Trabajo', 0], // Martes 18: Trabajo
        [19, 3, 2025, 'Trabajo', 0], // Miércoles 19: Trabajo
        [20, 3, 2025, 'Descanso', 4], // Jueves 20: Descanso (4)
        [21, 3, 2025, 'Refuerzo', 5], // Viernes 21: Refuerzo (Descanso) (5)
        [22, 3, 2025, 'Trabajo', 0], // Sábado 22: Trabajo
        [23, 3, 2025, 'Trabajo', 0], // Domingo 23: Trabajo (no se cuenta)
        [24, 3, 2025, 'Trabajo', 0], // Lunes 24: Trabajo
        [25, 3, 2025, 'Descanso', 1], // Martes 25: Descanso (1)
        [26, 3, 2025, 'Descanso', 2], // Miércoles 26: Descanso (2)
        [27, 3, 2025, 'Trabajo', 0], // Jueves 27: Trabajo
        [28, 3, 2025, 'Trabajo', 0], // Viernes 28: Trabajo
        [29, 3, 2025, 'Descanso', 3], // Sábado 29: Descanso (3)
        [30, 3, 2025, 'Descanso', 0], // Domingo 30: Descanso (no se cuenta)
        [31, 3, 2025, 'Descanso', 4], // Lunes 31: Descanso (4)
        // Abril 2025
        [1, 4, 2025, 'Refuerzo', 5], // Martes 1: Refuerzo (Descanso) (5)
        [2, 4, 2025, 'Descanso', 1], // Miércoles 2: Descanso (1)
        [3, 4, 2025, 'Trabajo', 0], // Jueves 3: Trabajo
        [4, 4, 2025, 'Trabajo', 0], // Viernes 4: Trabajo
        [5, 4, 2025, 'Descanso', 2], // Sábado 5: Descanso (2)
        [6, 4, 2025, 'Descanso', 0], // Domingo 6: Descanso (no se cuenta)
        [7, 4, 2025, 'Descanso', 3], // Lunes 7: Descanso (3)
        [8, 4, 2025, 'Trabajo', 0], // Martes 8: Trabajo
        [9, 4, 2025, 'Trabajo', 0], // Miércoles 9: Trabajo
        [10, 4, 2025, 'Descanso', 4], // Jueves 10: Descanso (4)
        [11, 4, 2025, 'Refuerzo', 5], // Viernes 11: Refuerzo (Descanso) (5)
        [12, 4, 2025, 'Trabajo', 0], // Sábado 12: Trabajo
        [13, 4, 2025, 'Descanso', 0], // Domingo 13: Descanso (no se cuenta)
    ];
    
    // Fecha de inicio de la cronología base (1 de marzo de 2025)
    $fechaBaseCronologia = new DateTime('2025-03-01');
    
    // Fecha de inicio para esta ruta específica
    $fechaInicioRuta = new DateTime($fechaInicio);
    
    // Calcular la diferencia en días entre la fecha base y la fecha de inicio
    $diferenciaDias = $fechaInicioRuta->diff($fechaBaseCronologia)->days;
    
    // Si la fecha de inicio es anterior a la fecha base, ajustar
    if ($fechaInicioRuta < $fechaBaseCronologia) {
        $diferenciaDias = -$diferenciaDias;
    }
    
    // Ajustar el índice de inicio en la cronología base
    $indiceInicio = $diferenciaDias % count($cronologiaBase);
    if ($indiceInicio < 0) {
        $indiceInicio += count($cronologiaBase);
    }
    
    // Preparar la consulta de inserción
    $insertQuery = "INSERT INTO dias_trabajo (ruta_id, fecha, tipo, contador_ciclo) VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);
    
    // Fecha actual para la generación del calendario
    $fechaActual = clone $fechaInicioRuta;
    
    // Fecha de inicio de refuerzo
    $fechaRefuerzo = new DateTime($fechaInicioRefuerzo);
    
    // Generar calendario para 365 días
    $diasGenerados = 0;
    
    while ($diasGenerados < 365) {
        // Obtener el índice correspondiente en la cronología base
        $indiceActual = ($indiceInicio + $diasGenerados) % count($cronologiaBase);
        
        // Obtener el tipo y contador de la cronología
        $tipo = $cronologiaBase[$indiceActual][3];
        $contador = $cronologiaBase[$indiceActual][4];
        
        // Manejar domingos (no se cuenta en la numeración)
        $diaSemana = (int)$fechaActual->format('w'); // 0 (domingo) a 6 (sábado)
        if ($diaSemana === 0) { // Domingo
            $contador = 0; // Los domingos no se cuentan en la numeración
        }
        
        // Ajustar refuerzos antes de la fecha de inicio de refuerzo
        if ($fechaActual < $fechaRefuerzo && $tipo === 'Refuerzo') {
            $tipo = 'Trabajo';
            $contador = 0;
        }
        
        $fechaStr = $fechaActual->format('Y-m-d');
        
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
        if (empty($monto) && !empty($observaciones)) {
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