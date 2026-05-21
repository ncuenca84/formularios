<?php
/**
 * PDF Template: Solicitud de Accesos Especiales para el Servicio de Internet
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

// Construir tipo de acceso
$accesos = [];
if (!empty($datos['acceso_redes_sociales'])) $accesos[] = 'Redes Sociales';
if (!empty($datos['acceso_streaming'])) $accesos[] = 'Streaming / Video';
if (!empty($datos['acceso_descargas'])) $accesos[] = 'Sitios de Descargas';
if (!empty($datos['acceso_otro'])) $accesos[] = 'Otros: ' . $d['acceso_otro_detalle'];
$accesosTexto = implode(', ', $accesos) ?: 'No especificado';

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 42mm 15mm 28mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9.5pt; color: #333; line-height: 1.5; }
    .inst-name { font-size: 11pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 10px; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9.5pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 3px; margin: 12px 0 6px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .dt td { padding: 5px 8px; border: 1px solid #ddd; font-size: 9pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .consideraciones { font-size: 8pt; text-align: justify; margin: 8px 0; padding: 8px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .consideraciones li { margin-bottom: 4px; }
    .firmas-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 5px 12px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 4px; font-size: 8pt; margin-top: 50px; }
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
</table>

<!-- REQUERIMIENTO -->
<div class="seccion">Informacion del Requerimiento</div>
<table class="dt">
    <tr><td class="lb">Fecha de solicitud:</td><td><?= $fecha ?></td></tr>
    <tr><td class="lb">Tipo de Acceso:</td><td><?= $accesosTexto ?></td></tr>
    <?php if (!empty($d['url_aplicacion'])): ?>
    <tr><td class="lb">Aplicacion / URL:</td><td><?= $d['url_aplicacion'] ?></td></tr>
    <?php endif; ?>
    <tr><td class="lb" style="vertical-align:top;">Justificacion:</td><td><?= nl2br($d['justificacion']) ?></td></tr>
</table>

<!-- CONSIDERACIONES -->
<div class="seccion">Consideraciones</div>
<div class="consideraciones">
<ul style="margin:0;padding-left:15px;">
    <li>El servicio de internet debe utilizarse exclusivamente para las actividades y funciones asignadas en el marco de las facultades que son de competencia de la ARCONEL, y para ningun otro fin.</li>
    <li>Cada servidor es responsable de la informacion y contenidos a los que accede y de aquella que copia para conservacion en los equipos de la institucion.</li>
    <li>El Coordinador/Director/Jefe de Area es responsable de la autorizacion que otorga al servidor, a traves de este formulario.</li>
    <li>A la Unidad de Tecnologias de la Informacion le corresponde auditar el uso del servicio de Internet, quedando eximida de responsabilidades por el uso indebido del servicio e informacion que se produzca fuera de dicha area.</li>
</ul>
</div>

<!-- FIRMAS -->
<div class="seccion">Suscripciones</div>
<table class="firmas-table">
    <tr>
        <td style="height:80px;">
            <p style="font-size:8pt;text-align:justify;">Declaro conocer y hacerme cargo de los compromisos que asumo por la correcta utilizacion del Internet, como herramienta de trabajo, asi como de las implicaciones que conlleva un uso inadecuado del mismo.</p>
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


</body>
</html>
<?php
return ob_get_clean();
