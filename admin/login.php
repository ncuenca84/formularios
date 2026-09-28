<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            $_SESSION['admin_logged'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o contrasena incorrectos.';
        }
    } catch (Exception $e) {
        $error = 'Error de conexion. Verifique la base de datos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Iniciar Sesion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; }
        .login-card {
            max-width: 420px; margin: 0 auto;
            background: white; border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header { background: #003366; color: white; padding: 2rem; text-align: center; }
        .login-body { padding: 2rem; }
        .btn-login { background: #003366; border: none; width: 100%; padding: 0.7rem; font-weight: 600; }
        .btn-login:hover { background: #002244; }
    </style>
</head>
<body>
<div class="container">
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-shield-lock-fill" style="font-size: 2.5rem;"></i>
            <h4 class="mt-2 mb-0">Panel de Administracion</h4>
            <small>Sistema de Formularios</small>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-person"></i> Usuario</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-key"></i> Contrasena</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesion
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="../index.php" class="text-muted small"><i class="bi bi-arrow-left"></i> Volver al formulario</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
