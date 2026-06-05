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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Clientes - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem 2rem;
            background-image: radial-gradient(circle at top right, #1e1b4b, #0f172a 40%);
        }
        .header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.6s ease-out;
        }
        .header h1 {
            font-size: 2.75rem;
            font-weight: 700;
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }
        .header p {
            color: var(--text-muted);
            font-size: 1.15rem;
        }
        .table-container {
            width: 100%;
            max-width: 1000px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.05);
            animation: fadeInUp 0.6s ease-out forwards;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: rgba(15, 23, 42, 0.6);
            color: #cbd5e1;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr {
            transition: background 0.2s ease;
        }
        tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            background-color: rgba(59, 130, 246, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
            color: #60a5fa;
            border: 2px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2.5rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3), 0 2px 4px -1px rgba(59, 130, 246, 0.2);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4), 0 4px 6px -2px rgba(59, 130, 246, 0.2);
        }
        .badge {
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .badge-success {
            background: rgba(52, 211, 153, 0.1);
            color: #34d399;
            border-color: rgba(52, 211, 153, 0.2);
        }
        .email-text {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            width: 90%;
            max-width: 500px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: scale(0.95) translateY(20px);
            transition: all 0.3s ease;
            position: relative;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }
        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-close:hover {
            color: white;
        }
        .modal-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .modal-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            border: 3px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            background-color: rgba(59, 130, 246, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #60a5fa;
        }
        .modal-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: white;
        }
        .modal-info p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .modal-body {
            display: grid;
            gap: 1.25rem;
        }
        .detail-item {
            background: rgba(15, 23, 42, 0.4);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-size: 1.05rem;
            color: #e2e8f0;
            font-weight: 500;
        }
        .connection-status {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        .status-badge {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.connected {
            background: rgba(52, 211, 153, 0.1);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.2);
        }
        .status-badge.disconnected {
            background: rgba(248, 113, 113, 0.1);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.2);
        }
        tr { cursor: pointer; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Directorio de Clientes</h1>
        <p>Unión en tiempo real de PostgreSQL y MongoDB Atlas</p>
    </div>

    <div class="table-container">
        <?php if (empty($lista)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1.5rem; opacity: 0.3; color: #94a3b8;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <h2>No hay clientes registrados</h2>
                <p style="margin-top: 0.5rem; color: #64748b;">Agrega algunos clientes para verlos aparecer aquí.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Perfil</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista as $cliente): ?>
                        <tr onclick="openModal(this)" data-cliente='<?php echo htmlspecialchars(json_encode($cliente), ENT_QUOTES, 'UTF-8'); ?>'>
                            <td><span class="badge">#<?php echo htmlspecialchars($cliente['id']); ?></span></td>
                            <td>
                                <?php if (strpos($cliente['foto'], 'Sin foto') === false && $cliente['foto'] !== ''): ?>
                                    <div class="avatar" style="background-image: url('<?php echo htmlspecialchars(ltrim($cliente['foto'], '/')); ?>');"></div>
                                <?php else: ?>
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 500; font-size: 1.05rem;"><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                            <td class="email-text"><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td style="color: #cbd5e1;"><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                            <td><span class="badge badge-success">Activo</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <a href="/registrar.php" class="btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Registrar Cliente de Prueba
    </a>

    <!-- Modal Container -->
    <div class="modal-overlay" id="clientModal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            
            <div class="modal-header">
                <div id="modalAvatar" class="modal-avatar"></div>
                <div class="modal-info">
                    <h2 id="modalName">Nombre del Cliente</h2>
                    <p id="modalEmail">correo@ejemplo.com</p>
                </div>
            </div>

            <div class="modal-body">
                <div class="detail-item">
                    <div class="detail-label">ID del Cliente</div>
                    <div class="detail-value" id="modalId">#0</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Teléfono</div>
                    <div class="detail-value" id="modalPhone">+0 000 000 0000</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Fecha de Registro</div>
                    <div class="detail-value" id="modalDate">00/00/0000</div>
                </div>
            </div>

            <div class="connection-status">
                <div class="status-badge connected">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    PostgreSQL
                </div>
                <div class="status-badge <?php echo ($mongoCollection !== null) ? 'connected' : 'disconnected'; ?>">
                    <?php if ($mongoCollection !== null): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <?php endif; ?>
                    MongoDB
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(row) {
            const cliente = JSON.parse(row.getAttribute('data-cliente'));
            
            document.getElementById('modalId').textContent = '#' + cliente.id;
            document.getElementById('modalName').textContent = cliente.nombre;
            document.getElementById('modalEmail').textContent = cliente.email;
            document.getElementById('modalPhone').textContent = cliente.telefono;
            document.getElementById('modalDate').textContent = cliente.creado_en ? new Date(cliente.creado_en).toLocaleString() : 'N/A';

            const avatarContainer = document.getElementById('modalAvatar');
            if (cliente.foto && !cliente.foto.includes('Sin foto') && cliente.foto !== '') {
                const fotoUrl = cliente.foto.startsWith('/') ? cliente.foto.substring(1) : cliente.foto;
                avatarContainer.style.backgroundImage = `url('${fotoUrl}')`;
                avatarContainer.style.backgroundColor = 'transparent';
                avatarContainer.textContent = '';
            } else {
                avatarContainer.style.backgroundImage = 'none';
                avatarContainer.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                avatarContainer.textContent = cliente.nombre.substring(0, 2).toUpperCase();
            }

            document.getElementById('clientModal').classList.add('active');
        }

        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('clientModal').classList.remove('active');
        }
    </script>
</body>
</html>
