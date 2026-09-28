<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Autorización de Salida de Equipo de Cómputo';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill"></i> ¿Qué es este formulario y cómo llenarlo?</h6>
                <p class="small mb-2">Use este formulario cuando necesite <strong>sacar un equipo de cómputo de las instalaciones de la ARCONEL</strong> (por teletrabajo, comisión de servicios u otra necesidad institucional). Recuerde que debe contar con la autorización de su jefe inmediato <strong>antes</strong> de retirar el equipo.</p>
                <ol class="mb-0 small">
                    <li><strong>Paso 1:</strong> Escriba sus datos personales.</li>
                    <li><strong>Paso 2:</strong> Registre los datos del equipo que va a sacar (los encuentra en el acta de entrega o en la etiqueta del equipo).</li>
                    <li><strong>Paso 3:</strong> Indique el motivo de la salida y las fechas.</li>
                    <li><strong>Paso 4:</strong> Escriba los datos de su jefe inmediato (quien autoriza la salida).</li>
                    <li><strong>Paso 5:</strong> Genere el PDF, fírmelo con <strong>FirmaEC</strong> junto con su jefe inmediato y envíelo a <strong>soporte@arconel.gob.ec</strong> antes de retirar el equipo.</li>
                </ol>
                <p class="small mb-0 mt-2 text-muted">Los campos marcados con <span class="text-danger">*</span> son obligatorios. Los demás son opcionales.</p>
            </div>

            <div class="form-card">
                <div class="card-header" style="background: #155724;">
                    <i class="bi bi-laptop"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="7">

                        <!-- PASO 1: DATOS DEL USUARIO -->
                        <div class="section-title"><i class="bi bi-1-circle-fill"></i> PASO 1: SUS DATOS</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos y Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_completo" class="form-control" placeholder="Ej: IZA CANDO HUGO FERNANDO" required>
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
                                <label class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" name="cargo" class="form-control" placeholder="Ej: Analista de Talento Humano" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Coordinación / Dirección <span class="text-danger">*</span></label>
                                <input type="text" name="area" class="form-control" placeholder="Ej: Dirección de Administración del Talento Humano" required>
                            </div>
                        </div>

                        <!-- PASO 2: DATOS DEL EQUIPO -->
                        <div class="section-title"><i class="bi bi-2-circle-fill"></i> PASO 2: DATOS DEL EQUIPO QUE VA A SACAR</div>
                        <p class="text-muted small">Estos datos constan en el acta de entrega del equipo o en la etiqueta adherida al mismo. Si tiene dudas, consulte a la DTIC.</p>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de equipo <span class="text-danger">*</span></label>
                                <select name="equipo_tipo" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Computador portátil">Computador portátil (laptop)</option>
                                    <option value="Computador de escritorio">Computador de escritorio</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Proyector">Proyector</option>
                                    <option value="Impresora">Impresora</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca <span class="text-danger">*</span></label>
                                <input type="text" name="equipo_marca" class="form-control" placeholder="Ej: DELL" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="equipo_modelo" class="form-control" placeholder="Ej: Latitude 5420 Touch">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número de serie <span class="text-danger">*</span></label>
                                <input type="text" name="equipo_serie" class="form-control" placeholder="Ej: 31X5HK3" required>
                                <small class="text-muted">Consta en la etiqueta del equipo.</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">¿Lleva cargador?</label>
                                <select name="equipo_cargador" class="form-select">
                                    <option value="Sí">Sí</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Otros accesorios</label>
                                <input type="text" name="equipo_accesorios" class="form-control" placeholder="Ej: mouse, maletín (opcional)">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones sobre el estado del equipo</label>
                            <input type="text" name="equipo_observaciones" class="form-control" placeholder="Ej: equipo en buen estado, sin novedades (opcional)">
                        </div>

                        <!-- PASO 3: MOTIVO Y FECHAS -->
                        <div class="section-title"><i class="bi bi-3-circle-fill"></i> PASO 3: MOTIVO Y TIEMPO DE LA SALIDA</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Motivo de la salida <span class="text-danger">*</span></label>
                                <select name="motivo_salida" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Teletrabajo">Teletrabajo</option>
                                    <option value="Comisión de servicios">Comisión de servicios</option>
                                    <option value="Necesidades operativas">Necesidades operativas</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Si seleccionó "Otro", especifique</label>
                                <input type="text" name="motivo_otro" class="form-control" placeholder="opcional">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de salida <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_salida" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha estimada de retorno</label>
                                <input type="date" name="fecha_retorno" class="form-control">
                                <small class="text-muted">Deje en blanco si es indefinida (ej: teletrabajo permanente).</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Lugar donde estará el equipo</label>
                                <input type="text" name="lugar_destino" class="form-control" placeholder="Ej: domicilio, ciudad de comisión (opcional)">
                            </div>
                        </div>

                        <!-- PASO 4: AUTORIZACION -->
                        <div class="section-title"><i class="bi bi-4-circle-fill"></i> PASO 4: ¿QUIÉN AUTORIZA LA SALIDA?</div>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#155724;">Su jefe inmediato <span class="text-danger">*</span></h6>
                            <p class="text-muted small mb-2">Director, Coordinador o Jefe de Área que autoriza la salida del equipo. Deberá firmar el documento junto con usted.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" name="autorizador_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                    <input type="text" name="autorizador_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="autorizador_cargo" class="form-control" placeholder="opcional">
                                </div>
                            </div>
                        </div>

                        <!-- COMPROMISOS COLAPSADOS -->
                        <div class="accordion mb-3" id="accCompromisos">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComp">
                                        <i class="bi bi-exclamation-circle me-2"></i> Compromisos del usuario (léalos antes de enviar)
                                    </button>
                                </h2>
                                <div id="collapseComp" class="accordion-collapse collapse" data-bs-parent="#accCompromisos">
                                    <div class="accordion-body consideraciones">
                                        <ul class="mb-0">
                                            <li>Utilizar el equipo exclusivamente para el cumplimiento de sus actividades institucionales.</li>
                                            <li>Mantener el equipo bajo condiciones adecuadas de cuidado, custodia y conservación, evitando acciones que puedan ocasionar daño, pérdida o afectación a su funcionamiento.</li>
                                            <li>No dejar el equipo desatendido en vehículos, lugares públicos o zonas vulnerables que representen un riesgo previsible de pérdida, hurto, robo o daño.</li>
                                            <li>Cumplir las políticas institucionales de seguridad de la información, buenas prácticas informáticas y normativas sobre confidencialidad y protección de datos vigentes en la ARCONEL.</li>
                                            <li>No abrir, reparar, modificar el hardware ni alterar sellos de garantía. Toda atención técnica deberá canalizarse exclusivamente a través de la DTIC.</li>
                                            <li>En caso de falla o daño, reportar inmediatamente la novedad a la DTIC mediante la Mesa de Ayuda (soporte@arconel.gob.ec).</li>
                                            <li>En caso de robo o asalto, notificar de forma inmediata a la DTIC y a su jefe inmediato, y presentar la denuncia ante la Fiscalía General del Estado o autoridad competente dentro de los plazos legales.</li>
                                            <li>Devolver el equipo y la totalidad de sus accesorios a la DTIC al finalizar el período de salida, en condiciones equivalentes a las registradas, salvo el desgaste normal.</li>
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
