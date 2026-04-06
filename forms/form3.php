<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Solicitud de Acceso a los Sistemas Informaticos';

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-pc-display"></i> <?= $titulo ?>
                </div>
                <div class="card-body">
                    <form action="../process.php" method="POST" novalidate>
                        <input type="hidden" name="tipo_formulario" value="3">

                        <!-- 1. DATOS DEL AUTORIZADOR -->
                        <div class="section-title"><i class="bi bi-person-check"></i> 1. DATOS DEL AUTORIZADOR (Jefe Inmediato)</div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Agencia <span class="text-danger">*</span></label>
                                <select name="agencia" class="form-select" required>
                                    <option value="MATRIZ_NIVEL_CENTRAL">Matriz - Nivel Central</option>
                                    <option value="NIVEL_DESCONCENTRADO_TERRITORIAL">Nivel Desconcentrado Territorial</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Coordinacion / Direccion <span class="text-danger">*</span></label>
                                <select name="coordinacion_direccion" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Coordinacion Administrativa Financiera">A - Coordinacion Administrativa Financiera</option>
                                    <option value="Direccion Administrativa">A - Direccion Administrativa</option>
                                    <option value="Direccion de Administracion del Talento Humano">A - Direccion de Administracion del Talento Humano</option>
                                    <option value="Direccion Financiera">A - Direccion Financiera</option>
                                    <option value="Coordinacion Nacional de Control Electrico">C - Coordinacion Nacional de Control Electrico</option>
                                    <option value="Direccion Tecnica de Control de Comercializacion">C - Direccion Tecnica de Control de Comercializacion</option>
                                    <option value="Direccion Tecnica de Control de Distribucion">C - Direccion Tecnica de Control de Distribucion</option>
                                    <option value="Direccion Tecnica de Control de Generacion y Transmision">C - Direccion Tecnica de Control de Generacion y Transmision</option>
                                    <option value="Direccion Ejecutiva">Direccion Ejecutiva</option>
                                    <option value="Direccion de Gestion Documental y Archivo">G - Direccion de Gestion Documental y Archivo</option>
                                    <option value="Coordinacion de Asesoria Juridica">J - Coordinacion de Asesoria Juridica</option>
                                    <option value="Direccion de Asesoria Juridica y Patrocinio Judicial">J - Direccion de Asesoria Juridica y Patrocinio Judicial</option>
                                    <option value="Direccion de Coactivas e Infracciones">J - Direccion de Coactivas e Infracciones</option>
                                    <option value="Coordinacion Nacional Ambiental Electrica">M - Coordinacion Nacional Ambiental Electrica</option>
                                    <option value="Direccion Tecnica de Control y Seguimiento Ambiental">M - Direccion Tecnica de Control y Seguimiento Ambiental</option>
                                    <option value="Direccion Tecnica de Regularizacion Ambiental">M - Direccion Tecnica de Regularizacion Ambiental</option>
                                    <option value="Coordinacion de Planificacion y Gestion Estrategica">P - Coordinacion de Planificacion y Gestion Estrategica</option>
                                    <option value="Direccion de Planificacion, Inversion, Seguimiento y Evaluacion">P - Direccion de Planificacion, Inversion, Seguimiento y Evaluacion</option>
                                    <option value="Direccion de Procesos, Servicios, Calidad y Gestion del Cambio">P - Direccion de Procesos, Servicios, Calidad y Gestion del Cambio</option>
                                    <option value="Direccion de Tecnologias de la Informacion y Comunicacion">P - Direccion de Tecnologias de la Informacion y Comunicacion</option>
                                    <option value="Coordinacion Nacional de Regulacion Electrica">R - Coordinacion Nacional de Regulacion Electrica</option>
                                    <option value="Direccion Tecnica de Estudios, Informacion e Innovacion">R - Direccion Tecnica de Estudios, Informacion e Innovacion</option>
                                    <option value="Direccion Tecnica de Regulacion">R - Direccion Tecnica de Regulacion</option>
                                    <option value="Direccion Tecnica de Regulacion Economica y Tarifas">R - Direccion Tecnica de Regulacion Economica y Tarifas</option>
                                    <option value="Direccion de Comunicacion Social">S - Direccion de Comunicacion Social</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control" value="Quito">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del Coordinador / Director <span class="text-danger">*</span></label>
                                <input type="text" name="autorizador_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cedula del Autorizador <span class="text-danger">*</span></label>
                                <input type="text" name="autorizador_cedula" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cargo del Jefe Inmediato</label>
                                <input type="text" name="autorizador_cargo" class="form-control" value="Director/a">
                            </div>
                        </div>

                        <!-- 2. DATOS DEL SOLICITANTE -->
                        <div class="section-title"><i class="bi bi-person"></i> 2. DATOS DEL SOLICITANTE</div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre1" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Segundo Nombre</label>
                                <input type="text" name="nombre2" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido1" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Segundo Apellido</label>
                                <input type="text" name="apellido2" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cedula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" class="form-control" data-solo-numeros maxlength="13" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Correo <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="correo_usuario" class="form-control" required>
                                    <span class="input-group-text">@arconel.gob.ec</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" name="cargo" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefono y/o Extension</label>
                            <input type="text" name="telefono_extension" class="form-control">
                        </div>

                        <!-- 3. ACCESO A SISTEMAS -->
                        <div class="section-title"><i class="bi bi-hdd-network"></i> 3. ACCESO A MODULOS DE SISTEMAS</div>
                        <div class="alert alert-info small"><i class="bi bi-info-circle"></i> Nota: Todos los permisos anteriores que no se encuentren especificados en este formulario seran desactivados.</div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">SISDAT - Modulos solicitados</label>
                            <textarea name="sisdat_modulos" class="form-control" rows="2" placeholder="Ingrese los modulos separados por coma (ej: Modulo 1, Modulo 2)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">SIGCON - Modulos y Roles</label>
                            <textarea name="sigcon_modulos" class="form-control" rows="2" placeholder="Ingrese modulos y roles (ej: Modulo 1 - Rol Consulta)"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">SNISPCB</label>
                                <select name="snispcb_acceso" class="form-select">
                                    <option value="">No requiere</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Coordinacion/Direccion (si aplica SNISPCB)</label>
                                <input type="text" name="snispcb_coordinacion" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Otros Sistemas (SITRANS, SBS, Discoverer, Power BI, STC, Tarjetas Inteligentes, etc.)</label>
                            <textarea name="otros_sistemas" class="form-control" rows="3" placeholder="Detalle los sistemas adicionales y roles requeridos"></textarea>
                        </div>

                        <!-- 4. CARPETAS COMPARTIDAS -->
                        <div class="section-title"><i class="bi bi-folder"></i> 4. ACCESO A CARPETAS COMPARTIDAS / REDES</div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombre de la Carpeta 1</label>
                                <input type="text" name="carpeta1_nombre" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Permiso</label>
                                <select name="carpeta1_permiso" class="form-select">
                                    <option value="">-</option>
                                    <option value="L">Lectura (L)</option>
                                    <option value="E">Escritura (E)</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Indefinido</label>
                                <select name="carpeta1_indefinido" class="form-select">
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Desde</label>
                                <input type="date" name="carpeta1_desde" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="carpeta1_hasta" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombre de la Carpeta 2</label>
                                <input type="text" name="carpeta2_nombre" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Permiso</label>
                                <select name="carpeta2_permiso" class="form-select">
                                    <option value="">-</option>
                                    <option value="L">Lectura (L)</option>
                                    <option value="E">Escritura (E)</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Indefinido</label>
                                <select name="carpeta2_indefinido" class="form-select">
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Desde</label>
                                <input type="date" name="carpeta2_desde" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="carpeta2_hasta" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observacion</label>
                            <textarea name="observacion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-triangle"></i> La notificacion de acceso sera enviada a la direccion electronica (e-mail) del funcionario solicitante.
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
