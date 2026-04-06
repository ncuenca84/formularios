<?php
/**
 * PDF Template: Habilitacion de Acceso VPN para Usuarios Externos
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');
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
    @page { margin: 18mm 15mm 25mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; line-height: 1.4; }
    .header-table { width: 100%; border-bottom: 3px solid <?= $colorPrimario ?>; padding-bottom: 8px; margin-bottom: 8px; }
    .header-logo { width: 70px; text-align: left; vertical-align: middle; }
    .header-logo img { max-height: 50px; }
    .header-center { text-align: center; vertical-align: middle; }
    .header-right { width: 70px; text-align: right; vertical-align: middle; }
    .header-right img { max-height: 50px; }
    .inst-name { font-size: 11pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .titulo { text-align: center; background: <?= $colorPrimario ?>; color: white; padding: 5px 12px; font-size: 9pt; font-weight: bold; margin: 8px 0; border-radius: 3px; }
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
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #888; border-top: 2px solid <?= $colorPrimario ?>; padding-top: 4px; }
    .nota { font-size: 7pt; color: #666; text-align: center; margin-top: 8px; font-style: italic; }
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

<div class="titulo">FORMULARIO DE HABILITACION DE ACCESO A LA RED INTERNA VIA VPN<br>PARA USUARIOS EXTERNOS A LA INSTITUCION</div>

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
