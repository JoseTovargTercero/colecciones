<?php
session_start();

$admin_user = 'admin';
$admin_pass = 'admin';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Login logic
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === $admin_user && $pass === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Credenciales incorrectas.";
    }
}

// Action logic
$msg_success = '';
if (isset($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['susc_id'])) {
    require_once __DIR__ . '/../config/Database.php';
    $db = Database::getInstance();
    $suscId = (int)$_POST['susc_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Obtener datos del usuario y suscripción
        $stmt = $db->prepare("SELECT u.nombre, u.email, s.tipo_pago FROM suscripciones s JOIN system_users u ON s.usuario_id = u.user_id WHERE s.id = ?");
        $stmt->bind_param('i', $suscId);
        $stmt->execute();
        $suscData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $db->prepare("UPDATE suscripciones SET estatus = 'activa' WHERE id = ?");
        $stmt->bind_param('i', $suscId);
        $stmt->execute();
        $stmt->close();
        
        if ($suscData && !empty($suscData['email'])) {
            require_once __DIR__ . '/../helpers/mailHelper.php';
            $mail = new MailHelper();
            $subject = "Pago de Suscripcion Aprobado";
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #0f172a; color: #f8fafc; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155;'>
                <div style='background: linear-gradient(to right, #38bdf8, #818cf8); padding: 20px; text-align: center;'>
                    <h1 style='margin: 0; color: #ffffff; font-size: 24px;'>Pago Aprobado</h1>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; margin-bottom: 20px;'>Hola <strong>" . htmlspecialchars($suscData['nombre']) . "</strong>,</p>
                    <p style='font-size: 16px; line-height: 1.5; color: #cbd5e1;'>
                        Nos complace informarte que tu pago para la suscripción <strong>" . htmlspecialchars(ucfirst($suscData['tipo_pago'])) . "</strong> ha sido validado exitosamente.
                    </p>
                    <div style='background-color: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 15px; margin: 25px 0;'>
                        <p style='margin: 0; color: #6ee7b7; font-weight: bold;'>✓ Tu cuenta ya se encuentra activa</p>
                    </div>
                    <p style='font-size: 16px; line-height: 1.5; color: #cbd5e1;'>
                        Ya puedes iniciar sesión y disfrutar de todos los beneficios de tu plan.
                    </p>
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='" . ($_ENV['APP_URL'] ?? 'http://localhost/colecciones') . "' style='display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-weight: bold;'>Acceder a mi cuenta</a>
                    </div>
                </div>
                <div style='background-color: #1e293b; padding: 15px; text-align: center; font-size: 12px; color: #64748b;'>
                    <p style='margin: 0;'>Este es un mensaje automático, por favor no respondas a este correo.</p>
                </div>
            </div>";
            $mail->sendMail($suscData['email'], $subject, $body);
        }

        $msg_success = "Pago #$suscId aprobado exitosamente y correo enviado. La suscripción ahora está activa.";
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE suscripciones SET estatus = 'cancelada' WHERE id = ?");
        $stmt->bind_param('i', $suscId);
        $stmt->execute();
        $stmt->close();
        $msg_success = "Pago #$suscId rechazado. La suscripción fue cancelada.";
    }
}

$is_logged_in = isset($_SESSION['admin_logged_in']);
$pendings = [];
if ($is_logged_in) {
    require_once __DIR__ . '/../config/Database.php';
    $db = Database::getInstance();
    $sql = "SELECT s.id as susc_id, s.fecha_inicio, s.fecha_fin, s.tipo_pago, 
                   u.nombre, u.email,
                   h.monto_pagado, h.fecha_pago, h.referencia_pago
            FROM suscripciones s
            JOIN system_users u ON s.usuario_id = u.user_id
            JOIN historial_pagos h ON s.id = h.suscripcion_id
            WHERE s.estatus = 'pendiente'
            ORDER BY s.id ASC";
    $res = $db->query($sql);
    if($res) {
        $pendings = $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Validar Pagos</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --success-color: #10b981;
            --success-hover: #059669;
            --danger-color: #ef4444;
            --danger-hover: #dc2626;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, var(--bg-color));
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* ----- Login ----- */
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--card-shadow);
            text-align: center;
            animation: fadeIn 0.6s ease;
        }
        .login-card h2 {
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 1.8rem;
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-group { margin-bottom: 1.5rem; text-align: left; }
        .form-group label {
            display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem;
        }
        .form-control {
            width: 100%; padding: 0.8rem 1rem; border-radius: 0.5rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            font-family: inherit; font-size: 1rem;
            outline: none; transition: var(--transition);
        }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); }
        .btn {
            display: inline-block; width: 100%; padding: 0.8rem 1rem;
            border: none; border-radius: 0.5rem;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: var(--transition); font-family: inherit;
        }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); }
        .btn-success { background: var(--success-color); color: white; width: auto; padding: 0.5rem 1rem; }
        .btn-success:hover { background: var(--success-hover); transform: translateY(-2px); }
        .btn-danger { background: var(--danger-color); color: white; width: auto; padding: 0.5rem 1rem; }
        .btn-danger:hover { background: var(--danger-hover); transform: translateY(-2px); }

        .alert {
            padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.9rem;
        }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger-color); color: #fca5a5; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success-color); color: #6ee7b7; }

        /* ----- Dashboard ----- */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .header h1 {
            font-weight: 600; font-size: 2rem;
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logout-link { color: var(--text-muted); text-decoration: none; transition: var(--transition); }
        .logout-link:hover { color: var(--danger-color); }

        .card {
            background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px);
            border: 1px solid var(--border-color); border-radius: 1rem;
            padding: 2rem; box-shadow: var(--card-shadow);
            animation: fadeIn 0.4s ease;
        }
        
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-muted); }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: 1rem; opacity: 0.5; }

        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { font-size: 0.95rem; }
        tbody tr { transition: var(--transition); }
        tbody tr:hover { background: rgba(255, 255, 255, 0.03); }

        .badge {
            display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
        }
        .badge-pendiente { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.5); }
        
        .actions-form { display: flex; gap: 0.5rem; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <div class="login-wrapper container">
        <div class="login-card">
            <h2>Admin Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="admin" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Ingresar</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div class="header">
            <h1>Validación de Pagos</h1>
            <a href="?logout=1" class="logout-link">Cerrar Sesión</a>
        </div>

        <?php if ($msg_success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($pendings)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3>Todo al día</h3>
                    <p>No hay pagos de suscripción pendientes de validación en este momento.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Plan / Monto</th>
                                <th>Referencia</th>
                                <th>Fecha Pago</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendings as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nombre']) ?></strong><br>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($p['email']) ?></span>
                                    </td>
                                    <td>
                                        <span style="text-transform: capitalize;"><?= htmlspecialchars($p['tipo_pago']) ?></span><br>
                                        <strong style="color: #6ee7b7;">$<?= number_format($p['monto_pagado'], 2) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($p['referencia_pago']) ?></td>
                                    <td><?= htmlspecialchars($p['fecha_pago']) ?></td>
                                    <td><span class="badge badge-pendiente">Pendiente</span></td>
                                    <td>
                                        <form method="POST" action="" class="actions-form">
                                            <input type="hidden" name="susc_id" value="<?= $p['susc_id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('¿Aprobar pago de <?= htmlspecialchars($p['nombre']) ?>?')">Aprobar</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('¿Rechazar pago de <?= htmlspecialchars($p['nombre']) ?>?')">Rechazar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
