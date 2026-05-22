<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

$config = getAllConfig();
$mensaje = $_SESSION['admin_msg'] ?? null;
$tipoMsg = $_SESSION['admin_msg_tipo'] ?? 'success';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_tipo']);

// Obtener metadatos de formularios
$formulariosMeta = getAllFormulariosMeta();

$nombresFormularios = [
    1 => 'Acuerdo de Confidencialidad',
    2 => 'Acceso Sistemas Terceros',
    3 => 'Acceso Sistemas Informaticos',
    4 => 'Acceso VPN Externos',
    5 => 'Privilegios Dir. Activo',
    6 => 'Accesos Internet',
];

// Obtener solicitudes recientes
$solicitudes = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM solicitudes ORDER BY created_at DESC LIMIT 50");
    $solicitudes = $stmt->fetchAll();
} catch (Exception $e) {
    // tabla puede no existir aun
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administracion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { background: #f0f2f5; }
        .sidebar {
            width: var(--sidebar-width); min-height: 100vh; background: #003366;
            position: fixed; top: 0; left: 0; color: white; padding-top: 1rem;
        }
        .sidebar .brand { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .brand h5 { margin: 0; font-size: 1rem; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7); padding: 0.8rem 1.5rem; display: block;
            text-decoration: none; transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card .stat-number { font-size: 2rem; font-weight: bold; color: #003366; }
        .config-card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logo-preview { max-height: 80px; border: 2px dashed #ddd; padding: 5px; border-radius: 5px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">
        <h5><i class="bi bi-gear-fill"></i> Administracion</h5>
        <small class="text-white-50">Formularios PDF</small>
    </div>
    <nav class="mt-3">
        <a href="#dashboard" class="nav-link active" data-tab="dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="#config" class="nav-link" data-tab="config">
            <i class="bi bi-sliders"></i> Configuracion General
        </a>
        <a href="#logos" class="nav-link" data-tab="logos">
            <i class="bi bi-image"></i> Logos e Imagenes
        </a>
        <a href="#encabezado" class="nav-link" data-tab="encabezado">
            <i class="bi bi-layout-text-window"></i> Encabezado y Pie
        </a>
        <a href="#formularios_meta" class="nav-link" data-tab="formularios_meta">
            <i class="bi bi-card-heading"></i> Encabezados por Form.
        </a>
        <a href="#solicitudes" class="nav-link" data-tab="solicitudes">
            <i class="bi bi-file-earmark-text"></i> Solicitudes
        </a>
        <hr class="border-light mx-3">
        <a href="../index.php" class="nav-link"><i class="bi bi-eye"></i> Ver Formulario</a>
        <a href="logout.php" class="nav-link text-warning"><i class="bi bi-box-arrow-left"></i> Cerrar Sesion</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Bienvenido, <?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin') ?></h3>
        <span class="badge bg-success"><i class="bi bi-circle-fill"></i> Conectado</span>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoMsg ?> alert-dismissible fade show">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TAB: Dashboard -->
    <div class="tab-content" id="tab-dashboard">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="stat-number"><?= count($solicitudes) ?></div>
                    <small class="text-muted">Total Solicitudes</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="stat-number text-warning"><?= count(array_filter($solicitudes, fn($s) => $s['estado'] === 'pendiente')) ?></div>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="stat-number text-success"><?= count(array_filter($solicitudes, fn($s) => $s['estado'] === 'aprobada')) ?></div>
                    <small class="text-muted">Aprobadas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="stat-number text-danger"><?= count(array_filter($solicitudes, fn($s) => $s['estado'] === 'rechazada')) ?></div>
                    <small class="text-muted">Rechazadas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: Configuracion General -->
    <div class="tab-content d-none" id="tab-config">
        <div class="card config-card">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-sliders"></i> Configuracion General</h5></div>
            <div class="card-body">
                <form action="save_settings.php" method="POST">
                    <input type="hidden" name="seccion" value="general">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombre de la Institucion</label>
                            <input type="text" name="nombre_institucion" class="form-control"
                                   value="<?= htmlspecialchars($config['nombre_institucion'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Subtitulo</label>
                            <input type="text" name="subtitulo_institucion" class="form-control"
                                   value="<?= htmlspecialchars($config['subtitulo_institucion'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Titulo del Formulario</label>
                            <input type="text" name="titulo_formulario" class="form-control"
                                   value="<?= htmlspecialchars($config['titulo_formulario'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email del Administrador</label>
                            <input type="email" name="email_admin" class="form-control"
                                   value="<?= htmlspecialchars($config['email_admin'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Color Primario</label>
                            <input type="color" name="color_primario" class="form-control form-control-color"
                                   value="<?= htmlspecialchars($config['color_primario'] ?? '#003366') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Color Secundario</label>
                            <input type="color" name="color_secundario" class="form-control form-control-color"
                                   value="<?= htmlspecialchars($config['color_secundario'] ?? '#0066cc') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Texto Post-Envio (mensaje al usuario)</label>
                        <textarea name="texto_post_envio" class="form-control" rows="2"><?= htmlspecialchars($config['texto_post_envio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Configuracion</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB: Logos -->
    <div class="tab-content d-none" id="tab-logos">
        <div class="card config-card">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-image"></i> Logos e Imagenes</h5></div>
            <div class="card-body">
                <form action="save_settings.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="seccion" value="logos">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Logo Principal (izquierda)</label>
                            <?php if (!empty($config['logo_path']) && file_exists(__DIR__ . '/../' . $config['logo_path'])): ?>
                                <div class="mb-2">
                                    <img src="../<?= htmlspecialchars($config['logo_path']) ?>" class="logo-preview" alt="Logo actual">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo_principal" class="form-control" accept="image/png,image/jpeg,image/gif">
                            <small class="text-muted">Formatos: PNG, JPG, GIF. Max: 2MB.</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Logo Secundario (derecha)</label>
                            <?php if (!empty($config['logo_secundario_path']) && file_exists(__DIR__ . '/../' . $config['logo_secundario_path'])): ?>
                                <div class="mb-2">
                                    <img src="../<?= htmlspecialchars($config['logo_secundario_path']) ?>" class="logo-preview" alt="Logo secundario actual">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo_secundario" class="form-control" accept="image/png,image/jpeg,image/gif">
                            <small class="text-muted">Formatos: PNG, JPG, GIF. Max: 2MB.</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Subir Logos</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB: Encabezado y Pie -->
    <div class="tab-content d-none" id="tab-encabezado">
        <div class="card config-card">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-layout-text-window"></i> Encabezado y Pie de Pagina</h5></div>
            <div class="card-body">
                <form action="save_settings.php" method="POST">
                    <input type="hidden" name="seccion" value="encabezado">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Texto Extra en Encabezado</label>
                        <input type="text" name="encabezado_extra" class="form-control"
                               value="<?= htmlspecialchars($config['encabezado_extra'] ?? '') ?>"
                               placeholder="Ej: Republica del Ecuador">
                    </div>
                    <hr>
                    <h6 class="fw-bold text-muted mb-3">PIE DE PAGINA DEL PDF</h6>
                    <div class="mb-3">
                        <label class="form-label">Linea 1 (Direccion)</label>
                        <input type="text" name="pie_pagina_linea1" class="form-control"
                               value="<?= htmlspecialchars($config['pie_pagina_linea1'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Linea 2 (Telefono / Web)</label>
                        <input type="text" name="pie_pagina_linea2" class="form-control"
                               value="<?= htmlspecialchars($config['pie_pagina_linea2'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Linea 3 (Correo)</label>
                        <input type="text" name="pie_pagina_linea3" class="form-control"
                               value="<?= htmlspecialchars($config['pie_pagina_linea3'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Encabezado/Pie</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB: Encabezados por Formulario -->
    <div class="tab-content d-none" id="tab-formularios_meta">
        <div class="card config-card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-card-heading"></i> Encabezados por Formulario</h5>
                <small class="text-muted">Configure el codigo, version, N° de acta y fecha de aprobacion de cada formulario</small>
            </div>
            <div class="card-body">
                <form action="save_settings.php" method="POST">
                    <input type="hidden" name="seccion" value="formularios_meta">

                    <div class="alert alert-info small mb-4">
                        <i class="bi bi-info-circle"></i> Estos datos aparecen en el encabezado institucional de cada PDF generado (esquina superior derecha del documento).
                    </div>

                    <?php for ($i = 1; $i <= 6; $i++):
                        $m = $formulariosMeta[$i] ?? [];
                    ?>
                    <div class="border rounded p-3 mb-3">
                        <h6 class="fw-bold" style="color:#003366;">
                            <span class="badge bg-secondary"><?= $i ?></span>
                            <?= htmlspecialchars($nombresFormularios[$i] ?? "Formulario $i") ?>
                        </h6>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label small fw-bold">Codigo Documento</label>
                                <input type="text" name="meta_<?= $i ?>_codigo_doc" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($m['codigo_doc'] ?? '') ?>"
                                       placeholder="Ej: GTIC.PA.FO.0<?= $i ?>">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small fw-bold">Version</label>
                                <input type="text" name="meta_<?= $i ?>_version" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($m['version'] ?? '01') ?>"
                                       placeholder="01">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small fw-bold">N° de Acta</label>
                                <input type="text" name="meta_<?= $i ?>_nro_acta" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($m['nro_acta'] ?? '') ?>"
                                       placeholder="021">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small fw-bold">Fecha Aprobacion</label>
                                <input type="text" name="meta_<?= $i ?>_fecha_aprobacion" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($m['fecha_aprobacion'] ?? '') ?>"
                                       placeholder="27/01/2026">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label small fw-bold">Titulo en Encabezado</label>
                                <input type="text" name="meta_<?= $i ?>_titulo_encabezado" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($m['titulo_encabezado'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Encabezados</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB: Solicitudes -->
    <div class="tab-content d-none" id="tab-solicitudes">
        <div class="card config-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Solicitudes Recientes</h5>
                <span class="badge bg-primary"><?= count($solicitudes) ?> registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Cedula</th>
                                <th>Area</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($solicitudes)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No hay solicitudes registradas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($solicitudes as $sol): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($sol['codigo']) ?></code></td>
                                    <td><?= htmlspecialchars($sol['nombre_completo']) ?></td>
                                    <td><?= htmlspecialchars($sol['cedula']) ?></td>
                                    <td><?= htmlspecialchars($sol['area']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($sol['estado']) {
                                            'aprobada' => 'bg-success',
                                            'rechazada' => 'bg-danger',
                                            default => 'bg-warning text-dark',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($sol['estado']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($sol['pdf_path'])): ?>
                                            <a href="../<?= htmlspecialchars($sol['pdf_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tab navigation
document.querySelectorAll('[data-tab]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const tab = this.dataset.tab;

        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('d-none'));
        document.getElementById('tab-' + tab)?.classList.remove('d-none');

        document.querySelectorAll('[data-tab]').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>
</body>
</html>
