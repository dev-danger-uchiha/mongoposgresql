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

$resultado = null;
$esExito = false;
$esAlerta = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $rutaFoto = 'Sin foto'; // Foto por defecto
    
    // Procesar la subida del archivo de imagen (Convertir a Base64)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['foto']['tmp_name'];
        
        // Obtener el tipo de contenido real de la imagen
        $fileType = mime_content_type($tmpName);
        if (!$fileType) {
            $fileType = $_FILES['foto']['type'];
        }
        
        // Leer el contenido del archivo y convertirlo a Base64
        $fileData = file_get_contents($tmpName);
        $base64Data = base64_encode($fileData);
        
        // Formatear como un Data URI válido para HTML/CSS
        $rutaFoto = 'data:' . $fileType . ';base64,' . $base64Data;
    }
    
    if (!empty($nombre) && !empty($email)) {
        $resultado = registrarCliente($nombre, $email, $telefono, $rutaFoto);
        $esExito = strpos($resultado, 'Éxito:') !== false;
        $esAlerta = strpos($resultado, 'Alerta:') !== false;
    } else {
        $resultado = "Error: El nombre y el correo electrónico son obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Cliente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-image: radial-gradient(circle at bottom left, #1e1b4b, #0f172a 40%);
        }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 3.5rem 3rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.05);
            animation: slideUp 0.5s ease-out forwards;
        }
        .card.centered { text-align: center; }
        .icon {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 2px solid rgba(16, 185, 129, 0.2); }
        .icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 2px solid rgba(245, 158, 11, 0.2); }
        .icon.error { background: rgba(239, 68, 68, 0.1); color: var(--error); border: 2px solid rgba(239, 68, 68, 0.2); }
        
        h1 {
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            text-align: center;
        }
        p.subtitle {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }
        p.message {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2.5rem;
            font-size: 1.05rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            color: var(--text-main);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }
        .form-control::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.05rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            box-shadow: none;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: none;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card <?php echo $resultado ? 'centered' : ''; ?>">
        <?php if ($resultado): ?>
            <!-- Response View -->
            <?php if ($esExito): ?>
                <div class="icon success">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <h1>Registro Exitoso</h1>
            <?php elseif ($esAlerta): ?>
                <div class="icon warning">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
                <h1>Aviso de Registro</h1>
            <?php else: ?>
                <div class="icon error">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                </div>
                <h1>Error Inesperado</h1>
            <?php endif; ?>
            
            <p class="message"><?php echo htmlspecialchars($resultado); ?></p>
            
            <div style="display: flex; gap: 1rem;">
                <a href="/registrar.php" class="btn btn-secondary">Registrar Otro</a>
                <a href="/consultar.php" class="btn">Ir al Dashboard</a>
            </div>

        <?php else: ?>
            <!-- Form View -->
            <h1>Nuevo Cliente</h1>
            <p class="subtitle">Añade un registro a PostgreSQL y MongoDB</p>
            
            <form method="POST" action="registrar.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Willman Fernando">
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="Ej: fer@example.com">
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" placeholder="Ej: 3001234567">
                </div>
                
                <div class="form-group">
                    <label for="foto">Foto de Perfil (Opcional)</label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*" style="padding: 0.65rem 1rem;">
                </div>
                
                <div class="form-actions">
                    <a href="/consultar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn">
                        Guardar Cliente
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
