<?php
/**
 * PDF Template: Solicitud de Acceso a los Sistemas Informaticos
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

$nombreCompleto = trim($d['nombre1'] . ' ' . $d['nombre2'] . ' ' . $d['apellido1'] . ' ' . $d['apellido2']);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 15mm 12mm 30mm 12mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 8.5pt; color: #333; line-height: 1.3; }
    .inst-name { font-size: 10pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .info-bar { width: 100%; font-size: 7.5pt; margin-bottom: 5px; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 8.5pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 8px 0 4px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .dt td { padding: 3px 6px; border: 1px solid #ddd; font-size: 8pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 22%; }
    .nota-box { font-size: 7.5pt; background: #fff3cd; padding: 4px 8px; border-radius: 3px; margin: 4px 0; }
    .firmas-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .firmas-table td { width: 33%; text-align: center; vertical-align: bottom; padding: 4px 8px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 2px; font-size: 7pt; margin-top: 35px; }
    .firma-header { background: #eef2f7; font-weight: 600; font-size: 7.5pt; color: <?= $colorPrimario ?>; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #666; border-top: 1px solid #999; padding-top: 4px; line-height: 1.4; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

<table class="info-bar">
    <tr>
        <td style="text-align:left;"><strong>Fecha:</strong> <?= $fecha ?></td>
        <td style="text-align:center;"><strong>Version:</strong> 1.0</td>
        <td style="text-align:right;"><strong>Codigo:</strong> <span style="font-family:Courier;font-weight:bold;color:<?= $colorPrimario ?>;"><?= $codigo ?></span></td>
    </tr>
</table>

<!-- 1. DATOS DEL AUTORIZADOR -->
<div class="seccion">1. DATOS DEL AUTORIZADOR</div>
<table class="dt">
    <tr><td class="lb">Agencia:</td><td><?= $d['agencia'] ?></td><td class="lb">Coord./Direccion:</td><td><?= $d['coordinacion_direccion'] ?></td></tr>
    <tr><td class="lb">Ciudad:</td><td><?= $d['ciudad'] ?></td><td class="lb">Fecha:</td><td><?= $fecha ?></td></tr>
    <tr><td class="lb">Nombre:</td><td><?= $d['autorizador_nombre'] ?></td><td class="lb">C.C.:</td><td><?= $d['autorizador_cedula'] ?></td></tr>
    <tr><td class="lb">Cargo:</td><td colspan="3"><?= $d['autorizador_cargo'] ?></td></tr>
</table>

<!-- 2. DATOS DEL SOLICITANTE -->
<div class="seccion">2. DATOS DEL SOLICITANTE</div>
<table class="dt">
    <tr><td class="lb">Nombres:</td><td colspan="3"><?= $nombreCompleto ?></td></tr>
    <tr><td class="lb">Cedula:</td><td><?= $d['cedula'] ?></td><td class="lb">Correo:</td><td><?= $d['correo_usuario'] ?>@arconel.gob.ec</td></tr>
    <tr><td class="lb">Cargo:</td><td><?= $d['cargo'] ?></td><td class="lb">Telefono/Ext:</td><td><?= $d['telefono_extension'] ?></td></tr>
</table>
<div class="footer">
    <em>"Este documento es para uso exclusivo de la ARCONEL. Se prohibe su uso no autorizado".</em><br>
    GESTION GENERAL DE PLANIFICACION Y GESTION ESTRATEGICA
</div>

</body>
</html>
<?php
return ob_get_clean();
