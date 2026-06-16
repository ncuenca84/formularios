<?php
/**
 * Funciones de layout compartido para los formularios
 */

function renderHeader(array $config, string $titulo): void
{
    $nombreInstitucion = $config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD';
    $colorPrimario = $config['color_primario'] ?? '#003366';
    $logoPath = $config['logo_path'] ?? '';
    ?>
    <div class="header-bar" style="background: <?= htmlspecialchars($colorPrimario) ?>;">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <?php if (!empty($logoPath) && file_exists(__DIR__ . '/../' . $logoPath)): ?>
                        <img src="../<?= htmlspecialchars($logoPath) ?>" alt="Logo" height="50" class="me-3">
                    <?php endif; ?>
                    <div>
                        <h1 style="font-size:1.3rem;margin:0;font-weight:600;"><?= htmlspecialchars($nombreInstitucion) ?></h1>
                        <p style="margin:0.25rem 0 0;opacity:0.85;font-size:0.85rem;"><?= htmlspecialchars($titulo) ?></p>
                    </div>
                </div>
                <a href="../index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>
        </div>
    </div>
    <?php
}

function renderHeadHtml(string $titulo, string $colorPrimario = '#003366'): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($titulo) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            :root { --color-primario: <?= htmlspecialchars($colorPrimario) ?>; }
            body { background: #f0f2f5; min-height: 100vh; }
            .header-bar { color: white; padding: 1rem 0; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .form-card {
                background: white; border-radius: 12px;
                box-shadow: 0 2px 20px rgba(0,0,0,0.08);
                border: none; overflow: hidden; margin-bottom: 2rem;
            }
            .form-card .card-header {
                background: var(--color-primario); color: white;
                padding: 1rem 1.5rem; font-weight: 600; font-size: 1.05rem;
            }
            .form-card .card-body { padding: 1.5rem 2rem; }
            .section-title {
                background: #e9ecef; padding: 0.5rem 1rem;
                font-weight: 600; font-size: 0.9rem; color: var(--color-primario);
                border-left: 4px solid var(--color-primario);
                margin: 1.5rem 0 1rem;
            }
            .form-label { font-weight: 500; color: #333; font-size: 0.9rem; }
            .form-control:focus, .form-select:focus { border-color: var(--color-primario); box-shadow: 0 0 0 0.2rem rgba(0,51,102,0.15); }
            .btn-primary { background: var(--color-primario); border-color: var(--color-primario); }
            .btn-primary:hover { background: #002244; border-color: #002244; }
            .consideraciones { font-size: 0.82rem; color: #555; background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 3px solid var(--color-primario); }
            .consideraciones li { margin-bottom: 0.4rem; }
            .firma-box { border: 1px dashed #ccc; padding: 1rem; border-radius: 8px; text-align: center; min-height: 120px; }
            .firma-box .firma-titulo { font-weight: 600; font-size: 0.85rem; color: var(--color-primario); }
            .firma-box .firma-label { font-size: 0.8rem; color: #666; margin-top: 0.5rem; }
        </style>
    </head>
    <body>
    <?php
}

function renderFooterHtml(string $nombreInstitucion): void
{
    ?>
    <div class="text-center mt-3 mb-5 text-muted small">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($nombreInstitucion) ?> - Sistema de Formularios</p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let valido = true;
            this.querySelectorAll('[required]').forEach(function(c) {
                if (!c.value.trim()) { c.classList.add('is-invalid'); valido = false; }
                else { c.classList.remove('is-invalid'); }
            });
            if (!valido) e.preventDefault();
        });
    });
    document.querySelectorAll('input[data-solo-numeros]').forEach(function(el) {
        el.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
    });
    </script>
    </body>
    </html>
    <?php
}
