<?php
/**
 * PDF Template: Autorizacion de Privilegios Especiales - Directorio Activo
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

$d = array_map(function($v) { return htmlspecialchars($v ?? ''); }, $datos);

// Construir tipo de privilegios
$privilegios = [];
if (!empty($datos['privilegio_admin_local'])) $privilegios[] = 'Administrador Local';
if (!empty($datos['privilegio_instalacion'])) $privilegios[] = 'Instalacion de Software';
if (!empty($datos['privilegio_otro'])) $privilegios[] = 'Otros: ' . $d['privilegio_otro_detalle'];
$privilegiosTexto = implode(', ', $privilegios) ?: 'No especificado';

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 20mm 15mm 25mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9.5pt; color: #333; line-height: 1.5; }
    .header-table { width: 100%; border-bottom: 3px solid <?= $colorPrimario ?>; padding-bottom: 8px; margin-bottom: 10px; }
    .header-logo { width: 70px; text-align: left; vertical-align: middle; }
    .header-logo img { max-height: 50px; }
    .header-center { text-align: center; vertical-align: middle; }
    .header-right { width: 70px; text-align: right; vertical-align: middle; }
    .header-right img { max-height: 50px; }
    .inst-name { font-size: 11pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .titulo { text-align: center; background: <?= $colorPrimario ?>; color: white; padding: 6px 12px; font-size: 9.5pt; font-weight: bold; margin: 10px 0; border-radius: 3px; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 10px; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9.5pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 3px; margin: 12px 0 6px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .dt td { padding: 5px 8px; border: 1px solid #ddd; font-size: 9pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .consideraciones { font-size: 8pt; text-align: justify; margin: 8px 0; padding: 8px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .consideraciones li { margin-bottom: 4px; }
    .firmas-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 5px 12px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 4px; font-size: 8pt; margin-top: 50px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #888; border-top: 2px solid <?= $colorPrimario ?>; padding-top: 4px; }
    .nota { font-size: 7.5pt; color: #666; text-align: center; margin-top: 10px; font-style: italic; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td class="header-logo"><?php if (!empty($logoDataUri)): ?><img src="<?= $logoDataUri ?>"><?php endif; ?></td>
        <td class="header-center"><div class="inst-name"><?= $institucion ?></div></td>
        <td class="header-right"><?php if (!empty($logoSecundarioDataUri)): ?><img src="<?= $logoSecundarioDataUri ?>"><?php endif; ?></td>
    </tr>
</table>

<div class="titulo">FORMULARIO DE AUTORIZACION DE PRIVILEGIOS ESPECIALES<br>DIRECTORIO ACTIVO</div>

<table class="info-bar">
    <tr><td style="text-align:left;"><strong>Fecha:</strong> <?= $fecha ?></td><td style="text-align:right;"><strong>Codigo:</strong> <span style="font-family:Courier;font-weight:bold;color:<?= $colorPrimario ?>;"><?= $codigo ?></span></td></tr>
</table>

<!-- INFORMACION GENERAL -->
<div class="seccion">Informacion General del Servidor</div>
<table class="dt">
    <tr><td class="lb">Coordinacion/Direccion/Area:</td><td><?= $d['area'] ?></td></tr>
    <tr><td class="lb">Apellidos y Nombres:</td><td><?= $d['nombre_completo'] ?></td></tr>
    <tr><td class="lb">Cedula:</td><td><?= $d['cedula'] ?></td></tr>
    <tr><td class="lb">Correo:</td><td><?= $d['correo'] ?></td></tr>
    <tr><td class="lb">Estado del empleado:</td><td><?= $d['estado_empleado'] ?></td></tr>
</table>

<!-- REQUERIMIENTO -->
<div class="seccion">Informacion del Requerimiento</div>
<table class="dt">
    <tr><td class="lb">Fecha de solicitud:</td><td><?= $fecha ?></td></tr>
    <tr><td class="lb">Tipo de Privilegios Especiales:</td><td><?= $privilegiosTexto ?></td></tr>
    <tr><td class="lb" style="vertical-align:top;">Justificacion:</td><td><?= nl2br($d['justificacion']) ?></td></tr>
</table>

<!-- CONSIDERACIONES -->
<div class="seccion">Consideraciones</div>
<div class="consideraciones">
<ul style="margin:0;padding-left:15px;">
    <li>Los privilegios asignados deben ser utilizados exclusivamente para las actividades y funciones asignadas en el marco de las facultades que son de competencia de la institucion, y para ningun otro fin.</li>
    <li>Cada servidor es responsable con los privilegios asignados de las acciones realizadas en los equipos de la institucion para conservacion de los mismos.</li>
    <li>El Coordinador/Director/Jefe de Area es responsable de la autorizacion que otorga al servidor, a traves de este formulario.</li>
    <li>A la Direccion de Tecnologias de la Informacion y Comunicacion le corresponde auditar el uso de los privilegios asignados, quedando eximida de responsabilidades que se produzca por el uso indebido de los privilegios asignados.</li>
</ul>
</div>

<!-- FIRMAS -->
<div class="seccion">Suscripciones</div>
<table class="firmas-table">
    <tr>
        <td style="height:80px;">
            <p style="font-size:8pt;text-align:justify;">Declaro conocer y hacerme cargo de los compromisos que asumo por la correcta utilizacion de los privilegios asignados como herramienta de trabajo, asi como de las implicaciones que conlleva un uso inadecuado del mismo.</p>
            <div class="firma-linea">
                <strong>Solicitado por:</strong><br>
                f. Servidor<br>
                Nombre: <?= $d['nombre_completo'] ?><br>
                C.C.: <?= $d['cedula'] ?>
            </div>
        </td>
        <td>
            <p style="font-size:8pt;text-align:justify;">Declaro conocer y hacerme cargo de los compromisos que asumo al autorizar la presente solicitud.</p>
            <div class="firma-linea">
                <strong>Autorizado por:</strong><br>
                f. Director/Coordinador/Jefe de Area<br>
                Nombre:<br>
                C.C.:
            </div>
        </td>
    </tr>
</table>

<div class="nota">Documento generado automaticamente el <?= $fecha ?> | <?= $codigo ?></div>

<div class="footer">
    <?php if (!empty($piePagina1)): ?><?= $piePagina1 ?><br><?php endif; ?>
    <?php if (!empty($piePagina2)): ?><?= $piePagina2 ?><br><?php endif; ?>
    <?php if (!empty($piePagina3)): ?><?= $piePagina3 ?><?php endif; ?>
</div>

</body>
</html>
<?php
return ob_get_clean();
