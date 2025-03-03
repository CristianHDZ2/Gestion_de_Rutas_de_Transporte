<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar que el usuario está logueado
checkLogin();

// Generar backup
$backup = generarBackup();

// Establecer encabezados para la descarga
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="backup_transporte_' . date('Y-m-d_H-i-s') . '.json"');
header('Content-Length: ' . strlen($backup));
header('Pragma: no-cache');

// Enviar el backup como descarga
echo $backup;
exit;