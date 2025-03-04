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
    
    // Inicializar la fecha de inicio
    $fechaActual = new DateTime($fechaInicio);
    
    // Convertir fecha de inicio de refuerzo a objeto DateTime
    $fechaRefuerzo = new DateTime($fechaInicioRefuerzo);
    
    // Obtener el día de la semana de la fecha de inicio (1=lunes, ..., 7=domingo)
    $diaSemana = (int)$fechaActual->format('N');
    
    // Definir los grupos de días
    // 1. Martes/Miércoles: días 2, 3
    // 2. Jueves/Viernes: días 4, 5
    // 3. Sábado/Domingo/Lunes: días 6, 7, 1
    
    // Determinar en qué grupo está el día de inicio para establecer el patrón inicial
    $grupoInicial = 0;
    if ($diaSemana == 2 || $diaSemana == 3) {
        $grupoInicial = 1; // Martes o Miércoles
    } else if ($diaSemana == 4 || $diaSemana == 5) {
        $grupoInicial = 2; // Jueves o Viernes
    } else {
        $grupoInicial = 3; // Sábado, Domingo o Lunes
    }
    
    // Establecer el estado inicial para cada grupo
    // El grupo del día inicial siempre es trabajo
    // Dependiendo del grupo inicial, establecemos los estados de los otros grupos
    $estadoGrupos = [
        1 => false, // Martes/Miércoles 
        2 => false, // Jueves/Viernes
        3 => false  // Sábado/Domingo/Lunes
    ];
    
    switch ($grupoInicial) {
        case 1: // Si empezamos en Martes/Miércoles
            $estadoGrupos[1] = true;  // Martes/Miércoles = Trabajo
            $estadoGrupos[2] = false; // Jueves/Viernes = Descanso
            $estadoGrupos[3] = true;  // Sábado/Domingo/Lunes = Trabajo
            break;
        case 2: // Si empezamos en Jueves/Viernes
            $estadoGrupos[1] = true;  // Martes/Miércoles = Trabajo
            $estadoGrupos[2] = true;  // Jueves/Viernes = Trabajo
            $estadoGrupos[3] = false; // Sábado/Domingo/Lunes = Descanso
            break;
        case 3: // Si empezamos en Sábado/Domingo/Lunes
            $estadoGrupos[1] = false; // Martes/Miércoles = Descanso
            $estadoGrupos[2] = true;  // Jueves/Viernes = Trabajo
            $estadoGrupos[3] = true;  // Sábado/Domingo/Lunes = Trabajo
            break;
    }
    
    // Contador para días de descanso consecutivos (excluyendo domingos)
    $contadorDescanso = 0;
    
    // Generar calendario para 2 años (730 días)
    $diasGenerados = 0;
    $semana = 0;
    
    // Variable para controlar si ya pasamos la fecha de inicio del refuerzo
    $refuerzoIniciado = false;
    
    // Preparar la consulta de inserción
    $insertQuery = "INSERT INTO dias_trabajo (ruta_id, fecha, tipo, contador_ciclo) VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);
    
    while ($diasGenerados < 730) {
        $diaSemanaActual = (int)$fechaActual->format('N'); // 1-7 (lunes-domingo)
        $fechaStr = $fechaActual->format('Y-m-d');
        
        // Determinar a qué grupo pertenece el día actual
        $grupoActual = 0;
        if ($diaSemanaActual == 2 || $diaSemanaActual == 3) {
            $grupoActual = 1; // Martes o Miércoles
        } else if ($diaSemanaActual == 4 || $diaSemanaActual == 5) {
            $grupoActual = 2; // Jueves o Viernes
        } else {
            $grupoActual = 3; // Sábado, Domingo o Lunes
        }
        
        // Determinar si es día de trabajo o descanso según el estado del grupo
        $esTrabajo = $estadoGrupos[$grupoActual];
        
        // Caso especial: si es exactamente la fecha de inicio de refuerzo, marcar como refuerzo
        if ($fechaActual->format('Y-m-d') === $fechaRefuerzo->format('Y-m-d')) {
            $tipo = 'Refuerzo';
            $contador = 5;
            $refuerzoIniciado = true; // Iniciar conteo para próximos días de descanso
            $contadorDescanso = 0; // Reiniciar contador para comenzar desde 1 en el siguiente día de descanso
        } 
        else if ($esTrabajo) {
            $tipo = 'Trabajo';
            $contador = 0;
        } 
        else {
            // Es día de descanso
            // Solo incrementamos el contador si ya iniciamos los refuerzos y NO es domingo
            if ($refuerzoIniciado && $diaSemanaActual != 7) { // 7 = domingo
                $contadorDescanso++;
                
                // Si llegamos al 5to día de descanso (excluyendo domingos), es día de refuerzo
                if ($contadorDescanso == 5) {
                    $tipo = 'Refuerzo';
                    $contador = 5;
                    $contadorDescanso = 0; // Reiniciamos el contador
                } else {
                    $tipo = 'Descanso';
                    $contador = $contadorDescanso;
                }
            } else {
                $tipo = 'Descanso';
                $contador = 0; // Antes de la fecha de inicio de refuerzo, no contamos los días
            }
        }
        
        // Insertar en la base de datos
        mysqli_stmt_bind_param($insertStmt, "issi", $rutaId, $fechaStr, $tipo, $contador);
        
        if (!mysqli_stmt_execute($insertStmt)) {
            echo "Error al insertar día " . $fechaStr . ": " . mysqli_error($conn);
            return false;
        }
        
        // Avanzar al siguiente día
        $fechaActual->modify('+1 day');
        $diasGenerados++;
        
        // Si completamos una semana (múltiplo de 7 días), invertimos los estados
        if ($diasGenerados % 7 == 0) {
            $semana++;
            // Cada semana alternamos los estados de todos los grupos
            $estadoGrupos[1] = !$estadoGrupos[1];
            $estadoGrupos[2] = !$estadoGrupos[2];
            $estadoGrupos[3] = !$estadoGrupos[3];
        }
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
function registrarIngreso($diaId, $monto, $estadoEntrega, $observaciones, $combustible = null) {
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
        $updateQuery = "UPDATE dias_trabajo SET monto = ?, estado_entrega = ?, observaciones = ?, combustible = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "dssdi", $monto, $estadoEntrega, $observaciones, $combustible, $diaId);
        
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

// Función para regenerar el calendario de trabajo a partir de una nueva fecha de inicio de refuerzo
function regenerarCalendarioTrabajo($rutaId, $nuevaFechaInicioRefuerzo) {
    global $conn;
    
    // Obtener información de la ruta
    $queryRuta = "SELECT fecha_inicio FROM rutas WHERE id = ?";
    $stmtRuta = mysqli_prepare($conn, $queryRuta);
    mysqli_stmt_bind_param($stmtRuta, "i", $rutaId);
    mysqli_stmt_execute($stmtRuta);
    $resultRuta = mysqli_stmt_get_result($stmtRuta);
    $ruta = mysqli_fetch_assoc($resultRuta);
    
    if (!$ruta) {
        return false;
    }
    
    // Convertir la nueva fecha a objeto DateTime
    $fechaRefuerzo = new DateTime($nuevaFechaInicioRefuerzo);
    $fechaHoy = new DateTime(date('Y-m-d'));
    
    // Si la nueva fecha es menor a la fecha actual, usamos la fecha actual para no modificar días pasados
    if ($fechaRefuerzo < $fechaHoy) {
        $fechaRefuerzo = $fechaHoy;
    }
    
    // Obtenemos todos los días a partir de la fecha de refuerzo
    $queryDias = "SELECT * FROM dias_trabajo WHERE ruta_id = ? AND fecha >= ? ORDER BY fecha ASC";
    $stmtDias = mysqli_prepare($conn, $queryDias);
    $fechaStr = $fechaRefuerzo->format('Y-m-d');
    mysqli_stmt_bind_param($stmtDias, "is", $rutaId, $fechaStr);
    mysqli_stmt_execute($stmtDias);
    $resultDias = mysqli_stmt_get_result($stmtDias);
    
    // Obtener todos los días posteriores a la fecha de corte
    $diasFuturos = [];
    while ($row = mysqli_fetch_assoc($resultDias)) {
        $diasFuturos[] = $row;
    }
    
    // El cambio clave - No eliminar todos los días futuros, sino simplemente actualizar el patrón de refuerzo
    // manteniendo intactos los días de trabajo y descanso
    
    // Primero, obtener el contador de descanso actual
    $queryContador = "SELECT MAX(contador_ciclo) as max_contador 
                     FROM dias_trabajo 
                     WHERE ruta_id = ? AND tipo = 'Descanso' AND fecha < ?";
    $stmtContador = mysqli_prepare($conn, $queryContador);
    mysqli_stmt_bind_param($stmtContador, "is", $rutaId, $fechaStr);
    mysqli_stmt_execute($stmtContador);
    $resultContador = mysqli_stmt_get_result($stmtContador);
    $rowContador = mysqli_fetch_assoc($resultContador);
    $contadorDescanso = ($rowContador['max_contador']) ? $rowContador['max_contador'] : 0;
    
    // Iniciar una transacción
    mysqli_begin_transaction($conn);
    
    try {
        // Para cada día futuro, actualizar solo el contador y tipo (si es refuerzo)
        foreach ($diasFuturos as $dia) {
            $diaFecha = new DateTime($dia['fecha']);
            $diaSemana = (int)$diaFecha->format('N'); // 1-7 (lunes-domingo)
            
            // Si el día actual es exactamente la fecha de inicio de refuerzo, marcarlo como refuerzo
            if ($diaFecha->format('Y-m-d') === $fechaRefuerzo->format('Y-m-d')) {
                $tipo = 'Refuerzo';
                $contador = 5;
                $contadorDescanso = 0; // Reiniciar contador para próximos días
                
                // Actualizar este día específico
                $updateQuery = "UPDATE dias_trabajo SET tipo = ?, contador_ciclo = ? WHERE id = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, "sii", $tipo, $contador, $dia['id']);
                
                if (!mysqli_stmt_execute($updateStmt)) {
                    throw new Exception("Error al actualizar día de refuerzo: " . mysqli_error($conn));
                }
            } 
            // Si es un día de trabajo, mantenerlo igual
            elseif ($dia['tipo'] == 'Trabajo') {
                continue; // No hacemos cambios
            }
            // Si es un día de descanso o ya era refuerzo, recalcular su contador
            else {
                // Ignorar domingos en el conteo
                if ($diaSemana != 7) { // 7 = domingo
                    // Si era refuerzo y ahora debe ser descanso
                    if ($dia['tipo'] == 'Refuerzo' && $diaFecha->format('Y-m-d') !== $fechaRefuerzo->format('Y-m-d')) {
                        $contadorDescanso++; // Incrementar para que sea el siguiente descanso
                        $tipo = 'Descanso';
                        $contador = $contadorDescanso;
                    } 
                    // Si era descanso y debe seguir siéndolo
                    elseif ($dia['tipo'] == 'Descanso') {
                        $contadorDescanso++;
                        
                        // Si llegamos al 5to día de descanso y no es el nuevo día de refuerzo, ponerlo como refuerzo
                        if ($contadorDescanso == 5 && $diaFecha->format('Y-m-d') !== $fechaRefuerzo->format('Y-m-d')) {
                            $tipo = 'Refuerzo';
                            $contador = 5;
                            $contadorDescanso = 0; // Reiniciar contador para próximos días
                        } else {
                            $tipo = 'Descanso';
                            $contador = $contadorDescanso;
                        }
                    }
                    
                    // Actualizar este día si ha cambiado
                    if ($tipo != $dia['tipo'] || $contador != $dia['contador_ciclo']) {
                        $updateQuery = "UPDATE dias_trabajo SET tipo = ?, contador_ciclo = ? WHERE id = ?";
                        $updateStmt = mysqli_prepare($conn, $updateQuery);
                        mysqli_stmt_bind_param($updateStmt, "sii", $tipo, $contador, $dia['id']);
                        
                        if (!mysqli_stmt_execute($updateStmt)) {
                            throw new Exception("Error al actualizar día: " . mysqli_error($conn));
                        }
                    }
                }
            }
        }
        
        // Confirmar los cambios
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        mysqli_rollback($conn);
        return false;
    }
}

// Función auxiliar para determinar el estado actual de los grupos
function determinarEstadoGrupos($rutaId, $fechaCorte) {
    global $conn;
    
    // Por defecto, inicializamos con un patrón estándar
    $estadoGrupos = [
        1 => true,  // Martes/Miércoles = Trabajo por defecto
        2 => false, // Jueves/Viernes = Descanso por defecto
        3 => true   // Sábado/Domingo/Lunes = Trabajo por defecto
    ];
    
    // Obtener los últimos 7 días antes de la fecha de corte para determinar el patrón
    $query = "SELECT fecha, tipo FROM dias_trabajo 
              WHERE ruta_id = ? AND fecha < ? 
              ORDER BY fecha DESC LIMIT 7";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $rutaId, $fechaCorte);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $dias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dias[] = $row;
    }
    
    // Si no hay días previos, usamos el patrón por defecto
    if (count($dias) == 0) {
        return $estadoGrupos;
    }
    
    // Analizar los días previos para determinar el patrón
    foreach ($dias as $dia) {
        $fecha = new DateTime($dia['fecha']);
        $diaSemana = (int)$fecha->format('N'); // 1-7 (lunes-domingo)
        
        // Determinar a qué grupo pertenece
        $grupo = 0;
        if ($diaSemana == 2 || $diaSemana == 3) {
            $grupo = 1; // Martes o Miércoles
        } else if ($diaSemana == 4 || $diaSemana == 5) {
            $grupo = 2; // Jueves o Viernes
        } else {
            $grupo = 3; // Sábado, Domingo o Lunes
        }
        
        // Actualizar el estado del grupo según el tipo de día
        if ($dia['tipo'] == 'Trabajo' || $dia['tipo'] == 'Refuerzo') {
            $estadoGrupos[$grupo] = true;
        } else {
            $estadoGrupos[$grupo] = false;
        }
    }
    
    // Invertir los estados para el siguiente ciclo
    // (ya que estamos a punto de cambiar de semana al pasar la fecha de corte)
    $estadoGrupos[1] = !$estadoGrupos[1];
    $estadoGrupos[2] = !$estadoGrupos[2];
    $estadoGrupos[3] = !$estadoGrupos[3];
    
    return $estadoGrupos;
}