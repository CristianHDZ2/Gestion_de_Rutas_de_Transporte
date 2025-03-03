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

// La nueva contraseña será "admin123"
$newPassword = "admin123";
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Actualizar la contraseña del administrador
$query = "UPDATE usuarios SET password = ? WHERE usuario = 'admin'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $hashedPassword);

if (mysqli_stmt_execute($stmt)) {
    echo "<h2>¡Contraseña restablecida correctamente!</h2>";
    echo "<p>Usuario: <strong>admin</strong></p>";
    echo "<p>Contraseña: <strong>$newPassword</strong></p>";
    echo "<p><a href='index.php'>Ir a la página de inicio de sesión</a></p>";
} else {
    echo "<h2>Error al restablecer la contraseña</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
}

// Cerrar la conexión
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Por seguridad, eliminar este archivo después de usarlo
echo "<p><strong>IMPORTANTE:</strong> Por seguridad, elimine este archivo después de usarlo.</p>";
?>