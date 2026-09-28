<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Solicitud de Acceso a Sistemas para Terceros';

$dticNombre = $config['dtic_nombre'] ?? 'Veronica Chamorro';
$dticCedula = $config['dtic_cedula'] ?? '1719366757';
$dticCargo = $config['dtic_cargo'] ?? 'Directora de Tecnologias de la Informacion y Comunicacion';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill"></i> ¿Cómo llenar este formulario?</h6>
                <ol class="mb-0 small">
                    <li>Complete los <strong>datos de la entidad solicitante</strong> (institución externa que pide el acceso).</li>
                    <li>Registre los datos de la <strong>autoridad</strong> de esa entidad y de la <strong>persona que usará el acceso</strong> (solicitante).</li>
                    <li>En <strong>información técnica</strong> indique el sistema o servicio al que necesita acceder y el rol requerido.</li>
                    <li>Complete los <strong>datos de los firmantes</strong> que autorizarán la solicitud.</li>
                    <li>Genere el PDF, fírmelo con <strong>FirmaEC</strong> y envíelo al correo <strong>soporte@arconel.gob.ec</strong>.</li>
                </ol>
            </div>

            <div class="form-card">
                <div class="card-header" style="background: #006400;">
                    <i class="bi bi-person-badge"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="/process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="2">

                        <!-- DATOS DE LA ENTIDAD SOLICITANTE -->
                        <div class="section-title"><i class="bi bi-building"></i> DATOS DE LA ENTIDAD SOLICITANTE</div>
                        <p class="text-muted small"><i class="bi bi-pencil-fill"></i> Información de la institución externa que solicita el acceso.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Institución <span class="text-danger">*</span></label>
                                <input type="text" name="institucion" class="form-control" placeholder="Ej: GAD Municipal de Quito" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RUC</label>
                                <input type="text" name="ruc_institucion" class="form-control" data-solo-numeros maxlength="13" placeholder="13 dígitos">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" name="provincia" class="form-control" placeholder="Ej: Pichincha">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cantón</label>
                                <input type="text" name="canton" class="form-control" placeholder="Ej: Quito">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Parroquia</label>
                                <input type="text" name="parroquia" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono_institucion" class="form-control" placeholder="Ej: 022345678">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion_institucion" class="form-control" placeholder="Dirección de la institución">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Casillero Judicial</label>
                                <input type="text" name="casillero_judicial" class="form-control" placeholder="Si aplica">
                            </div>
                        </div>

                        <!-- DATOS DE LA AUTORIDAD -->
                        <div class="section-title"><i class="bi bi-person-check"></i> DATOS DE LA AUTORIDAD DE LA ENTIDAD SOLICITANTE</div>
                        <p class="text-muted small"><i class="bi bi-pencil-fill"></i> Datos del representante legal o autoridad de la entidad externa. (Para los GAD's debe coincidir con los datos de la credencial emitida por el Consejo Nacional Electoral.)</p>
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
                                <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" name="autoridad_cedula" class="form-control" data-solo-numeros maxlength="13" placeholder="Solo números" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo</label>
                                <input type="text" name="autoridad_cargo" class="form-control" placeholder="Ej: Alcalde, Gerente General">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="autoridad_correo" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="autoridad_telefono" class="form-control">
                            </div>
                        </div>

                        <!-- INFORMACION DEL SOLICITANTE -->
                        <div class="section-title"><i class="bi bi-person"></i> INFORMACIÓN DEL SOLICITANTE</div>
                        <p class="text-muted small"><i class="bi bi-pencil-fill"></i> Datos de la persona que usará el acceso al sistema o servicio.</p>
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
                                    <option value="Cedula">Cédula</option>
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
                                <input type="text" name="solicitante_cargo" class="form-control" placeholder="Ej: Analista de Sistemas" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" name="correo" class="form-control" placeholder="Ej: nombre@entidad.gob.ec" required>
                                <small class="text-muted">A este correo llegará la notificación de acceso.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="solicitante_telefono" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">País</label>
                                <input type="text" name="solicitante_pais" class="form-control" value="Ecuador">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" name="solicitante_provincia" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cantón</label>
                                <input type="text" name="solicitante_canton" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Parroquia</label>
                                <input type="text" name="solicitante_parroquia" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="solicitante_direccion" class="form-control">
                            </div>
                        </div>

                        <!-- INFORMACION TECNICA -->
                        <div class="section-title"><i class="bi bi-gear"></i> INFORMACIÓN TÉCNICA</div>
                        <p class="text-muted small"><i class="bi bi-pencil-fill"></i> Indique a qué sistema necesita acceder y con qué rol o perfil.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sistema / Servicio de TI al que solicita acceso <span class="text-danger">*</span></label>
                                <input type="text" name="sistema_servicio" class="form-control" placeholder="Ej: SISDAT, SIGCON" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol del Sistema / Servicio que solicita</label>
                                <input type="text" name="rol_sistema" class="form-control" placeholder="Ej: Consulta, Administrador GAD">
                            </div>
                        </div>

                        <!-- OBLIGACIONES -->
                        <div class="section-title"><i class="bi bi-list-check"></i> OBLIGACIONES DEL SOLICITANTE</div>
                        <div class="consideraciones">
                            <ul class="mb-0">
                                <li>Suscribir el acuerdo de confidencialidad y no divulgación de información con terceros, así como entender y aceptar su contenido.</li>
                                <li>Para los usuarios Administradores GAD, gestionar y autorizar el acceso a los servicios de la ARCONEL, a los usuarios de su entidad que por su competencia y funciones sea necesario.</li>
                                <li>Gestionar oportunamente las necesidades de su entidad para el uso de los servicios de la ARCONEL.</li>
                                <li>El Representante Legal/Director/Coordinador/Jefe de Área de la entidad solicitante es responsable de la autorización que otorga al servidor, a través de este formulario.</li>
                                <li>El Coordinador/Director de Área de la ARCONEL es responsable de la autorización que otorga a la persona solicitante, a través de este formulario.</li>
                                <li>A la Dirección de Tecnologías de la Información y Comunicación - DTIC le corresponde registrar la activación y desactivación del usuario, quedando eximida de responsabilidades por el uso indebido del servicio e información que se produzca fuera de la DTIC.</li>
                            </ul>
                        </div>

                        <!-- FIRMANTES -->
                        <div class="section-title"><i class="bi bi-pen"></i> DATOS DE FIRMANTES</div>
                        <p class="text-muted small"><i class="bi bi-pencil-fill"></i> Complete los datos de las personas que firmarán el documento. El solicitante ya fue registrado arriba.</p>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#006400;">1. Director/Coordinador/Jefe de Área (Entidad Externa)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_entidad_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_entidad_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_entidad_cargo" class="form-control" placeholder="Ej: Director General">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#003366;">2. Director/Coordinador de Área funcional del Sistema (ARCONEL)</h6>
                            <p class="text-muted small mb-2">Funcionario de la ARCONEL responsable del sistema al que solicita acceso.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_area_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                    <input type="text" name="firma_area_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="firma_area_cargo" class="form-control" placeholder="Ej: Director de Regulación">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3" style="background:#f8f9fa;">
                            <h6 class="fw-bold" style="color:#003366;">3. Directora de Tecnologías de la Información y Comunicación (ARCONEL)</h6>
                            <p class="text-muted small mb-2"><i class="bi bi-lock-fill"></i> Estos datos son fijos y corresponden a la Dirección de TIC vigente. No es necesario modificarlos.</p>
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

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-info-circle"></i> La entidad solicitante deberá notificar a la ARCONEL el cambio del usuario solicitante.</small>
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
