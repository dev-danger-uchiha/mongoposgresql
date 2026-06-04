<?php
require __DIR__ . '/config/db.php';

function obtenerListadoClientes() {
    global $pdo, $mongoCollection;

    // 1. Obtener datos desde PostgreSQL
    $clientes = [];
    try {
        $stmt = $pdo->query("SELECT id, nombre, email FROM clientes ORDER BY id DESC");
        $clientes = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Error al consultar PostgreSQL: " . $e->getMessage());
    }

    // Si no hay clientes, no hay nada que buscar en Mongo
    if (empty($clientes)) {
        return [];
    }

    // Extraer los IDs para hacer una sola consulta eficiente a MongoDB usando el operador $in
    $clienteIds = array_column($clientes, 'id');

    // 2. Obtener datos desde MongoDB
    $mapaFotos = [];
    if ($mongoCollection !== null) {
        try {
            $cursor = $mongoCollection->find([
                'cliente_id' => ['$in' => $clienteIds]
            ]);
            
            foreach ($cursor as $doc) {
                // Mapear el ID del cliente con su foto para búsqueda rápida
                $mapaFotos[$doc['cliente_id']] = $doc['ruta_foto'];
            }
        } catch (Exception $e) {
            error_log("Fallo al consultar MongoDB: " . $e->getMessage());
        }
    }

    // 3. Unir lógicamente los datos
    $resultadoFinal = [];
    foreach ($clientes as $cliente) {
        $id = $cliente['id'];
        
        // Si existe la foto en el mapa la asignamos, de lo contrario enviamos un fallback
        $cliente['foto'] = $mapaFotos[$id] ?? 'Sin foto (o servicio de MongoDB no disponible)';
        $resultadoFinal[] = $cliente;
    }

    return $resultadoFinal;
}

// Mostrar resultados formateados
$lista = obtenerListadoClientes();

echo "<h3>Listado de Clientes (Unión PostgreSQL + MongoDB)</h3>";
echo "<pre>";
print_r($lista);
echo "</pre>";
?>