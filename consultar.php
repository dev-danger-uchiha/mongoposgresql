<?php
require __DIR__ . '/config/db.php';

function obtenerListadoClientes() {
    global $pdo, $mongoCollection;

    // 1. Obtener datos desde PostgreSQL
    $clientes = [];
    try {
        // Añadimos las nuevas columnas a la consulta
        $stmt = $pdo->query("SELECT id, nombre, email, telefono, creado_en FROM clientes ORDER BY id DESC");
        $clientes = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Error al consultar PostgreSQL: " . $e->getMessage());
    }

    if (empty($clientes)) {
        return [];
    }

    $clienteIds = array_column($clientes, 'id');

    // 2. Obtener datos desde MongoDB
    $mapaFotos = [];
    if ($mongoCollection !== null) {
        try {
            $cursor = $mongoCollection->find([
                'cliente_id' => ['$in' => $clienteIds]
            ]);
            
            foreach ($cursor as $doc) {
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
        $cliente['foto'] = $mapaFotos[$id] ?? 'Sin foto (o MongoDB no disponible)';
        $resultadoFinal[] = $cliente;
    }

    return $resultadoFinal;
}

// Mostrar resultados
$lista = obtenerListadoClientes();
echo "<h3>Listado de Clientes (Unión PostgreSQL + MongoDB)</h3>";
echo "<pre>";
print_r($lista);
echo "</pre>";
?>
