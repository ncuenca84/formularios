<?php
/**
 * Formulario 7 — Autorizacion de salida de equipo de computo EN ARRENDAMIENTO.
 * Nuevo flujo (Fase 3): el equipo se toma del inventario (cedula + codigo),
 * y la autorizacion se firma EN LINEA: primero el custodio (al enviar este
 * formulario) y luego su jefe inmediato (recibe el enlace por correo).
 */
require_once __DIR__ . '/../includes/eqr/publico.php';
require_once __DIR__ . '/../includes/layout.php';

$config = getAllConfig();
$colorPrimario = $config['color_primario'] ?? '#003366';
$titulo = 'Autorización de Salida de Equipo de Cómputo';
$dominio = getConfig('eqr_dominio_correo', 'arconel.gob.ec');

renderHeadHtml($titulo, $colorPrimario);
renderHeader($config, $titulo);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill"></i> ¿Qué es este formulario y cómo funciona?</h6>
                <p class="small mb-2">Use este formulario cuando necesite <strong>sacar su equipo de cómputo en
                arrendamiento</strong> de las instalaciones de la ARCONEL (teletrabajo, comisión de servicios u
                otra necesidad institucional). <strong>Todo se firma en línea</strong>, sin imprimir nada:</p>
                <ol class="mb-0 small">
                    <li><strong>Paso 1:</strong> Escriba su cédula y el código de su equipo (ej.: REN-0012, consta
                        en la etiqueta del equipo o en su acta) y pulse <strong>Verificar</strong>.</li>
                    <li><strong>Paso 2:</strong> Indique el motivo, las fechas y quién es su jefe inmediato.</li>
                    <li><strong>Paso 3:</strong> Al enviar, el sistema le mostrará el documento para que lo
                        <strong>firme ahí mismo con su archivo de firma electrónica (.p12)</strong>.</li>
                    <li><strong>Paso 4:</strong> Su jefe inmediato recibirá un enlace por correo para autorizar con
                        su firma. Cuando ambos firmen, la autorización queda lista y les llega por correo.</li>
                </ol>
            </div>

            <div class="form-card">
                <div class="card-header" style="background: #155724;">
                    <i class="bi bi-laptop"></i> <?= htmlspecialchars($titulo) ?>
                </div>
                <div class="card-body">
                    <form action="/eqr_salida_crear.php" method="POST" id="formSalida" novalidate>
                        <?= eqrPubCsrfCampo() ?>

                        <!-- PASO 1: IDENTIFICACION -->
                        <div class="section-title"><i class="bi bi-1-circle-fill"></i> PASO 1: IDENTIFIQUE SU EQUIPO</div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Su cédula <span class="text-danger">*</span></label>
                                <input type="text" name="cedula" id="cedula" class="form-control" data-solo-numeros
                                       maxlength="10" placeholder="Solo números" required autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código del equipo <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" id="codigo" class="form-control"
                                       placeholder="Ej: REN-0012" required autocomplete="off">
                                <small class="text-muted">Consta en la etiqueta del equipo y en su acta.</small>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="button" id="btnVerificar" class="btn btn-success w-100 mb-1">
                                    <i class="bi bi-search"></i> Verificar</button>
                            </div>
                        </div>
                        <div id="msgVerificar" class="alert alert-warning small" style="display:none"></div>
                        <div id="equipoCard" class="alert alert-success small" style="display:none"></div>

                        <!-- RESTO DEL FORMULARIO (se habilita tras verificar) -->
                        <div id="restoForm" style="display:none">

                        <div class="section-title"><i class="bi bi-2-circle-fill"></i> PASO 2: MOTIVO Y TIEMPO DE LA SALIDA</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Motivo de la salida <span class="text-danger">*</span></label>
                                <select name="salida_motivo" id="salida_motivo" class="form-select" required>
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
                                <input type="date" name="salida_fecha" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha estimada de retorno</label>
                                <input type="date" name="salida_fecha_retorno" class="form-control">
                                <small class="text-muted">Deje en blanco si es indefinida (ej.: teletrabajo permanente).</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Lugar donde estará el equipo</label>
                                <input type="text" name="salida_lugar" class="form-control"
                                       placeholder="Ej: domicilio, ciudad de comisión (opcional)">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Accesorios que lleva</label>
                                <input type="text" name="accesorios" id="accesorios" class="form-control"
                                       placeholder="Ej: cargador, mouse, maletín (opcional)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Observaciones del estado del equipo</label>
                                <input type="text" name="observaciones" class="form-control"
                                       placeholder="Ej: en buen estado, sin novedades (opcional)">
                            </div>
                        </div>

                        <div class="section-title"><i class="bi bi-3-circle-fill"></i> PASO 3: ¿QUIÉN AUTORIZA LA SALIDA?</div>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="fw-bold" style="color:#155724;">Su jefe inmediato</h6>
                            <p class="text-muted small mb-2">Director, Coordinador o Jefe de Área. Recibirá en su
                            correo institucional un enlace para <strong>autorizar la salida con su firma
                            electrónica</strong>.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Apellidos y nombres <span class="text-danger">*</span></label>
                                    <input type="text" name="jefe_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                                    <input type="text" name="jefe_cedula" class="form-control" data-solo-numeros
                                           maxlength="10" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Correo institucional <span class="text-danger">*</span></label>
                                    <input type="email" name="jefe_correo" class="form-control"
                                           placeholder="usuario@<?= htmlspecialchars($dominio) ?>" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="jefe_cargo" class="form-control" placeholder="opcional">
                                </div>
                            </div>
                        </div>

                        <!-- COMPROMISOS -->
                        <div class="accordion mb-3" id="accCompromisos">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComp">
                                        <i class="bi bi-exclamation-circle me-2"></i> Compromisos del usuario (constan en el documento que firmará)
                                    </button>
                                </h2>
                                <div id="collapseComp" class="accordion-collapse collapse" data-bs-parent="#accCompromisos">
                                    <div class="accordion-body consideraciones">
                                        <ul class="mb-0">
                                            <li>Utilizar el equipo exclusivamente para el cumplimiento de sus actividades institucionales.</li>
                                            <li>Mantener el equipo bajo condiciones adecuadas de cuidado, custodia y conservación.</li>
                                            <li>No dejar el equipo desatendido en vehículos, lugares públicos o zonas vulnerables.</li>
                                            <li>Cumplir las políticas institucionales de seguridad de la información.</li>
                                            <li>No abrir, reparar ni modificar el hardware; toda atención técnica va por la DTIC.</li>
                                            <li>Reportar fallas o daños de inmediato a la Mesa de Ayuda (soporte@arconel.gob.ec).</li>
                                            <li>En caso de robo o asalto, notificar de inmediato a la DTIC y a su jefe, y presentar la denuncia ante la autoridad competente.</li>
                                            <li>Devolver el equipo y sus accesorios a la DTIC al finalizar el período de salida.</li>
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

                        </div><!-- /restoForm -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var csrf = document.querySelector('#formSalida input[name="csrf"]').value;
    var btn = document.getElementById('btnVerificar');
    var msg = document.getElementById('msgVerificar');
    var card = document.getElementById('equipoCard');
    var resto = document.getElementById('restoForm');

    function aviso(texto, tipo) {
        msg.className = 'alert alert-' + tipo + ' small';
        msg.textContent = texto;
        msg.style.display = 'block';
    }

    btn.addEventListener('click', function () {
        var cedula = document.getElementById('cedula').value.replace(/\D/g, '');
        var codigo = document.getElementById('codigo').value.trim();
        if (cedula.length < 9 || !codigo) {
            aviso('Escriba su cédula y el código del equipo.', 'warning');
            return;
        }
        btn.disabled = true;
        aviso('Verificando…', 'secondary');
        card.style.display = 'none';
        resto.style.display = 'none';
        fetch('/eqr_salida_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf: csrf, cedula: cedula, codigo: codigo })
        }).then(function (r) { return r.json(); }).then(function (d) {
            btn.disabled = false;
            if (!d.ok) { aviso(d.mensaje || 'No se pudo verificar.', 'warning'); return; }
            if (d.problema) { aviso(d.problema, 'warning'); return; }
            msg.style.display = 'none';
            var eq = d.equipo;
            card.innerHTML = '<i class="bi bi-check-circle-fill"></i> <strong>Equipo verificado:</strong> '
                + eq.codigo + ' — ' + eq.marca + ' ' + eq.modelo + ' (serie ' + eq.serie + '), a cargo de '
                + eq.custodio + '.';
            card.style.display = 'block';
            var acc = document.getElementById('accesorios');
            if (eq.cargador && !acc.value) { acc.value = 'Cargador'; }
            resto.style.display = 'block';
        }).catch(function () {
            btn.disabled = false;
            aviso('Error de conexión. Intente de nuevo.', 'warning');
        });
    });

    document.getElementById('formSalida').addEventListener('submit', function (ev) {
        if (resto.style.display === 'none') {
            ev.preventDefault();
            aviso('Primero verifique su equipo (Paso 1).', 'warning');
        }
    });
})();
</script>

<?php renderFooterHtml($config['nombre_institucion'] ?? 'ARCONEL'); ?>
