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

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('America/El_Salvador');

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redireccionar si no está logueado
function checkLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
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
        header("Location: dashboard.php");
        exit;
    }
}