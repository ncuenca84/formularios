<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Solicitud de Acceso a Sistemas para Terceros';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-card">
                <div class="card-header" style="background: #006400;">
                    <i class="bi bi-person-badge"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="2">

                        <!-- DATOS DE LA ENTIDAD SOLICITANTE -->
                        <div class="section-title"><i class="bi bi-building"></i> DATOS DE LA ENTIDAD SOLICITANTE</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Institucion <span class="text-danger">*</span></label>
                                <input type="text" name="institucion" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RUC</label>
                                <input type="text" name="ruc_institucion" class="form-control" data-solo-numeros maxlength="13">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" name="provincia" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Canton</label>
                                <input type="text" name="canton" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Parroquia</label>
                                <input type="text" name="parroquia" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="telefono_institucion" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Direccion</label>
                                <input type="text" name="direccion_institucion" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Casillero Judicial</label>
                                <input type="text" name="casillero_judicial" class="form-control">
                            </div>
                        </div>

                        <!-- DATOS DE LA MAXIMA AUTORIDAD -->
                        <div class="section-title"><i class="bi bi-person-check"></i> DATOS DE LA MAXIMA AUTORIDAD DE LA ENTIDAD SOLICITANTE</div>
                        <p class="text-muted small">(Para los GAD's debe coincidir con los datos de la credencial emitida por el CNE)</p>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="autoridad_nombres" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" name="autoridad_apellidos" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                <input type="text" name="autoridad_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo</label>
                                <input type="text" name="autoridad_cargo" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo electronico</label>
                                <input type="email" name="autoridad_correo" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="autoridad_telefono" class="form-control">
                            </div>
                        </div>

                        <!-- INFORMACION DEL SOLICITANTE -->
                        <div class="section-title"><i class="bi bi-person"></i> INFORMACION DEL SOLICITANTE</div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="solicitante_nombres" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" name="solicitante_apellidos" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Documento</label>
                                <select name="solicitante_tipo_doc" class="form-select">
                                    <option value="Cedula">Cedula</option>
                                    <option value="RUC">RUC</option>
                                    <option value="Pasaporte">Pasaporte</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nro. de Documento <span class="text-danger">*</span></label>
                                <input type="text" name="solicitante_nro_doc" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" name="solicitante_cargo" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo electronico <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="solicitante_telefono" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pais</label>
                                <input type="text" name="solicitante_pais" class="form-control" value="Ecuador">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" name="solicitante_provincia" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Canton</label>
                                <input type="text" name="solicitante_canton" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Parroquia</label>
                                <input type="text" name="solicitante_parroquia" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Direccion</label>
                                <input type="text" name="solicitante_direccion" class="form-control">
                            </div>
                        </div>

                        <!-- INFORMACION TECNICA -->
                        <div class="section-title"><i class="bi bi-gear"></i> INFORMACION TECNICA</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sistema / Servicio de TI al que solicita acceso <span class="text-danger">*</span></label>
                                <input type="text" name="sistema_servicio" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol del Sistema / Servicio que solicita</label>
                                <input type="text" name="rol_sistema" class="form-control">
                            </div>
                        </div>

                        <!-- OBLIGACIONES -->
                        <div class="section-title"><i class="bi bi-list-check"></i> OBLIGACIONES DEL SOLICITANTE</div>
                        <div class="consideraciones">
                            <ul class="mb-0">
                                <li>Suscribir el acuerdo de confidencialidad y no divulgacion de informacion con terceros, asi como entender y aceptar su contenido.</li>
                                <li>Para los usuarios Administradores GAD, gestionar y autorizar el acceso a los servicios de la ARCONEL, a los usuarios de su entidad que por su competencia y funciones sea necesario.</li>
                                <li>Gestionar oportunamente las necesidades de su entidad para el uso de los servicios de la ARCONEL.</li>
                                <li>El Representante Legal/Director/Coordinador/Jefe de Area de la entidad solicitante es responsable de la autorizacion que otorga al servidor, a traves de este formulario.</li>
                                <li>El Coordinador/Director de Area de la ARCONEL es responsable de la autorizacion que otorga a la persona solicitante, a traves de este formulario.</li>
                                <li>A la Direccion de Tecnologias de la Informacion y Comunicacion - DTIC le corresponde registrar la activacion y desactivacion del usuario, quedando eximida de responsabilidades por el uso indebido del servicio e informacion que se produzca fuera de la DTIC.</li>
                            </ul>
                        </div>

                        <!-- SUSCRIPCIONES / FIRMANTES -->
                        <div class="section-title"><i class="bi bi-pen"></i> DATOS DE FIRMANTES</div>

                        <p class="text-muted small mb-3">Complete los datos de las 4 personas que firmaran el documento.</p>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#006400;">1. Director/Coordinador/Jefe de Area de la entidad solicitante</h6>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_entidad_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_entidad_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_entidad_cargo" class="form-control" placeholder="Ej: Director General">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#003366;">2. Director/Coordinador de Area funcional del Sistema (ARCONEL)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_area_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_area_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_area_cargo" class="form-control" placeholder="Ej: Director de Regulacion">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#003366;">3. Director de Tecnologias de la Informacion y Comunicacion (ARCONEL)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_dtic_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_dtic_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_dtic_cargo" class="form-control" value="Director de TIC">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-info-circle"></i> La entidad solicitante debera notificar cambios de usuario.</small>
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
