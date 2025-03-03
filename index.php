<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Verificar si ya está logueado
if (isLoggedIn()) {
    header("Location: inicio.php");
    exit;
}

// Procesar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Procesar formulario de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $error = "";
    
    // Verificar credenciales
    if (empty($username) || empty($password)) {
        $error = "Por favor ingrese usuario y contraseña";
    } else {
        // Buscar usuario en la base de datos
        $query = "SELECT id, nombre, apellido, usuario, password, rol FROM usuarios WHERE usuario = ? AND estado = 'Activo'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Verificar password
            if (password_verify($password, $row['password'])) {
                // Iniciar sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['nombre'] . ' ' . $row['apellido'];
                $_SESSION['user_role'] = $row['rol'];
                
                // Redireccionar al dashboard
                header("Location: inicio.php");
                exit;
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado o inactivo";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Control de Transporte</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="login-logo">
            <i class="bi bi-bus-front"></i>
        </div>
        <h2 class="text-center mb-4">Sistema de Control de Transporte</h2>
        
        <?php if (isset($error) && !empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>
            </div>
        </form>
        <p class="text-center mt-3 text-muted">Sistema de Control de Ingresos para Transporte de Pasajeros</p>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>