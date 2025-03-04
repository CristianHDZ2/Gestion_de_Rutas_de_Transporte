<?php
// Iniciar la sesión
session_start();

// Forzar destrucción completa de la sesión
session_unset();
session_destroy();

// Borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Forzar redirección a login usando JavaScript para evitar caché
echo '<!DOCTYPE html>
<html>
<head>
    <title>Cerrando sesión...</title>
    <script>
        // Limpiar caché de navegador
        if (window.localStorage) localStorage.clear();
        if (window.sessionStorage) sessionStorage.clear();
        
        // Eliminar todas las cookies
        document.cookie.split(";").forEach(function(c) {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });
        
        // Redireccionar directamente a index.php
        window.location.replace("index.php");
    </script>
</head>
<body>
    <p>Cerrando sesión, por favor espere...</p>
    <p>Si no es redirigido automáticamente, <a href="index.php">haga clic aquí</a>.</p>
</body>
</html>';
exit;
?>