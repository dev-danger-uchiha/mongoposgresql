<?php
require __DIR__ . '/config/db.php';

function registrarCliente($nombre, $email, $rutaFoto) {
    global $pdo, $mongoCollection;
    
    $statusPg = false;
    $statusMongo = false;
    $clienteId = null;

    // 1. Guardar en PostgreSQL (Datos Estructurados)
    try {
        $sql = "INSERT INTO clientes (nombre, email) VALUES (:nombre, :email) RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':email'  => $email
        ]);
        
        $resultado = $stmt->fetch();
        $clienteId = $resultado['id'];
        $statusPg = true;
    } catch (PDOException $e) {
        return "Error Crítico: Falló el registro en PostgreSQL. No se guardó nada. (" . $e->getMessage() . ")";
    }

    // 2. Guardar en MongoDB (Datos No Estructurados) - Solo si PostgreSQL fue exitoso
    if ($statusPg && $mongoCollection !== null) {
        try {
            $documento = [
                'cliente_id' => $clienteId,
                'ruta_foto'  => $rutaFoto,
                'fecha_registro' => new MongoDB\BSON\UTCDateTime()
            ];
            $mongoCollection->insertOne($documento);
            $statusMongo = true;
        } catch (Exception $e) {
            $statusMongo = false;
        }
    }

    // 3. Validación y Mensaje Unificado
    if ($statusPg && $statusMongo) {
        return "Éxito: Cliente '$nombre' guardado en PostgreSQL y foto registrada en MongoDB.";
    } elseif ($statusPg && !$statusMongo) {
        return "Alerta: Cliente '$nombre' guardado en PostgreSQL, pero falló la conexión o el guardado en MongoDB.";
    }

    return "Error desconocido en el proceso de registro.";
}

// Prueba de ejecución
echo registrarCliente("Willman Fernando", "fer@example.com", "/uploads/fotos/fer_profile.jpg");
?>