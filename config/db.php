<?php
require __DIR__ . '/../vendor/autoload.php';

// ==========================================
// 1. Conexión a PostgreSQL 
// ==========================================
$pgHost = getenv('PG_HOST');
$pgDb   = getenv('PG_DB');
$pgUser = getenv('PG_USER');
$pgPass = getenv('PG_PASS');

try {
    $dsn = "pgsql:host=$pgHost;dbname=$pgDb";
    $pdo = new PDO($dsn, $pgUser, $pgPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error crítico: No se pudo conectar a PostgreSQL."); // Ocultar detalles del error en producción
}

// ==========================================
// 2. Conexión a MongoDB Atlas
// ==========================================
$mongoUri = getenv('MONGO_URI');
$mongoClient = null;
$mongoCollection = null;

try {
    $mongoClient = new MongoDB\Client($mongoUri);
    // Usamos variables de entorno también para el nombre de la DB
    $mongoDbName = getenv('MONGO_DB_NAME') ?: 'mi_base_datos'; 
    $mongoCollection = $mongoClient->$mongoDbName->fotos_clientes;
    
    $mongoClient->selectDatabase('admin')->command(['ping' => 1]); 
} catch (Exception $e) {
    $mongoCollection = null; 
    error_log("Advertencia: No se pudo conectar a MongoDB Atlas.");
}
?>
