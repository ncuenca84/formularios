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
    @page { margin: 15mm 12mm 22mm 12mm; }
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
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 6.5pt; color: #888; border-top: 2px solid <?= $colorPrimario ?>; padding-top: 3px; }
    .nota-final { font-size: 7pt; color: #666; text-align: center; margin-top: 6px; font-style: italic; }
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
<div class="nota-box"><strong>Nota:</strong> Todos los permisos anteriores que no se encuentren especificados en este formulario seran desactivados.</div>

<!-- 3. ACCESO A SISTEMAS -->
<div class="seccion">3. ACCESO A MODULOS DE SISTEMAS</div>
<table class="dt">
    <?php if (!empty($d['sisdat_modulos'])): ?>
    <tr><td class="lb">SISDAT:</td><td colspan="3"><?= $d['sisdat_modulos'] ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($d['sigcon_modulos'])): ?>
    <tr><td class="lb">SIGCON:</td><td colspan="3"><?= $d['sigcon_modulos'] ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($d['snispcb_acceso']) && $d['snispcb_acceso'] !== ''): ?>
    <tr><td class="lb">SNISPCB:</td><td><?= $d['snispcb_acceso'] ?></td><td class="lb">Coord./Dir:</td><td><?= $d['snispcb_coordinacion'] ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($d['otros_sistemas'])): ?>
    <tr><td class="lb">Otros Sistemas:</td><td colspan="3"><?= nl2br($d['otros_sistemas']) ?></td></tr>
    <?php endif; ?>
</table>

<!-- 4. CARPETAS COMPARTIDAS -->
<div class="seccion">4. ACCESO A CARPETAS COMPARTIDAS / REDES</div>
<table class="dt">
    <tr style="background:#eef2f7;font-weight:600;"><td>Nombre Carpeta</td><td>Permiso</td><td>Periodo</td><td>Desde / Hasta</td></tr>
    <?php if (!empty($d['carpeta1_nombre'])): ?>
    <tr><td><?= $d['carpeta1_nombre'] ?></td><td><?= $d['carpeta1_permiso'] ?></td><td><?= $d['carpeta1_indefinido'] === 'SI' ? 'Indefinido' : 'Definido' ?></td><td><?= $d['carpeta1_desde'] ?> - <?= $d['carpeta1_hasta'] ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($d['carpeta2_nombre'])): ?>
    <tr><td><?= $d['carpeta2_nombre'] ?></td><td><?= $d['carpeta2_permiso'] ?></td><td><?= $d['carpeta2_indefinido'] === 'SI' ? 'Indefinido' : 'Definido' ?></td><td><?= $d['carpeta2_desde'] ?> - <?= $d['carpeta2_hasta'] ?></td></tr>
    <?php endif; ?>
</table>
<?php if (!empty($d['observacion'])): ?>
<p style="font-size:8pt;"><strong>Observacion:</strong> <?= $d['observacion'] ?></p>
<?php endif; ?>

<div class="nota-box">La notificacion de acceso sera enviada a la direccion electronica (e-mail) del funcionario solicitante.</div>

<!-- FIRMAS -->
<div class="seccion">Autorizacion</div>
<table class="firmas-table">
    <tr>
        <td class="firma-header">Solicitado por:</td>
        <td class="firma-header">Autorizado por:</td>
        <td class="firma-header">Autorizacion adicional:</td>
    </tr>
    <tr>
        <td style="height:65px;">
            <div class="firma-linea">
                <strong>Firma Solicitante</strong><br>
                Nombres: <?= $nombreCompleto ?><br>
                C.C.: <?= $d['cedula'] ?><br>
                Cargo: <?= $d['cargo'] ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
                <strong>Firma Autorizador</strong><br>
                Nombres: <?= $d['autorizador_nombre'] ?><br>
                C.C.: <?= $d['autorizador_cedula'] ?><br>
                Cargo: <?= $d['autorizador_cargo'] ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
                <strong>Firma Autorizacion Adicional</strong><br>
                Nombres:<br>
                C.C.:<br>
                Cargo:
            </div>
        </td>
    </tr>
</table>

<div class="nota-final">Documento generado automaticamente el <?= $fecha ?> | <?= $codigo ?></div>

<div class="footer">
    <?php if (!empty($piePagina1)): ?><?= $piePagina1 ?><br><?php endif; ?>
    <?php if (!empty($piePagina2)): ?><?= $piePagina2 ?><br><?php endif; ?>
    <?php if (!empty($piePagina3)): ?><?= $piePagina3 ?><?php endif; ?>
</div>

</body>
</html>
<?php
return ob_get_clean();
