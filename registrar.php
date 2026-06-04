<?php
require __DIR__ . '/config/db.php';

function registrarCliente($nombre, $email, $telefono, $rutaFoto) {
    global $pdo, $mongoCollection;
    
    $statusPg = false;
    $statusMongo = false;
    $clienteId = null;

    // 1. Guardar en PostgreSQL
    try {
        $sql = "INSERT INTO clientes (nombre, email, telefono) VALUES (:nombre, :email, :telefono) RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':email'    => $email,
            ':telefono' => $telefono
        ]);
        
        $resultado = $stmt->fetch();
        $clienteId = $resultado['id'];
        $statusPg = true;
    } catch (PDOException $e) {
        // Capturar específicamente el error de Email Duplicado (Código 23505)
        if ($e->getCode() == '23505') {
            return "Alerta: El email '$email' ya existe en PostgreSQL. No se guardó nada.";
        }
        return "Error Crítico: Falló el registro en PostgreSQL. (" . $e->getMessage() . ")";
    }

    // 2. Guardar en MongoDB (Solo si PostgreSQL fue exitoso)
    if ($statusPg && $mongoCollection !== null) {
        try {
            $documento = [
                'cliente_id' => $clienteId,
                'ruta_foto'  => $rutaFoto,
                'fecha_registro_mongo' => new MongoDB\BSON\UTCDateTime()
            ];
            $mongoCollection->insertOne($documento);
            $statusMongo = true;
        } catch (Exception $e) {
            $statusMongo = false;
        }
    }

    // 3. Validación y Mensaje Unificado
    if ($statusPg && $statusMongo) {
        return "Éxito: Cliente '$nombre' guardado en PostgreSQL y foto en MongoDB.";
    } elseif ($statusPg && !$statusMongo) {
        return "Alerta: Cliente '$nombre' guardado en PostgreSQL, pero falló la conexión a MongoDB.";
    }

    return "Error desconocido en el proceso.";
}

// Prueba de ejecución actualizada
echo registrarCliente("Willman Fernando", "fer@example.com", "3001234567", "/uploads/fotos/fer_profile.jpg");
?>
