<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Acuerdo de Confidencialidad y no Divulgación de Información con Terceros';

$osiNombre = $config['osi_nombre'] ?? 'William Lopez';
$osiCedula = $config['osi_cedula'] ?? '1713046827';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill"></i> ¿Cómo llenar este formulario?</h6>
                <ol class="mb-0 small">
                    <li>Complete los <strong>datos del receptor</strong>: la persona externa que recibirá información de la ARCONEL.</li>
                    <li>Verifique que el nombre y la cédula estén escritos correctamente, tal como constan en el documento de identidad.</li>
                    <li>Presione <strong>"Generar Acuerdo PDF"</strong>: el documento se creará con todas las cláusulas legales.</li>
                    <li>Descargue el PDF, fírmelo con <strong>FirmaEC</strong> y envíelo al correo <strong>soporte@arconel.gob.ec</strong>.</li>
                </ol>
            </div>

            <div class="form-card">
                <div class="card-header" style="background: #8B0000;">
                    <i class="bi bi-shield-lock"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="1">

                        <div class="section-title"><i class="bi bi-building"></i> DATOS DE LA ARCONEL</div>
                        <p class="text-muted small"><i class="bi bi-lock-fill"></i> Estos datos son fijos y corresponden al Oficial de Seguridad de la Información vigente. No es necesario modificarlos.</p>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombre del Oficial de Seguridad de la Información (OSI)</label>
                                <input type="text" name="oficial_seguridad" class="form-control" value="<?= htmlspecialchars($osiNombre) ?>" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cédula del OSI</label>
                                <input type="text" name="cedula_oficial" class="form-control" value="<?= htmlspecialchars($osiCedula) ?>" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo del Oficial</label>
                                <input type="text" name="cargo_oficial" class="form-control" value="Oficial de Seguridad de la Informacion (OSI)" readonly>
                            </div>
                        </div>

                        <div class="section-title"><i class="bi bi-person-badge"></i> DATOS DEL RECEPTOR</div>
                        <p class="text-muted small"><i class="bi bi-pencil-fill"></i> Complete los datos de la persona que firmará el acuerdo y recibirá la información.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Institución <span class="text-danger">*</span></label>
                                <input type="text" name="institucion_receptor" class="form-control" placeholder="Ej: Servicio de Rentas Internas" required>
                                <small class="text-muted">Institución o empresa a la que pertenece el receptor.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombres y Apellidos del Receptor <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_receptor" class="form-control" placeholder="Ej: PÉREZ LÓPEZ JUAN CARLOS" required>
                                <small class="text-muted">Tal como constan en la cédula de identidad.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo del Receptor <span class="text-danger">*</span></label>
                                <input type="text" name="cargo_receptor" class="form-control" placeholder="Ej: Analista, Consultor, Director" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cédula del Receptor <span class="text-danger">*</span></label>
                                <input type="text" name="cedula_receptor" class="form-control" data-solo-numeros maxlength="13" placeholder="Solo números, sin guiones" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="correo" class="form-control" placeholder="Ej: nombre@institucion.gob.ec">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección domiciliaria del Receptor</label>
                            <input type="text" name="direccion_receptor" class="form-control" placeholder="Ej: Av. Amazonas N34-451 y Av. Atahualpa, Quito">
                            <small class="text-muted">Dirección para avisos y notificaciones del acuerdo.</small>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-info-circle"></i> El documento se generará con todas las cláusulas legales completas.</small>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-pdf"></i> Generar Acuerdo PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooterHtml($config['nombre_institucion'] ?? 'ARCONEL'); ?>
