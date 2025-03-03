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
    
    // Patrón de cronología basado en el documento "CRONOLOGIA DE TRABAJO.docx"
    // Definimos el patrón completo para 10 días que se repetirá (excluyendo domingos)
    // T = Trabajo, D1-D4 = Descanso (con contador), R5 = Refuerzo (Descanso 5)
    $patron = [
        'T' => ['Trabajo', 0],
        'D1' => ['Descanso', 1],
        'D2' => ['Descanso', 2],
        'D3' => ['Descanso', 3],
        'D4' => ['Descanso', 4],
        'R5' => ['Refuerzo', 5]
    ];
    
    // Secuencia de días según la cronología (excluyendo domingos)
    $secuencia = [
        'T', 'T', 'D1', 'D2', 'D3', 'D4', 'R5', 'D1', 'T', 'T',   // Primeros 10 días
        'D1', 'D2', 'D3', 'D4', 'R5', 'T', 'T', 'D1', 'D2', 'D3'  // Siguientes 10 días 
    ];
    
    // Generamos la secuencia completa repitiendo el patrón
    $secuenciaCompleta = [];
    for ($i = 0; $i < 30; $i++) { // Repetimos el patrón para cubrir los 365 días
        $secuenciaCompleta = array_merge($secuenciaCompleta, $secuencia);
    }
    
    // Encontrar el día de la semana de inicio para saber dónde comenzar en la secuencia
    $diaSemanaInicio = (int)$fechaActual->format('w'); // 0 (domingo) a 6 (sábado)
    $posicionSecuencia = 0; // Comenzamos desde el principio de la secuencia
    
    // Preparar la consulta de inserción
    $insertQuery = "INSERT INTO dias_trabajo (ruta_id, fecha, tipo, contador_ciclo) VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);
    
    // Generar calendario para 365 días
    $diasGenerados = 0;
    
    while ($diasGenerados < 365) {
        $fechaStr = $fechaActual->format('Y-m-d');
        $diaSemana = (int)$fechaActual->format('w'); // 0 (domingo) a 6 (sábado)
        
        // Para domingos, seguimos la misma cronología pero no contamos en la numeración
        if ($diaSemana === 0) {
            // Obtenemos el tipo del día anterior
            $diaAnterior = (clone $fechaActual)->modify('-1 day');
            $diaSemanaAnterior = (int)$diaAnterior->format('w');
            
            // Si el día anterior era sábado, usamos su mismo tipo (sin incrementar secuencia)
            if ($diaSemanaAnterior === 6) {
                $codigoActual = $secuenciaCompleta[$posicionSecuencia];
                $tipo = $patron[$codigoActual][0];
                $contador = 0; // Los domingos no se cuentan en la numeración
            } else {
                // Si no, seguimos la secuencia normal
                $codigoActual = $secuenciaCompleta[$posicionSecuencia];
                $tipo = $patron[$codigoActual][0];
                $contador = 0; // Los domingos no se cuentan en la numeración
            }
        } else {
            // Días normales (no domingos)
            $codigoActual = $secuenciaCompleta[$posicionSecuencia];
            $tipo = $patron[$codigoActual][0];
            $contador = $patron[$codigoActual][1];
            
            // Avanzar en la secuencia solo para días que no son domingo
            $posicionSecuencia = ($posicionSecuencia + 1) % count($secuenciaCompleta);
        }
        
        // Si estamos antes de la fecha de inicio de refuerzo y el tipo es refuerzo, cambiamos a trabajo
        $fechaRefuerzo = new DateTime($fechaInicioRefuerzo);
        if ($fechaActual < $fechaRefuerzo && $tipo === 'Refuerzo') {
            $tipo = 'Trabajo';
            $contador = 0;
        }
        
        // Insertar en la base de datos
        mysqli_stmt_bind_param($insertStmt, "issi", $rutaId, $fechaStr, $tipo, $contador);
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
        
        // Para días de trabajo o refuerzo, podemos registrar ingreso o solo observaciones
        if ($monto <= 0 && !empty($observaciones)) {
            // Si no hay monto pero hay observaciones, solo registramos observaciones
            $updateQuery = "UPDATE dias_trabajo SET observaciones = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "si", $observaciones, $diaId);
            
            if (mysqli_stmt_execute($updateStmt)) {
                return [true, "Observación registrada correctamente"];
            } else {
                return [false, "Error al registrar la observación: " . mysqli_error($conn)];
            }
        } else {
            // Actualizar el registro con monto, estado y observaciones
            $updateQuery = "UPDATE dias_trabajo SET monto = ?, estado_entrega = ?, observaciones = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "dssi", $monto, $estadoEntrega, $observaciones, $diaId);
            
            if (mysqli_stmt_execute($updateStmt)) {
                return [true, "Ingreso registrado correctamente"];
            } else {
                return [false, "Error al registrar el ingreso: " . mysqli_error($conn)];
            }
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