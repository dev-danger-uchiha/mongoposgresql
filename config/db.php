<?php
require __DIR__ . '/../vendor/autoload.php';

// ==========================================
// 1. Conexión a PostgreSQL (PDO Estricto)
// ==========================================
$pgHost = '127.0.0.1';
$pgDb   = 'mi_base_datos';
$pgUser = 'postgres';
$pgPass = 'tu_password';

try {
    $dsn = "pgsql:host=$pgHost;dbname=$pgDb";
    $pdo = new PDO($dsn, $pgUser, $pgPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Manejo estricto de errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error crítico: No se pudo conectar a PostgreSQL. Detalles: " . $e->getMessage());
}

// ==========================================
// 2. Conexión a MongoDB
// ==========================================
$mongoUri = "mongodb://127.0.0.1:27017";
$mongoClient = null;
$mongoCollection = null;

try {
    $mongoClient = new MongoDB\Client($mongoUri);
    // Seleccionamos base de datos y colección
    $mongoCollection = $mongoClient->mi_base_datos->fotos_clientes;
    
    // Provocar un error si el servidor de Mongo está apagado haciendo un ping rápido
    $mongoClient->selectDatabase('admin')->command(['ping' => 1]); 
} catch (Exception $e) {
    // No detenemos la ejecución, permitimos que la app maneje la falla más adelante
    $mongoCollection = null; 
    error_log("Advertencia: No se pudo conectar a MongoDB. " . $e->getMessage());
}
?>