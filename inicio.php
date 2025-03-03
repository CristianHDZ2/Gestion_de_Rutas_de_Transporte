<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';

// Consultar estadísticas
// Total de rutas
$queryRutas = "SELECT COUNT(*) as total FROM rutas WHERE estado = 'Activa'";
$resultRutas = mysqli_query($conn, $queryRutas);
$totalRutas = mysqli_fetch_assoc($resultRutas)['total'];

// Total de vehículos
$queryVehiculos = "SELECT COUNT(*) as total FROM vehiculos WHERE estado = 'Activo'";
$resultVehiculos = mysqli_query($conn, $queryVehiculos);
$totalVehiculos = mysqli_fetch_assoc($resultVehiculos)['total'];

// Total de motoristas
$queryMotoristas = "SELECT COUNT(*) as total FROM motoristas WHERE estado = 'Activo'";
$resultMotoristas = mysqli_query($conn, $queryMotoristas);
$totalMotoristas = mysqli_fetch_assoc($resultMotoristas)['total'];

// Ingresos totales del mes actual
$primerDiaMes = date('Y-m-01');
$ultimoDiaMes = date('Y-m-t');
$queryIngresos = "SELECT SUM(monto) as total FROM dias_trabajo 
                 WHERE fecha BETWEEN ? AND ? AND estado_entrega = 'Recibido'";
$stmtIngresos = mysqli_prepare($conn, $queryIngresos);
mysqli_stmt_bind_param($stmtIngresos, "ss", $primerDiaMes, $ultimoDiaMes);
mysqli_stmt_execute($stmtIngresos);
$resultIngresos = mysqli_stmt_get_result($stmtIngresos);
$totalIngresos = mysqli_fetch_assoc($resultIngresos)['total'] ?? 0;

// Montos pendientes
$queryPendientes = "SELECT SUM(monto) as total FROM dias_trabajo 
                   WHERE estado_entrega = 'Pendiente'";
$resultPendientes = mysqli_query($conn, $queryPendientes);
$totalPendientes = mysqli_fetch_assoc($resultPendientes)['total'] ?? 0;

// Próximos días de trabajo
$hoy = date('Y-m-d');
$proximaSemana = date('Y-m-d', strtotime('+7 days'));
$queryProximo = "SELECT dt.fecha, dt.tipo, r.numero as ruta, v.placa, CONCAT(m.nombre, ' ', m.apellido) as motorista
                FROM dias_trabajo dt
                JOIN rutas r ON dt.ruta_id = r.id
                JOIN vehiculos v ON r.vehiculo_id = v.id
                JOIN motoristas m ON r.motorista_id = m.id
                WHERE dt.fecha BETWEEN ? AND ? AND dt.tipo IN ('Trabajo', 'Refuerzo')
                ORDER BY dt.fecha ASC
                LIMIT 5";
$stmtProximo = mysqli_prepare($conn, $queryProximo);
mysqli_stmt_bind_param($stmtProximo, "ss", $hoy, $proximaSemana);
mysqli_stmt_execute($stmtProximo);
$resultProximo = mysqli_stmt_get_result($stmtProximo);

// Últimos ingresos registrados
$queryUltimos = "SELECT dt.fecha, dt.monto, dt.estado_entrega, r.numero as ruta
                FROM dias_trabajo dt
                JOIN rutas r ON dt.ruta_id = r.id
                WHERE dt.monto IS NOT NULL
                ORDER BY dt.fecha DESC
                LIMIT 5";
$resultUltimos = mysqli_query($conn, $queryUltimos);
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-speedometer2"></i> Panel de Control</h1>
    </div>
</div>

<!-- Tarjetas de resumen -->
<div class="row">
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon mb-3">
                    <i class="bi bi-signpost-2"></i>
                </div>
                <h5 class="card-title">Rutas</h5>
                <h2 class="card-text"><?php echo $totalRutas; ?></h2>
                <a href="rutas/lista_rutas.php" class="btn btn-sm btn-primary">Ver rutas</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon mb-3">
                    <i class="bi bi-truck"></i>
                </div>
                <h5 class="card-title">Vehículos</h5>
                <h2 class="card-text"><?php echo $totalVehiculos; ?></h2>
                <a href="vehiculos/lista_vehiculos.php" class="btn btn-sm btn-primary">Ver vehículos</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon mb-3">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h5 class="card-title">Motoristas</h5>
                <h2 class="card-text"><?php echo $totalMotoristas; ?></h2>
                <a href="motoristas/lista_motoristas.php" class="btn btn-sm btn-primary">Ver motoristas</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon mb-3">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h5 class="card-title">Ingresos del Mes</h5>
                <h2 class="card-text">$<?php echo number_format($totalIngresos, 2); ?></h2>
                <a href="reportes/dias_recibidos.php" class="btn btn-sm btn-primary">Ver reporte</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Próximos días de trabajo -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-check"></i> Próximos días de trabajo
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Ruta</th>
                                <th>Vehículo</th>
                                <th>Motorista</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($resultProximo)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                <td>
                                    <?php if ($row['tipo'] == 'Trabajo'): ?>
                                        <span class="badge bg-success">Trabajo</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Refuerzo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['ruta']; ?></td>
                                <td><?php echo $row['placa']; ?></td>
                                <td><?php echo $row['motorista']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (mysqli_num_rows($resultProximo) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center">No hay próximos días de trabajo</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <a href="ingresos/calendario.php" class="btn btn-sm btn-outline-primary">Ver calendario completo</a>
            </div>
        </div>
    </div>
    
    <!-- Últimos ingresos registrados -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-journal-check"></i> Últimos ingresos registrados
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Ruta</th>
                                <th>Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($resultUltimos)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                <td><?php echo $row['ruta']; ?></td>
                                <td>$<?php echo number_format($row['monto'], 2); ?></td>
                                <td>
                                    <?php if ($row['estado_entrega'] == 'Recibido'): ?>
                                        <span class="badge bg-success">Recibido</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (mysqli_num_rows($resultUltimos) == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay ingresos registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="ingresos/registrar_ingreso.php" class="btn btn-sm btn-outline-primary">Registrar ingreso</a>
                    <div class="alert alert-warning py-2 px-3 mb-0">
                        <strong>Pendientes:</strong> $<?php echo number_format($totalPendientes, 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>