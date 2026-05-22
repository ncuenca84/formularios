<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Solicitud de Accesos Especiales para el Servicio de Internet';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="form-card">
                <div class="card-header" style="background: #2F4F4F;">
                    <i class="bi bi-wifi"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="6">

                        <!-- INFORMACION GENERAL DEL SERVIDOR -->
                        <div class="section-title"><i class="bi bi-person"></i> INFORMACION GENERAL DEL SERVIDOR</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Coordinacion / Direccion / Area <span class="text-danger">*</span></label>
                                <input type="text" name="area" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos y Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_completo" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                        </div>

                        <!-- INFORMACION DEL REQUERIMIENTO -->
                        <div class="section-title"><i class="bi bi-clipboard-data"></i> INFORMACION DEL REQUERIMIENTO</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Acceso <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="acceso_redes_sociales" value="1" id="accRedes">
                                    <label class="form-check-label" for="accRedes">Redes Sociales</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="acceso_streaming" value="1" id="accStreaming">
                                    <label class="form-check-label" for="accStreaming">Streaming / Video</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="acceso_descargas" value="1" id="accDescargas">
                                    <label class="form-check-label" for="accDescargas">Sitios de Descargas</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="acceso_otro" value="1" id="accOtro">
                                    <label class="form-check-label" for="accOtro">Otros</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especifique (Otros)</label>
                                <input type="text" name="acceso_otro_detalle" class="form-control">
                                <div class="mt-2">
                                    <label class="form-label">Aplicacion / Direccion Web (URL)</label>
                                    <input type="text" name="url_aplicacion" class="form-control" placeholder="Ej: https://www.ejemplo.com">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Justificacion <span class="text-danger">*</span></label>
                            <textarea name="justificacion" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- CONSIDERACIONES -->
                        <div class="section-title"><i class="bi bi-exclamation-circle"></i> CONSIDERACIONES</div>
                        <div class="consideraciones">
                            <ul class="mb-0">
                                <li>El servicio de internet debe utilizarse exclusivamente para las actividades y funciones asignadas en el marco de las facultades que son de competencia de la ARCONEL, y para ningun otro fin.</li>
                                <li>Cada servidor es responsable de la informacion y contenidos a los que accede y de aquella que copia para conservacion en los equipos de la institucion.</li>
                                <li>El Coordinador/Director/Jefe de Area es responsable de la autorizacion que otorga al servidor, a traves de este formulario.</li>
                                <li>A la Unidad de Tecnologias de la Informacion le corresponde auditar el uso del servicio de Internet, quedando eximida de responsabilidades por el uso indebido del servicio e informacion que se produzca fuera de dicha area.</li>
                            </ul>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-shield-lock"></i> Los datos seran tratados de forma confidencial.</small>
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
