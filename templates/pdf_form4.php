<?php
/**
 * PDF Template: Habilitacion de Acceso VPN para Usuarios Externos
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

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 15mm 30mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; line-height: 1.4; }
    .inst-name { font-size: 11pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 8px; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 10px 0 5px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .dt td { padding: 4px 8px; border: 1px solid #ddd; font-size: 8.5pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .consideraciones { font-size: 7.5pt; text-align: justify; margin: 6px 0; padding: 6px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .consideraciones li { margin-bottom: 3px; }
    .firmas-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 4px 8px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 3px; font-size: 7.5pt; margin-top: 40px; }
    .firma-header { background: #eef2f7; font-weight: 600; font-size: 8pt; color: <?= $colorPrimario ?>; padding: 4px; }
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
    <tr><td class="lb">Institucion:</td><td colspan="3"><?= $d['institucion'] ?></td></tr>
    <tr><td class="lb">Coordinacion/Direccion/Area:</td><td colspan="3"><?= $d['area'] ?></td></tr>
    <tr><td class="lb">Apellidos y Nombres:</td><td colspan="3"><?= $d['nombre_completo'] ?></td></tr>
    <tr><td class="lb">Cedula:</td><td><?= $d['cedula'] ?></td><td class="lb">Correo:</td><td><?= $d['correo'] ?></td></tr>
    <tr><td class="lb">Estado del empleado:</td><td><?= $d['estado_empleado'] ?></td><td class="lb"><?= $d['estado_empleado'] === 'Otro' ? 'Especifique:' : '' ?></td><td><?= $d['estado_otro'] ?></td></tr>
</table>

<!-- REQUERIMIENTO -->
<div class="seccion">Informacion del Requerimiento</div>
<table class="dt">
    <tr><td class="lb">Fecha de solicitud:</td><td colspan="3"><?= $fecha ?></td></tr>
    <tr><td class="lb">Tipo de Acceso:</td><td colspan="3"><?= $d['tipo_acceso'] ?></td></tr>
    <tr><td class="lb" style="vertical-align:top;">Justificacion:</td><td colspan="3"><?= nl2br($d['justificacion']) ?></td></tr>
</table>

<!-- CONSIDERACIONES -->
<div class="seccion">Consideraciones</div>
<div class="consideraciones">
<ul style="margin:0;padding-left:15px;">
    <li>El acceso a la red interna de ARCONEL debe utilizarse exclusivamente para las actividades y funciones asignadas.</li>
    <li>Cada persona es responsable del manejo de la informacion a la que tiene acceso a traves de la VPN.</li>
    <li>El Coordinador/Director/Jefe de Area de la institucion solicitante es responsable de la autorizacion.</li>
    <li>El Coordinador/Director/Jefe de Area de ARCONEL es responsable de la autorizacion que otorga.</li>
    <li>A la DTIC le corresponde registrar la conexion y desconexion del usuario, quedando eximida de responsabilidades por uso indebido.</li>
</ul>
</div>

<!-- FIRMAS -->
<div class="seccion">Suscripciones</div>
<table class="firmas-table">
    <tr><td colspan="2" class="firma-header">Suscripciones de la institucion solicitante</td></tr>
    <tr>
        <td style="height:70px;"><div class="firma-linea"><strong>Solicitado por:</strong><br>f. Solicitante<br>Nombre:<br>C.C.:<br>Fecha:</div></td>
        <td><div class="firma-linea"><strong>Autorizado por:</strong><br>f. Director/Coordinador/Jefe de Area<br>Nombre:<br>C.C.:<br>Fecha:</div></td>
    </tr>
    <tr><td colspan="2" class="firma-header">Suscripciones de ARCONEL</td></tr>
    <tr>
        <td style="height:70px;"><div class="firma-linea"><strong>Autorizado por:</strong><br>f. Director/Coordinador/Jefe de Area duena de la informacion<br>Nombre:<br>C.C.:<br>Fecha:</div></td>
        <td><div class="firma-linea"><strong>Autorizado por:</strong><br>Director de Tecnologias de la Informacion y Comunicacion<br>Nombre:<br>C.C.:<br>Fecha:</div></td>
    </tr>
</table>

<!-- DOCUMENTOS RESPALDO -->
<div class="seccion">Documentos de respaldo (uso interno de la DTIC)</div>
<table class="dt">
    <tr><td class="lb">Nro. oficio/memorando solicitud:</td><td><?= $d['nro_oficio_solicitud'] ?></td></tr>
    <tr><td class="lb">Nro. oficio/memorando autorizacion:</td><td><?= $d['nro_oficio_autorizacion'] ?></td></tr>
</table>

<div class="footer">
    <em>"Este documento es para uso exclusivo de la ARCONEL. Se prohibe su uso no autorizado".</em><br>
    GESTION GENERAL DE PLANIFICACION Y GESTION ESTRATEGICA
</div>

</body>
</html>
<?php
return ob_get_clean();
