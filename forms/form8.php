<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Solicitud de Cuenta Adicional de Correo Electrónico';
$dominio = getConfig('eqr_dominio_correo', 'arconel.gob.ec');

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill"></i> ¿Qué es este formulario?</h6>
                <p class="small mb-2">Use este formulario cuando su área necesite una <strong>cuenta adicional
                de correo electrónico institucional</strong> (por ejemplo, una cuenta general de área o de
                servicio, como <em>gestion.documental@<?= htmlspecialchars($dominio) ?></em>).</p>
                <ol class="mb-0 small">
                    <li><strong>Paso 1:</strong> Escriba sus datos y los de la cuenta que solicita.</li>
                    <li><strong>Paso 2:</strong> Indique quién aprueba (su Director/a o Jefe/a de Área).</li>
                    <li><strong>Paso 3:</strong> Presione <strong>"Continuar y firmar en línea"</strong>: usted
                        firmará ahí mismo con su archivo .p12 y su jefe recibirá el enlace para aprobar con su
                        firma. Al completarse, la solicitud firmada llega a la DTIC. <strong>No necesita enviar
                        nada manualmente.</strong></li>
                </ol>
            </div>

            <div class="form-card">
                <div class="card-header" style="background: #B45309;">
                    <i class="bi bi-envelope-plus"></i> <?= htmlspecialchars($titulo) ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="8">

                        <div class="section-title"><i class="bi bi-1-circle-fill"></i> PASO 1: SUS DATOS</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos y Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_completo" class="form-control" required
                                       placeholder="Tal como constan en su cédula">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" data-solo-numeros maxlength="10" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Correo institucional actual <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" required
                                       placeholder="usuario@<?= htmlspecialchars($dominio) ?>">
                                <small class="text-muted">Ahí le llegará su enlace de firma.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" name="cargo" class="form-control" required
                                       placeholder="Ej: Especialista en Gestión Documental">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departamento / Dirección <span class="text-danger">*</span></label>
                                <input type="text" name="area" class="form-control" required
                                       placeholder="Ej: Dirección de Gestión Documental y Archivo">
                            </div>
                        </div>

                        <div class="section-title"><i class="bi bi-2-circle-fill"></i> PASO 2: LA CUENTA QUE SOLICITA</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre sugerido para la nueva cuenta <span class="text-danger">*</span></label>
                                <input type="text" name="cuenta_sugerida" class="form-control" required
                                       placeholder="Ej: gestion.documental@<?= htmlspecialchars($dominio) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Justificación <span class="text-danger">*</span></label>
                                <textarea name="justificacion" class="form-control" rows="3" required
                                    placeholder="Explique para qué necesita la cuenta y por qué no basta con el correo personal institucional."></textarea>
                            </div>
                        </div>

                        <div class="section-title"><i class="bi bi-3-circle-fill"></i> PASO 3: ¿QUIÉN APRUEBA?</div>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#B45309;">Su Director/a o Jefe/a de Área</h6>
                            <p class="text-muted small mb-2">Recibirá en su correo institucional un enlace para
                                aprobar la solicitud con su firma electrónica.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Apellidos y nombres <span class="text-danger">*</span></label>
                                    <input type="text" name="autorizador_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                    <input type="text" name="autorizador_cedula" class="form-control" data-solo-numeros maxlength="10" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Correo institucional <span class="text-danger">*</span></label>
                                    <input type="email" name="autorizador_correo" class="form-control" required
                                           placeholder="usuario@<?= htmlspecialchars($dominio) ?>">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="autorizador_cargo" class="form-control"
                                           placeholder="Ej: Director/a de Área">
                                </div>
                            </div>
                        </div>

                        <div class="accordion mb-3" id="accLineamientos">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLin">
                                        <i class="bi bi-exclamation-circle me-2"></i> Lineamientos de uso (constan en el documento que firmará)
                                    </button>
                                </h2>
                                <div id="collapseLin" class="accordion-collapse collapse" data-bs-parent="#accLineamientos">
                                    <div class="accordion-body consideraciones">
                                        <ul class="mb-0">
                                            <li>La cuenta será utilizada exclusivamente para tareas propias de las funciones que se desarrollan en la institución y no para ningún otro fin.</li>
                                            <li>El solicitante y su jefe inmediato son responsables tanto del contenido de los mensajes enviados como de cualquier otra información que se adjunte.</li>
                                            <li>Todos los mensajes pueden ser monitoreados y respaldados según los procedimientos de la institución.</li>
                                            <li>La nueva cuenta estará asociada en el servidor de correo a la cuenta de usuario institucional del solicitante.</li>
                                            <li>Si el solicitante se cambia de área o sale de la institución, se desactivará la cuenta creada hasta que se presente una nueva solicitud.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-shield-lock"></i> Sus datos serán tratados de forma confidencial.</small>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-pen"></i> Continuar y firmar en línea
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooterHtml($config['nombre_institucion'] ?? 'ARCONEL'); ?>
