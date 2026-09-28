<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Habilitación de Acceso a la Red Interna vía VPN para Usuarios Externos';

$dticNombre = $config['dtic_nombre'] ?? 'Veronica Chamorro';
$dticCedula = $config['dtic_cedula'] ?? '1719366757';
$dticCargo = $config['dtic_cargo'] ?? 'Directora de Tecnologias de la Informacion y Comunicacion';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill"></i> ¿Qué es este formulario y cómo llenarlo?</h6>
                <p class="small mb-2">Use este formulario si usted <strong>no trabaja en la ARCONEL</strong> pero necesita conectarse a su red interna (VPN) para realizar un trabajo autorizado.</p>
                <ol class="mb-0 small">
                    <li><strong>Paso 1:</strong> Escriba sus datos personales y los de su institución.</li>
                    <li><strong>Paso 2:</strong> Explique con sus palabras para qué necesita el acceso.</li>
                    <li><strong>Paso 3:</strong> Escriba el nombre de su jefe (quien aprueba su pedido) y, si lo conoce, el del funcionario de la ARCONEL responsable.</li>
                    <li><strong>Paso 4:</strong> Presione "Generar Solicitud PDF", descargue el documento, fírmelo con <strong>FirmaEC</strong> junto con las personas indicadas y envíelo a <strong>soporte@arconel.gob.ec</strong> solicitando la activación del acceso.</li>
                </ol>
                <p class="small mb-0 mt-2 text-muted">Los campos marcados con <span class="text-danger">*</span> son obligatorios. Los demás son opcionales.</p>
            </div>

            <div class="form-card">
                <div class="card-header" style="background: #4B0082;">
                    <i class="bi bi-globe"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="4">

                        <!-- PASO 1: DATOS DEL SOLICITANTE -->
                        <div class="section-title"><i class="bi bi-1-circle-fill"></i> PASO 1: SUS DATOS</div>
                        <p class="text-muted small">Información sobre usted y la institución donde trabaja.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Institución donde trabaja <span class="text-danger">*</span></label>
                                <input type="text" name="institucion" class="form-control" placeholder="Ej: Ministerio de Energía y Minas" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Área o departamento <span class="text-danger">*</span></label>
                                <input type="text" name="area" class="form-control" placeholder="Ej: Dirección de Sistemas" required>
                                <small class="text-muted">Coordinación, Dirección o Área a la que pertenece.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos y Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_completo" class="form-control" placeholder="Ej: PÉREZ LÓPEZ JUAN CARLOS" required>
                                <small class="text-muted">Tal como constan en su cédula.</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" data-solo-numeros maxlength="13" placeholder="Solo números" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="correo" class="form-control" placeholder="opcional">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de relación laboral <span class="text-danger">*</span></label>
                                <select name="estado_empleado" class="form-select" required>
                                    <option value="">Seleccione una opción...</option>
                                    <option value="Nombramiento">Nombramiento (personal permanente)</option>
                                    <option value="Ocasional">Contrato ocasional</option>
                                    <option value="Serv. Prof.">Servicios profesionales (consultor/contratista)</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Si seleccionó "Otro", especifique</label>
                                <input type="text" name="estado_otro" class="form-control" placeholder="opcional">
                            </div>
                        </div>

                        <!-- PASO 2: QUE NECESITA -->
                        <div class="section-title"><i class="bi bi-2-circle-fill"></i> PASO 2: ¿QUÉ ACCESO NECESITA Y PARA QUÉ?</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de acceso <span class="text-danger">*</span></label>
                                <select name="tipo_acceso" class="form-select" required>
                                    <option value="">Seleccione una opción...</option>
                                    <option value="VPN Cliente (conexion desde mi computador)">VPN Cliente (conexión desde mi computador)</option>
                                    <option value="VPN Sitio a Sitio (conexion entre instituciones)">VPN Sitio a Sitio (conexión entre instituciones)</option>
                                    <option value="No estoy seguro - requiere asesoria de la DTIC">No estoy seguro (la DTIC me asesorará)</option>
                                </select>
                                <small class="text-muted">Si no sabe cuál elegir, seleccione "No estoy seguro".</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">¿Para qué necesita el acceso? (justificación) <span class="text-danger">*</span></label>
                            <textarea name="justificacion" class="form-control" rows="3" placeholder="Explique con sus palabras. Ej: Necesito conectarme al sistema SISDAT para cargar la información mensual de mi empresa distribuidora." required></textarea>
                        </div>

                        <!-- PASO 3: QUIENES AUTORIZAN -->
                        <div class="section-title"><i class="bi bi-3-circle-fill"></i> PASO 3: ¿QUIÉNES AUTORIZAN SU SOLICITUD?</div>
                        <p class="text-muted small">Estas personas deberán firmar el documento junto con usted.</p>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#4B0082;">Su jefe inmediato (de su institución) <span class="text-danger">*</span></h6>
                            <p class="text-muted small mb-2">Director, Coordinador o Jefe de Área de su institución que aprueba este pedido.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_inst_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_inst_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_inst_cargo" class="form-control" placeholder="opcional">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#003366;">Funcionario de la ARCONEL responsable de la información <span class="badge bg-secondary">opcional</span></h6>
                            <p class="text-muted small mb-2">Si conoce al Director o Jefe de Área de la ARCONEL con quien coordina su trabajo, escriba sus datos. Si no lo conoce, deje en blanco y la DTIC lo completará.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre completo</label>
                                    <input type="text" name="firma_area_nombre" class="form-control" placeholder="opcional">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cédula</label>
                                    <input type="text" name="firma_area_cedula" class="form-control" data-solo-numeros maxlength="13" placeholder="opcional">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_area_cargo" class="form-control" placeholder="opcional">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3" style="background:#f8f9fa;">
                            <h6 class="fw-bold" style="color:#003366;">Dirección de Tecnologías de la Información y Comunicación (ARCONEL)</h6>
                            <p class="text-muted small mb-2"><i class="bi bi-lock-fill"></i> Estos datos ya están completos. No necesita modificarlos.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="firma_dtic_nombre" class="form-control" value="<?= htmlspecialchars($dticNombre) ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cédula</label>
                                    <input type="text" name="firma_dtic_cedula" class="form-control" value="<?= htmlspecialchars($dticCedula) ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_dtic_cargo" class="form-control" value="<?= htmlspecialchars($dticCargo) ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- INFO ADICIONAL COLAPSADA -->
                        <div class="accordion mb-3" id="accInfoAdicional">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRespaldo">
                                        <i class="bi bi-paperclip me-2"></i> Datos adicionales (opcional — uso interno de la DTIC)
                                    </button>
                                </h2>
                                <div id="collapseRespaldo" class="accordion-collapse collapse" data-bs-parent="#accInfoAdicional">
                                    <div class="accordion-body">
                                        <p class="text-muted small">Complete solo si su solicitud ya tiene un oficio o memorando asociado. Si no lo tiene, deje en blanco.</p>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Nro. de oficio o memorando de la solicitud</label>
                                                <input type="text" name="nro_oficio_solicitud" class="form-control" placeholder="opcional">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Nro. de oficio o memorando de la autorización</label>
                                                <input type="text" name="nro_oficio_autorizacion" class="form-control" placeholder="opcional">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CONSIDERACIONES COLAPSADAS -->
                        <div class="accordion mb-3" id="accConsideraciones">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseConsider">
                                        <i class="bi bi-exclamation-circle me-2"></i> Consideraciones importantes (léalas antes de enviar)
                                    </button>
                                </h2>
                                <div id="collapseConsider" class="accordion-collapse collapse" data-bs-parent="#accConsideraciones">
                                    <div class="accordion-body consideraciones">
                                        <ul class="mb-0">
                                            <li>El acceso a la red interna de ARCONEL debe utilizarse exclusivamente para las actividades y funciones asignadas en el marco de las facultades que son de competencia de ARCONEL, y para ningún otro fin.</li>
                                            <li>Cada persona es responsable del manejo de la información a la que tiene acceso y contenidos a los que accede a través de la VPN y de aquella información que copia para conservación en los equipos de ARCONEL.</li>
                                            <li>El Coordinador/Director/Jefe de Área de la institución solicitante es responsable de la autorización que otorga al servidor, a través de este formulario.</li>
                                            <li>El Coordinador/Director/Jefe de Área de ARCONEL es responsable de la autorización que otorga a la persona solicitante, a través de este formulario.</li>
                                            <li>A la Dirección de Tecnologías de la Información - DTIC - le corresponde registrar la conexión y desconexión del usuario a través de la VPN, quedando eximida de responsabilidades por el uso indebido del servicio e información que se produzca fuera de dicha Dirección.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-shield-lock"></i> Sus datos serán tratados de forma confidencial.</small>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-pdf"></i> Generar Solicitud PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooterHtml($config['nombre_institucion'] ?? 'ARCONEL'); ?>
