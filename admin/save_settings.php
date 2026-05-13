<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$seccion = $_POST['seccion'] ?? '';
$db = getDB();

try {
    switch ($seccion) {
        case 'general':
            $campos = [
                'nombre_institucion',
                'subtitulo_institucion',
                'titulo_formulario',
                'email_admin',
                'color_primario',
                'color_secundario',
                'texto_post_envio',
            ];
            $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                                  ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($campos as $campo) {
                if (isset($_POST[$campo])) {
                    $stmt->execute([$campo, trim($_POST[$campo])]);
                }
            }
            $_SESSION['admin_msg'] = 'Configuracion general guardada correctamente.';
            $_SESSION['admin_msg_tipo'] = 'success';
            break;

        case 'logos':
            $uploadDir = __DIR__ . '/../assets/uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $maxSize = 2 * 1024 * 1024; // 2MB
            $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];

            // Logo principal
            if (!empty($_FILES['logo_principal']['name']) && $_FILES['logo_principal']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['logo_principal']['size'] > $maxSize) {
                    throw new Exception('El logo principal excede el tamano maximo de 2MB.');
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['logo_principal']['tmp_name']);
                if (!in_array($mime, $allowedTypes)) {
                    throw new Exception('Formato de logo principal no permitido.');
                }
                $ext = match($mime) {
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/gif' => 'gif',
                    default => 'png',
                };
                $filename = 'logo_principal_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['logo_principal']['tmp_name'], $uploadDir . $filename);

                $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES ('logo_path', ?)
                                      ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
                $stmt->execute(['assets/uploads/logos/' . $filename]);
            }

            // Logo secundario
            if (!empty($_FILES['logo_secundario']['name']) && $_FILES['logo_secundario']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['logo_secundario']['size'] > $maxSize) {
                    throw new Exception('El logo secundario excede el tamano maximo de 2MB.');
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['logo_secundario']['tmp_name']);
                if (!in_array($mime, $allowedTypes)) {
                    throw new Exception('Formato de logo secundario no permitido.');
                }
                $ext = match($mime) {
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/gif' => 'gif',
                    default => 'png',
                };
                $filename = 'logo_secundario_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['logo_secundario']['tmp_name'], $uploadDir . $filename);

                $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES ('logo_secundario_path', ?)
                                      ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
                $stmt->execute(['assets/uploads/logos/' . $filename]);
            }

            $_SESSION['admin_msg'] = 'Logos actualizados correctamente.';
            $_SESSION['admin_msg_tipo'] = 'success';
            break;

        case 'formularios_meta':
            $stmt = $db->prepare("
                INSERT INTO formularios_meta (formulario_id, codigo_doc, version, nro_acta, fecha_aprobacion, titulo_encabezado)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    codigo_doc = VALUES(codigo_doc),
                    version = VALUES(version),
                    nro_acta = VALUES(nro_acta),
                    fecha_aprobacion = VALUES(fecha_aprobacion),
                    titulo_encabezado = VALUES(titulo_encabezado)
            ");
            for ($i = 1; $i <= 6; $i++) {
                $stmt->execute([
                    $i,
                    trim($_POST["meta_{$i}_codigo_doc"] ?? ''),
                    trim($_POST["meta_{$i}_version"] ?? '01'),
                    trim($_POST["meta_{$i}_nro_acta"] ?? ''),
                    trim($_POST["meta_{$i}_fecha_aprobacion"] ?? ''),
                    trim($_POST["meta_{$i}_titulo_encabezado"] ?? ''),
                ]);
            }
            $_SESSION['admin_msg'] = 'Encabezados de formularios guardados correctamente.';
            $_SESSION['admin_msg_tipo'] = 'success';
            break;

        case 'encabezado':
            $campos = [
                'encabezado_extra',
                'pie_pagina_linea1',
                'pie_pagina_linea2',
                'pie_pagina_linea3',
            ];
            $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                                  ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($campos as $campo) {
                if (isset($_POST[$campo])) {
                    $stmt->execute([$campo, trim($_POST[$campo])]);
                }
            }
            $_SESSION['admin_msg'] = 'Encabezado y pie de pagina guardados correctamente.';
            $_SESSION['admin_msg_tipo'] = 'success';
            break;

        default:
            $_SESSION['admin_msg'] = 'Seccion no reconocida.';
            $_SESSION['admin_msg_tipo'] = 'danger';
    }
} catch (Exception $e) {
    $_SESSION['admin_msg'] = 'Error: ' . $e->getMessage();
    $_SESSION['admin_msg_tipo'] = 'danger';
}

header('Location: index.php');
exit;
