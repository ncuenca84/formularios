<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Acuerdo de Confidencialidad y no Divulgacion de Informacion con Terceros';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="form-card">
                <div class="card-header" style="background: #8B0000;">
                    <i class="bi bi-shield-lock"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="../process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="1">

                        <div class="section-title"><i class="bi bi-building"></i> DATOS DE LA ARCONEL</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del Oficial de Seguridad de la Informacion</label>
                                <input type="text" name="oficial_seguridad" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo del Oficial</label>
                                <input type="text" name="cargo_oficial" class="form-control" value="Oficial de Seguridad de la Informacion" required>
                            </div>
                        </div>

                        <div class="section-title"><i class="bi bi-person-badge"></i> DATOS DEL RECEPTOR (TERCERO)</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre/Razon Social del Receptor <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_receptor" class="form-control" placeholder="Ej: Servicio de Rentas Internas" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Representante Legal <span class="text-danger">*</span></label>
                                <input type="text" name="representante_legal" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo del Representante <span class="text-danger">*</span></label>
                                <input type="text" name="cargo_representante" class="form-control" placeholder="Ej: Director General" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cedula del Representante <span class="text-danger">*</span></label>
                                <input type="text" name="cedula_representante" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo electronico <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Direccion del Receptor</label>
                                <input type="text" name="direccion_receptor" class="form-control">
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-info-circle"></i> El documento se generara con todas las clausulas legales completas.</small>
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
