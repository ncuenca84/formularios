<?php
/**
 * PDF Template: Solicitud de Acceso a los Sistemas Informaticos
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
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
    @page { margin: 42mm 12mm 28mm 12mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 8.5pt; color: #333; line-height: 1.3; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 8.5pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 8px 0 4px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .dt td { padding: 3px 6px; border: 1px solid #ddd; font-size: 8pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 22%; }
    .nota-box { font-size: 7.5pt; background: #fff3cd; padding: 4px 8px; border-radius: 3px; margin: 4px 0; }
    .firmas-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .firmas-table td { width: 33%; text-align: center; vertical-align: bottom; padding: 4px 8px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 2px; font-size: 7pt; margin-top: 70px; }
    .firma-header { background: #eef2f7; font-weight: 600; font-size: 7.5pt; color: <?= $colorPrimario ?>; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

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
    <?php if (!empty($d['snispcb_acceso'])): ?>
    <tr><td class="lb">SNISPCB:</td><td><?= $d['snispcb_acceso'] ?></td><td class="lb">Coord./Dir:</td><td><?= $d['snispcb_coordinacion'] ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($d['otros_sistemas'])): ?>
    <tr><td class="lb">Otros Sistemas:</td><td colspan="3"><?= nl2br($d['otros_sistemas']) ?></td></tr>
    <?php endif; ?>
    <?php if (empty($d['sisdat_modulos']) && empty($d['sigcon_modulos']) && empty($d['snispcb_acceso']) && empty($d['otros_sistemas'])): ?>
    <tr><td colspan="4" style="text-align:center;color:#999;">No se especificaron sistemas</td></tr>
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
    <?php if (empty($d['carpeta1_nombre']) && empty($d['carpeta2_nombre'])): ?>
    <tr><td colspan="4" style="text-align:center;color:#999;">No se solicitaron carpetas</td></tr>
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
        <td style="height:55px;">
            <p style="font-size:7pt;text-align:justify;">Declaro conocer y hacerme cargo de los compromisos que asumo al acceder a los permisos y sistemas solicitados.</p>
            <div class="firma-linea">
                <strong>Firma Solicitante</strong><br>
                Nombres: <?= $nombreCompleto ?><br>
                C.C.: <?= $d['cedula'] ?><br>
                Cargo: <?= $d['cargo'] ?>
            </div>
        </td>
        <td>
            <p style="font-size:7pt;text-align:justify;">Declaro conocer y hacerme cargo de los compromisos que asumo al autorizar la presente solicitud.</p>
            <div class="firma-linea">
                <strong>Firma Autorizador</strong><br>
                Nombres: <?= $d['autorizador_nombre'] ?><br>
                C.C.: <?= $d['autorizador_cedula'] ?><br>
                Cargo: <?= $d['autorizador_cargo'] ?>
            </div>
        </td>
        <td>
            <p style="font-size:7pt;text-align:justify;">Declaro conocer y hacerme cargo de los compromisos que asumo al autorizar la presente solicitud.</p>
            <div class="firma-linea">
                <strong>Firma Autorizacion Adicional</strong><br>
                Nombres:<br>
                C.C.:<br>
                Cargo:
            </div>
        </td>
    </tr>
</table>

</body>
</html>
<?php
return ob_get_clean();
