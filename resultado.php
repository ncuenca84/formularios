<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['resultado'])) {
    header('Location: index.php');
    exit;
}

$resultado = $_SESSION['resultado'];
unset($_SESSION['resultado']);

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$nombreInstitucion = $config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Generada - <?= htmlspecialchars($nombreInstitucion) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --color-primario: <?= htmlspecialchars($colorPrimario) ?>; }
        body { background: #f0f2f5; min-height: 100vh; }
        .header-bar {
            background: var(--color-primario); color: white;
            padding: 1rem 0; margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .result-card {
            background: white; border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
            overflow: hidden; max-width: 650px; margin: 0 auto;
        }
        .result-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white; padding: 2rem; text-align: center;
        }
        .result-header .icon { font-size: 3rem; margin-bottom: 0.5rem; }
        .result-body { padding: 2rem; }
        .codigo-box {
            background: #f8f9fa; border: 2px dashed var(--color-primario);
            border-radius: 10px; padding: 1rem; text-align: center;
            margin-bottom: 1.5rem;
        }
        .codigo-box .codigo {
            font-size: 1.4rem; font-weight: bold;
            font-family: monospace; color: var(--color-primario);
        }
        .action-btn {
            display: flex; align-items: center; padding: 1rem 1.5rem;
            border-radius: 10px; text-decoration: none; color: white;
            transition: all 0.3s; margin-bottom: 1rem; font-weight: 500;
        }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); color: white; }
        .action-btn .btn-icon { font-size: 1.5rem; margin-right: 1rem; }
        .action-btn .btn-text small { opacity: 0.85; }
        .btn-preview { background: linear-gradient(135deg, #0066cc, #004499); }
        .btn-download { background: linear-gradient(135deg, #dc3545, #c82333); }
        .info-box {
            background: #e8f4fd; border-left: 4px solid var(--color-primario);
            padding: 1rem; border-radius: 0 8px 8px 0; margin-top: 1.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="header-bar">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 style="font-size:1.3rem;margin:0;font-weight:600;"><?= htmlspecialchars($nombreInstitucion) ?></h1>
                <p style="margin:0;opacity:0.85;font-size:0.85rem;">Sistema de Formularios Institucionales</p>
            </div>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Inicio</a>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="result-card">
        <div class="result-header">
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <h4 class="mb-1">Solicitud Generada Exitosamente</h4>
            <p class="mb-0" style="opacity:0.9;"><?= htmlspecialchars($resultado['tipo_formulario']) ?></p>
        </div>

        <div class="result-body">
            <div class="codigo-box">
                <small class="text-muted">Codigo de solicitud</small><br>
                <span class="codigo"><?= htmlspecialchars($resultado['codigo']) ?></span><br>
                <small class="text-muted"><?= htmlspecialchars($resultado['nombre']) ?></small>
            </div>

            <!-- VISTA PREVIA -->
            <a href="<?= htmlspecialchars($resultado['pdf_path']) ?>" target="_blank" class="action-btn btn-preview">
                <span class="btn-icon"><i class="bi bi-eye-fill"></i></span>
                <span class="btn-text">
                    Vista Previa del Documento<br>
                    <small>Abrir el PDF en una nueva pestana para revisar</small>
                </span>
            </a>

            <!-- DESCARGAR PDF -->
            <a href="download.php?codigo=<?= urlencode($resultado['codigo']) ?>" class="action-btn btn-download">
                <span class="btn-icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                <span class="btn-text">
                    Descargar PDF<br>
                    <small>Guardar el documento en su equipo</small>
                </span>
            </a>

            <div class="info-box">
                <i class="bi bi-envelope-check-fill" style="color:var(--color-primario);"></i>
                <strong>Se ha enviado una notificacion</strong> al correo <strong>soporte@arconel.gob.ec</strong> con los datos de su solicitud.
            </div>

            <div class="info-box" style="background:#fff3cd;border-color:#ffc107;">
                <i class="bi bi-pen-fill" style="color:#856404;"></i>
                Descargue el documento, <strong>firmelo con FirmaEC</strong> y envielo al correo: <strong>soporte@arconel.gob.ec</strong>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Formularios
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
