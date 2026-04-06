<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Habilitacion de Acceso a la Red Interna via VPN para Usuarios Externos';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="form-card">
                <div class="card-header" style="background: #4B0082;">
                    <i class="bi bi-globe"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="../process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="4">

                        <!-- INFORMACION GENERAL DEL SERVIDOR -->
                        <div class="section-title"><i class="bi bi-person"></i> INFORMACION GENERAL DEL SERVIDOR</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Institucion <span class="text-danger">*</span></label>
                                <input type="text" name="institucion" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Coordinacion / Direccion / Area <span class="text-danger">*</span></label>
                                <input type="text" name="area" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos y Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_completo" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Correo <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado del empleado <span class="text-danger">*</span></label>
                                <select name="estado_empleado" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Nombramiento">Nombramiento</option>
                                    <option value="Ocasional">Ocasional</option>
                                    <option value="Serv. Prof.">Servicios Profesionales</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especifique (si selecciono Otro)</label>
                                <input type="text" name="estado_otro" class="form-control">
                            </div>
                        </div>

                        <!-- INFORMACION DEL REQUERIMIENTO -->
                        <div class="section-title"><i class="bi bi-clipboard-data"></i> INFORMACION DEL REQUERIMIENTO</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Acceso <span class="text-danger">*</span></label>
                                <input type="text" name="tipo_acceso" class="form-control" placeholder="Ej: VPN Site-to-Site, VPN Client" required>
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
                                <li>El acceso a la red interna de ARCONEL debe utilizarse exclusivamente para las actividades y funciones asignadas en el marco de las facultades que son de competencia de ARCONEL, y para ningun otro fin.</li>
                                <li>Cada persona es responsable del manejo de la informacion a la que tiene acceso y contenidos a los que accede a traves de la VPN y de aquella informacion que copia para conservacion en los equipos de ARCONEL.</li>
                                <li>El Coordinador/Director/Jefe de Area de la institucion solicitante es responsable de la autorizacion que otorga al servidor, a traves de este formulario.</li>
                                <li>El Coordinador/Director/Jefe de Area de ARCONEL es responsable de la autorizacion que otorga a la persona solicitante, a traves de este formulario.</li>
                                <li>A la Direccion de Tecnologias de la Informacion - DTIC - le corresponde registrar la conexion y desconexion del usuario a traves de la VPN, quedando eximida de responsabilidades por el uso indebido del servicio e informacion que se produzca fuera de dicha Direccion.</li>
                            </ul>
                        </div>

                        <!-- DOCUMENTOS DE RESPALDO -->
                        <div class="section-title"><i class="bi bi-file-text"></i> DOCUMENTOS DE RESPALDO (uso interno DTIC)</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nro. de oficio o memorando de la solicitud</label>
                                <input type="text" name="nro_oficio_solicitud" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nro. de oficio o memorando de autorizacion</label>
                                <input type="text" name="nro_oficio_autorizacion" class="form-control">
                            </div>
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
