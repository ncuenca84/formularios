<?php
/**
 * PDF Template: Autorizacion de Privilegios Especiales - Directorio Activo
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');
$encabezadoInstitucional = renderPdfEncabezadoInstitucional($config, $meta, $logoDataUri, $logoSecundarioDataUri);
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
    @page { margin: 20mm 15mm 30mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9.5pt; color: #333; line-height: 1.5; }
    .inst-name { font-size: 11pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
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
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #666; border-top: 1px solid #999; padding-top: 4px; line-height: 1.4; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

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

<div class="footer">
    <em>"Este documento es para uso exclusivo de la ARCONEL. Se prohibe su uso no autorizado".</em><br>
    GESTION GENERAL DE PLANIFICACION Y GESTION ESTRATEGICA
</div>

</body>
</html>
<?php
return ob_get_clean();
