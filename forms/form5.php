<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Autorizacion de Privilegios Especiales - Directorio Activo';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="form-card">
                <div class="card-header" style="background: #B8860B;">
                    <i class="bi bi-key"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="../process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="5">

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
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado del empleado <span class="text-danger">*</span></label>
                                <select name="estado_empleado" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Nombramiento">Nombramiento</option>
                                    <option value="Ocasional">Ocasional</option>
                                    <option value="Serv. Prof.">Servicios Profesionales</option>
                                    <option value="Temporal">Temporal</option>
                                </select>
                            </div>
                        </div>

                        <!-- INFORMACION DEL REQUERIMIENTO -->
                        <div class="section-title"><i class="bi bi-clipboard-data"></i> INFORMACION DEL REQUERIMIENTO</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Privilegios Especiales <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="privilegio_admin_local" value="1" id="privAdminLocal">
                                    <label class="form-check-label" for="privAdminLocal">Administrador Local</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="privilegio_instalacion" value="1" id="privInstalacion">
                                    <label class="form-check-label" for="privInstalacion">Instalacion de Software</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="privilegio_otro" value="1" id="privOtro">
                                    <label class="form-check-label" for="privOtro">Otros</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especifique (Otros) / Aplicacion</label>
                                <input type="text" name="privilegio_otro_detalle" class="form-control" placeholder="Ej: Instalacion de Visual Studio Code">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Justificacion para otorgar el acceso <span class="text-danger">*</span></label>
                            <textarea name="justificacion" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- CONSIDERACIONES -->
                        <div class="section-title"><i class="bi bi-exclamation-circle"></i> CONSIDERACIONES</div>
                        <div class="consideraciones">
                            <ul class="mb-0">
                                <li>Los privilegios asignados deben ser utilizados exclusivamente para las actividades y funciones asignadas en el marco de las facultades que son de competencia de la institucion, y para ningun otro fin.</li>
                                <li>Cada servidor es responsable con los privilegios asignados de las acciones realizadas en los equipos de la institucion para conservacion de los mismos.</li>
                                <li>El Coordinador/Director/Jefe de Area es responsable de la autorizacion que otorga al servidor, a traves de este formulario.</li>
                                <li>A la Direccion de Tecnologias de la Informacion y Comunicacion le corresponde auditar el uso de los privilegios asignados, quedando eximida de responsabilidades que se produzca por el uso indebido de los privilegios asignados.</li>
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
