<?php
// Configuración de la base de datos
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sistema_transporte');

// Establecer conexión con la base de datos
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer UTF-8 para la conexión
mysqli_set_charset($conn, "utf8");

// Iniciar sesión si no está iniciada (debe ir al principio del archivo)
if (session_status() == PHP_SESSION_NONE) {
    // Configurar opciones de cookie de sesión más seguras
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true
    ]);
    session_start();
}

// Zona horaria
date_default_timezone_set('America/El_Salvador');

// Función para verificar si el usuario está logueado// Función para verificar si el usuario está logueado
function isLoggedIn() {
    // Verificar existencia y validez de la sesión
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Verificación adicional si es necesario (por ejemplo, verificar en la base de datos)
    // Esto es opcional pero puede ayudar a validar que la sesión sigue siendo válida
    global $conn;
    $userId = $_SESSION['user_id'];
    $query = "SELECT id FROM usuarios WHERE id = ? AND estado = 'Activo' LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    return mysqli_stmt_num_rows($stmt) > 0;
}

// Función para redireccionar si no está logueado
function checkLogin() {
    if (!isLoggedIn()) {
        // Obtener la ruta al directorio raíz
        $rootPath = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));
        if (empty($rootPath)) $rootPath = '/';
        
        header("Location: " . $rootPath . "index.php");
        exit;
    }
}


// Función para verificar permisos de administrador
function isAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'Administrador');
}

// Restringir acceso solo a administradores
function checkAdmin() {
    if (!isAdmin()) {
        header("Location: inicio.php");
        exit;
    }
}