<?php
/**
 * PDF Template: Solicitud de Acceso a Sistemas para Terceros
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
    body { font-family: 'Helvetica', sans-serif; font-size: 9.5pt; color: #333; line-height: 1.4; }
    .inst-name { font-size: 11pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 8px; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 10px 0 5px; }
    .datos-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .datos-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 8.5pt; }
    .datos-table .label { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 25%; }
    .obligaciones { font-size: 8pt; text-align: justify; margin: 8px 0; padding: 6px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .obligaciones li { margin-bottom: 3px; }
    .firmas-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 5px 10px; border: 1px solid #ddd; }
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

<!-- DATOS ENTIDAD -->
<div class="seccion">Datos de la entidad solicitante</div>
<table class="datos-table">
    <tr><td class="label">Institucion:</td><td colspan="3"><?= $d['institucion'] ?></td></tr>
    <tr><td class="label">RUC:</td><td><?= $d['ruc_institucion'] ?></td><td class="label">Telefono:</td><td><?= $d['telefono_institucion'] ?></td></tr>
    <tr><td class="label">Provincia:</td><td><?= $d['provincia'] ?></td><td class="label">Canton:</td><td><?= $d['canton'] ?></td></tr>
    <tr><td class="label">Parroquia:</td><td><?= $d['parroquia'] ?></td><td class="label">Casillero Judicial:</td><td><?= $d['casillero_judicial'] ?></td></tr>
    <tr><td class="label">Direccion:</td><td colspan="3"><?= $d['direccion_institucion'] ?></td></tr>
</table>

<!-- MAXIMA AUTORIDAD -->
<div class="seccion">Datos de la maxima autoridad de la entidad solicitante</div>
<table class="datos-table">
    <tr><td class="label">Nombres:</td><td><?= $d['autoridad_nombres'] ?></td><td class="label">Apellidos:</td><td><?= $d['autoridad_apellidos'] ?></td></tr>
    <tr><td class="label">Cedula:</td><td><?= $d['autoridad_cedula'] ?></td><td class="label">Cargo:</td><td><?= $d['autoridad_cargo'] ?></td></tr>
    <tr><td class="label">Correo:</td><td><?= $d['autoridad_correo'] ?></td><td class="label">Telefono:</td><td><?= $d['autoridad_telefono'] ?></td></tr>
</table>

<!-- SOLICITANTE -->
<div class="seccion">Informacion del solicitante</div>
<table class="datos-table">
    <tr><td class="label">Nombres:</td><td><?= $d['solicitante_nombres'] ?></td><td class="label">Apellidos:</td><td><?= $d['solicitante_apellidos'] ?></td></tr>
    <tr><td class="label">Tipo Doc:</td><td><?= $d['solicitante_tipo_doc'] ?></td><td class="label">Nro. Doc:</td><td><?= $d['solicitante_nro_doc'] ?></td></tr>
    <tr><td class="label">Cargo:</td><td><?= $d['solicitante_cargo'] ?></td><td class="label">Correo:</td><td><?= $d['correo'] ?></td></tr>
    <tr><td class="label">Telefono:</td><td><?= $d['solicitante_telefono'] ?></td><td class="label">Pais:</td><td><?= $d['solicitante_pais'] ?></td></tr>
    <tr><td class="label">Provincia:</td><td><?= $d['solicitante_provincia'] ?></td><td class="label">Canton:</td><td><?= $d['solicitante_canton'] ?></td></tr>
    <tr><td class="label">Parroquia:</td><td><?= $d['solicitante_parroquia'] ?></td><td class="label">Direccion:</td><td><?= $d['solicitante_direccion'] ?></td></tr>
</table>

<!-- INFORMACION TECNICA -->
<div class="seccion">Informacion tecnica</div>
<table class="datos-table">
    <tr><td class="label">Sistema/Servicio:</td><td colspan="3"><?= $d['sistema_servicio'] ?></td></tr>
    <tr><td class="label">Rol solicitado:</td><td colspan="3"><?= $d['rol_sistema'] ?></td></tr>
</table>

<!-- OBLIGACIONES -->
<div class="seccion">Obligaciones del solicitante</div>
<div class="obligaciones">
<ul style="margin:0;padding-left:15px;">
    <li>Suscribir el acuerdo de confidencialidad y no divulgacion de informacion con terceros.</li>
    <li>Gestionar oportunamente las necesidades de su entidad para el uso de los servicios de la ARCONEL.</li>
    <li>El Representante Legal de la entidad solicitante es responsable de la autorizacion que otorga al servidor.</li>
    <li>A la DTIC le corresponde registrar la activacion y desactivacion del usuario.</li>
</ul>
</div>

<!-- FIRMAS (4 secciones) -->
<div class="seccion">Suscripciones</div>
<table class="firmas-table">
    <tr><td colspan="2" class="firma-header">Suscripciones de la entidad solicitante</td></tr>
    <tr>
        <td style="height:80px;"><div class="firma-linea"><strong>f. Solicitante</strong><br>Nombre:<br>C.C.:<br>Fecha:</div></td>
        <td><div class="firma-linea"><strong>f. Representante Legal/Director/Coordinador/Jefe de Area</strong><br>Nombre:<br>C.C.:<br>Fecha:</div></td>
    </tr>
    <tr><td colspan="2" class="firma-header">Suscripciones de la ARCONEL</td></tr>
    <tr>
        <td style="height:80px;"><div class="firma-linea"><strong>f. Director/Coordinador de Area funcional del Sistema</strong><br>Nombre:<br>C.C.:<br>Fecha:</div></td>
        <td><div class="firma-linea"><strong>f. Director de Tecnologias de la Informacion y Comunicacion</strong><br>Nombre:<br>C.C.:<br>Fecha:</div></td>
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
