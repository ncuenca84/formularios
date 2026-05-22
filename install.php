<?php
/**
 * Script de instalacion - Ejecutar una sola vez
 * Crea las tablas y el usuario admin por defecto
 *
 * URL: http://tu-servidor/install.php
 * Despues de instalar, ELIMINAR este archivo.
 */

require_once __DIR__ . '/config/database.php';

$resultado = [];
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = $_POST['admin_pass'] ?? 'admin123';
    $adminNombre = trim($_POST['admin_nombre'] ?? 'Administrador');

    try {
        $db = getDB();

        // Crear tablas
        $db->exec("
            CREATE TABLE IF NOT EXISTS solicitudes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(20) NOT NULL UNIQUE,
                nombre_completo VARCHAR(255) NOT NULL,
                cedula VARCHAR(20) NOT NULL,
                correo VARCHAR(255) NOT NULL,
                cargo VARCHAR(255) NOT NULL,
                area VARCHAR(255) NOT NULL,
                tipo_formulario VARCHAR(100) NOT NULL DEFAULT 'Solicitud de Acceso VPN',
                fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
                pdf_path VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $resultado[] = 'Tabla solicitudes creada.';

        $db->exec("
            CREATE TABLE IF NOT EXISTS configuracion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(100) NOT NULL UNIQUE,
                valor TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $resultado[] = 'Tabla configuracion creada.';

        $db->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $resultado[] = 'Tabla admins creada.';

        // Crear tabla de metadatos por formulario
        $db->exec("
            CREATE TABLE IF NOT EXISTS formularios_meta (
                id INT AUTO_INCREMENT PRIMARY KEY,
                formulario_id INT NOT NULL,
                codigo_doc VARCHAR(50) NOT NULL DEFAULT '',
                version VARCHAR(10) NOT NULL DEFAULT '01',
                nro_acta VARCHAR(20) NOT NULL DEFAULT '',
                fecha_aprobacion VARCHAR(20) NOT NULL DEFAULT '',
                titulo_encabezado VARCHAR(255) NOT NULL DEFAULT '',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_formulario (formulario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $resultado[] = 'Tabla formularios_meta creada.';

        // Insertar metadatos por defecto por formulario
        $metaDefaults = [
            [1, 'GTIC.PA.FO.01', '01', '021', '27/01/2026', 'ACUERDO DE CONFIDENCIALIDAD Y NO DIVULGACION DE INFORMACION CON TERCEROS'],
            [2, 'GTIC.PA.FO.02', '01', '021', '27/01/2026', 'SOLICITUD DE ACCESO A SISTEMAS PARA TERCEROS'],
            [3, 'GTIC.PA.FO.03', '01', '021', '27/01/2026', 'SOLICITUD DE ACCESO A LOS SISTEMAS INFORMATICOS'],
            [4, 'GTIC.PA.FO.04', '01', '021', '27/01/2026', 'FORMULARIO DE HABILITACION DE ACCESO VPN PARA USUARIOS EXTERNOS'],
            [5, 'GTIC.PA.FO.05', '01', '021', '27/01/2026', 'FORMULARIO DE AUTORIZACION DE PRIVILEGIOS ESPECIALES DIRECTORIO ACTIVO'],
            [6, 'GTIC.PA.FO.06', '01', '021', '27/01/2026', 'SOLICITUD DE ACCESOS ESPECIALES PARA EL SERVICIO DE INTERNET'],
        ];
        $stmtMeta = $db->prepare("INSERT IGNORE INTO formularios_meta (formulario_id, codigo_doc, version, nro_acta, fecha_aprobacion, titulo_encabezado) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($metaDefaults as $m) {
            $stmtMeta->execute($m);
        }
        $resultado[] = 'Metadatos de formularios insertados.';

        // Insertar configuracion por defecto
        $defaults = [
            'nombre_institucion' => 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD',
            'subtitulo_institucion' => 'DIRECCION DE TECNOLOGIAS DE LA INFORMACION Y COMUNICACION',
            'titulo_formulario' => 'FORMULARIOS INSTITUCIONALES',
            'logo_path' => '',
            'logo_secundario_path' => '',
            'color_primario' => '#003366',
            'color_secundario' => '#0066cc',
            'pie_pagina_linea1' => 'Direccion: Av. Principal S/N',
            'pie_pagina_linea2' => 'Telefono: (02) 123-4567 | www.institucion.gob.ec',
            'pie_pagina_linea3' => 'Correo: soporte@tudominio.com',
            'encabezado_extra' => '',
            'email_admin' => 'soporte@tudominio.com',
            'texto_post_envio' => 'Descargue el documento, firmelo con FirmaEC y envielo al correo: soporte@tudominio.com',
        ];

        $stmt = $db->prepare("INSERT IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
        foreach ($defaults as $clave => $valor) {
            $stmt->execute([$clave, $valor]);
        }
        $resultado[] = 'Configuracion por defecto insertada.';

        // Crear admin
        $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, password, nombre) VALUES (?, ?, ?)
                              ON DUPLICATE KEY UPDATE password = VALUES(password), nombre = VALUES(nombre)");
        $stmt->execute([$adminUser, $hashedPass, $adminNombre]);
        $resultado[] = "Usuario admin '{$adminUser}' creado/actualizado.";

        // Crear directorios necesarios
        $dirs = [
            __DIR__ . '/assets/uploads/logos',
            __DIR__ . '/assets/uploads/pdfs',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $resultado[] = "Directorio creado: " . basename($dir);
            }
        }

        $resultado[] = '--- INSTALACION COMPLETADA ---';
        $resultado[] = 'IMPORTANTE: Elimine este archivo (install.php) por seguridad.';

    } catch (Exception $e) {
        $error = true;
        $resultado[] = 'ERROR: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalacion - Sistema de Formularios PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; }
        .install-card { max-width: 550px; margin: 0 auto; border-radius: 15px; overflow: hidden; }
    </style>
</head>
<body>
<div class="container">
    <div class="card install-card shadow-lg">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0">Instalacion del Sistema</h4>
            <small>Sistema de Formularios PDF Institucionales</small>
        </div>
        <div class="card-body p-4">
            <?php if (empty($resultado)): ?>
                <div class="alert alert-info">
                    <strong>Requisitos previos:</strong>
                    <ul class="mb-0 mt-1">
                        <li>PHP 8.0+ con extensiones PDO y mbstring</li>
                        <li>MySQL 5.7+ con base de datos <code>formularios_pdf</code> creada</li>
                        <li>Composer instalado (ejecutar <code>composer install</code>)</li>
                    </ul>
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuario Admin</label>
                        <input type="text" name="admin_user" class="form-control" value="admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contrasena Admin</label>
                        <input type="password" name="admin_pass" class="form-control" value="" placeholder="Ingrese una contrasena segura" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Administrador</label>
                        <input type="text" name="admin_nombre" class="form-control" value="Administrador" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">Instalar Sistema</button>
                </form>
            <?php else: ?>
                <div class="alert alert-<?= $error ? 'danger' : 'success' ?>">
                    <?php foreach ($resultado as $msg): ?>
                        <div><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$error): ?>
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-success">Ir al Formulario</a>
                        <a href="admin/login.php" class="btn btn-outline-primary">Ir al Panel Admin</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
