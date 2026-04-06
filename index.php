<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$config = getAllConfig();
$nombreInstitucion = $config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD';
$colorPrimario = $config['color_primario'] ?? '#003366';

$mensaje = $_SESSION['mensaje'] ?? null;
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'success';
$codigo = $_SESSION['codigo_solicitud'] ?? '';
$textoPostEnvio = $config['texto_post_envio'] ?? '';
$formOrigen = $_SESSION['form_origen'] ?? '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje'], $_SESSION['codigo_solicitud'], $_SESSION['form_origen']);

$formularios = [
    1 => [
        'titulo' => 'Acuerdo de Confidencialidad y no Divulgacion de Informacion con Terceros',
        'icono' => 'bi-shield-lock',
        'color' => '#8B0000',
        'descripcion' => 'Acuerdo de confidencialidad para terceros que acceden a informacion de la ARCONEL.',
    ],
    2 => [
        'titulo' => 'Solicitud de Acceso a Sistemas para Terceros',
        'icono' => 'bi-person-badge',
        'color' => '#006400',
        'descripcion' => 'Solicitud de acceso a sistemas y servicios de TI para usuarios externos.',
    ],
    3 => [
        'titulo' => 'Solicitud de Acceso a los Sistemas Informaticos',
        'icono' => 'bi-pc-display',
        'color' => '#003366',
        'descripcion' => 'Solicitud de credenciales y permisos a sistemas de informacion internos (SISDAT, SIGCON, etc.).',
    ],
    4 => [
        'titulo' => 'Habilitacion de Acceso VPN para Usuarios Externos',
        'icono' => 'bi-globe',
        'color' => '#4B0082',
        'descripcion' => 'Formulario de habilitacion de acceso a la red interna via VPN para externos.',
    ],
    5 => [
        'titulo' => 'Autorizacion de Privilegios Especiales - Directorio Activo',
        'icono' => 'bi-key',
        'color' => '#B8860B',
        'descripcion' => 'Solicitud de privilegios especiales en el directorio activo institucional.',
    ],
    6 => [
        'titulo' => 'Solicitud de Accesos Especiales para Internet',
        'icono' => 'bi-wifi',
        'color' => '#2F4F4F',
        'descripcion' => 'Solicitud de acceso a sitios web, aplicaciones o servicios restringidos en internet.',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Formularios - <?= htmlspecialchars($nombreInstitucion) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --color-primario: <?= htmlspecialchars($colorPrimario) ?>; }
        body { background: #f0f2f5; min-height: 100vh; }
        .header-bar {
            background: var(--color-primario);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-bar h1 { font-size: 1.5rem; margin: 0; font-weight: 600; }
        .header-bar p { margin: 0.25rem 0 0; opacity: 0.85; font-size: 0.9rem; }
        .form-card-selector {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .form-card-selector:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            color: inherit;
        }
        .form-card-selector .card-icon {
            width: 60px; height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        .form-card-selector .card-body { padding: 1.5rem; }
        .form-card-selector h5 { font-size: 0.95rem; font-weight: 600; color: #333; }
        .form-card-selector p { font-size: 0.82rem; color: #666; margin: 0; }
        .form-number {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 2rem;
            font-weight: 800;
            opacity: 0.08;
        }
        .alert-success-custom {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 1.5rem;
        }
        .codigo-solicitud {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--color-primario);
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="header-bar">
    <div class="container">
        <div class="d-flex align-items-center">
            <?php
            $logoPath = $config['logo_path'] ?? '';
            if (!empty($logoPath) && file_exists(__DIR__ . '/' . $logoPath)):
            ?>
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" height="50" class="me-3">
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars($nombreInstitucion) ?></h1>
                <p><?= htmlspecialchars($config['subtitulo_institucion'] ?? 'Direccion de Tecnologias de la Informacion y Comunicacion') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">

    <?php if ($mensaje): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?php if ($tipo_mensaje === 'success'): ?>
                        <div class="alert-success-custom">
                            <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Solicitud generada exitosamente</h4>
                            <p class="codigo-solicitud"><?= htmlspecialchars($codigo) ?></p>
                            <hr>
                            <p class="mb-1"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Su PDF se ha descargado automaticamente.</p>
                            <p class="mb-0 mt-2">
                                <i class="bi bi-info-circle-fill"></i>
                                <strong><?= htmlspecialchars($textoPostEnvio) ?></strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $mensaje ?>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mb-4">
        <h3 class="fw-bold" style="color: var(--color-primario);">Formularios Institucionales</h3>
        <p class="text-muted">Seleccione el formulario que desea completar</p>
    </div>

    <div class="row g-4">
        <?php foreach ($formularios as $num => $form): ?>
            <div class="col-md-6 col-lg-4">
                <a href="forms/form<?= $num ?>.php" class="form-card-selector position-relative">
                    <div class="card-body">
                        <span class="form-number"><?= $num ?></span>
                        <div class="card-icon" style="background: <?= $form['color'] ?>;">
                            <i class="bi <?= $form['icono'] ?>"></i>
                        </div>
                        <h5><?= htmlspecialchars($form['titulo']) ?></h5>
                        <p class="mt-2"><?= htmlspecialchars($form['descripcion']) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-5 text-muted small">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($nombreInstitucion) ?> - Sistema de Formularios Institucionales</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($tipo_mensaje === 'success' && !empty($codigo)): ?>
<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        window.location.href = 'download.php?codigo=<?= urlencode($codigo) ?>';
    }, 500);
});
</script>
<?php endif; ?>

</body>
</html>
