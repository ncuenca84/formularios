<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$config = getAllConfig();
$nombreInstitucion = $config['nombre_institucion'] ?? 'INSTITUCION PUBLICA';
$tituloFormulario = $config['titulo_formulario'] ?? 'SOLICITUD DE ACCESO VPN';
$colorPrimario = $config['color_primario'] ?? '#003366';

$mensaje = $_SESSION['mensaje'] ?? null;
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'success';
$codigo = $_SESSION['codigo_solicitud'] ?? '';
$textoPostEnvio = $config['texto_post_envio'] ?? '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje'], $_SESSION['codigo_solicitud']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloFormulario) ?> - <?= htmlspecialchars($nombreInstitucion) ?></title>
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
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }
        .form-card .card-header {
            background: var(--color-primario);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .form-card .card-body { padding: 2rem; }
        .form-label { font-weight: 500; color: #333; }
        .form-control:focus { border-color: var(--color-primario); box-shadow: 0 0 0 0.2rem rgba(0,51,102,0.15); }
        .btn-primary {
            background: var(--color-primario);
            border-color: var(--color-primario);
            padding: 0.6rem 2rem;
            font-weight: 500;
        }
        .btn-primary:hover { background: #002244; border-color: #002244; }
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
                <p><?= htmlspecialchars($config['subtitulo_institucion'] ?? '') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
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
            <?php endif; ?>

            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($tituloFormulario) ?>
                </div>
                <div class="card-body">
                    <form action="process.php" method="POST" id="formSolicitud" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_completo" class="form-label">
                                    <i class="bi bi-person"></i> Nombre Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                       placeholder="Ej: Juan Carlos Perez Lopez" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">
                                    <i class="bi bi-credit-card"></i> Cedula <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="cedula" name="cedula"
                                       placeholder="Ej: 1712345678" pattern="[0-9]{10,13}"
                                       maxlength="13" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="correo" class="form-label">
                                <i class="bi bi-envelope"></i> Correo Electronico <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="correo" name="correo"
                                   placeholder="Ej: juan.perez@institucion.gob.ec" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cargo" class="form-label">
                                    <i class="bi bi-briefcase"></i> Cargo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="cargo" name="cargo"
                                       placeholder="Ej: Analista de Sistemas" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="area" class="form-label">
                                    <i class="bi bi-building"></i> Area / Departamento <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="area" name="area"
                                       placeholder="Ej: Direccion de TI" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-lock"></i> Sus datos seran tratados de forma confidencial.
                            </small>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-pdf"></i> Generar Solicitud PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4 text-muted small">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($nombreInstitucion) ?> - Sistema de Formularios</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('formSolicitud').addEventListener('submit', function(e) {
    const campos = this.querySelectorAll('[required]');
    let valido = true;
    campos.forEach(function(campo) {
        if (!campo.value.trim()) {
            campo.classList.add('is-invalid');
            valido = false;
        } else {
            campo.classList.remove('is-invalid');
        }
    });
    if (!valido) e.preventDefault();
});

document.getElementById('cedula').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php if ($tipo_mensaje === 'success' && !empty($codigo)): ?>
<script>
// Trigger PDF download
window.addEventListener('load', function() {
    setTimeout(function() {
        window.location.href = 'download.php?codigo=<?= urlencode($codigo) ?>';
    }, 500);
});
</script>
<?php endif; ?>

</body>
</html>
