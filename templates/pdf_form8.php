<?php
/**
 * PDF Template: Solicitud de Cuenta Adicional de Correo Electronico
 * Basado en el formulario GGPGE.GTIC.02.FO.06 (version 01, aprobado 29/07/2024).
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$encabezadoInstitucional = renderPdfEncabezadoInstitucional($config, $meta, $logoDataUri, $logoSecundarioDataUri);
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

$d = array_map(function ($v) { return htmlspecialchars((string)($v ?? '')); }, $datos);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 42mm 15mm 28mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; line-height: 1.4; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 10px 0 5px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .dt td { padding: 4px 8px; border: 1px solid #ddd; font-size: 8.5pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .intro { text-align: justify; font-size: 8.5pt; margin-bottom: 6px; }
    .lineamientos { font-size: 8pt; text-align: justify; margin: 6px 0; padding: 6px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .lineamientos li { margin-bottom: 3px; }
    .codigo-doc { text-align: right; font-size: 8pt; color: #555; margin-bottom: 4px; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

<div class="codigo-doc">Solicitud Nro. <strong><?= $codigo ?></strong> · Fecha: <?= $fecha ?></div>

<p class="intro">Mediante el presente formulario se documenta la solicitud de una <strong>cuenta adicional
de correo electrónico institucional</strong>, conforme a la Norma de Control Interno 410-15 de la
Contraloría General del Estado (portal web, servicios telemáticos e intranet) y al Esquema Gubernamental
de Seguridad de la Información (EGSI), control 1.10 sobre el uso aceptable de la información y la
reglamentación del uso del correo electrónico institucional.</p>

<!-- 1. DATOS DEL SOLICITANTE -->
<div class="seccion">1. INFORMACIÓN DEL SOLICITANTE</div>
<table class="dt">
    <tr><td class="lb">Apellidos y nombres:</td><td colspan="3"><?= $d['nombre_completo'] ?></td></tr>
    <tr><td class="lb">Cédula:</td><td><?= $d['cedula'] ?></td>
        <td class="lb">Correo institucional actual:</td><td><?= $d['correo'] ?></td></tr>
    <tr><td class="lb">Cargo:</td><td><?= $d['cargo'] ?></td>
        <td class="lb">Departamento / Dirección:</td><td><?= $d['area'] ?></td></tr>
</table>

<!-- 2. CUENTA SOLICITADA -->
<div class="seccion">2. CUENTA SOLICITADA</div>
<table class="dt">
    <tr><td class="lb">Nombre sugerido para la nueva cuenta:</td><td><strong><?= $d['cuenta_sugerida'] ?></strong></td></tr>
    <tr><td class="lb">Justificación:</td><td><?= nl2br($d['justificacion']) ?></td></tr>
</table>

<!-- 3. LINEAMIENTOS -->
<div class="seccion">3. LINEAMIENTOS DE USO</div>
<p class="intro">El solicitante y su jefe inmediato comprenden y aceptan los siguientes lineamientos:</p>
<div class="lineamientos">
<ul style="margin:0;padding-left:15px;">
    <li>Esta cuenta será utilizada exclusivamente para tareas propias de las funciones que se desarrollan en la institución y no para ningún otro fin.</li>
    <li>Son responsables tanto del contenido de los mensajes enviados como de cualquier otra información que se adjunte.</li>
    <li>Todos los mensajes pueden ser monitoreados y respaldados según los procedimientos de la institución.</li>
    <li>La nueva cuenta de correo estará asociada en el servidor de correo a la cuenta de usuario institucional del solicitante.</li>
    <li>Si el solicitante se cambia de área o sale de la institución se desactivará la cuenta creada hasta que se presente una nueva solicitud.</li>
</ul>
</div>

<!-- 4. ACEPTACION -->
<div class="seccion">4. ACEPTACIÓN</div>
<p class="intro">Con su firma electrónica, el solicitante declara la necesidad institucional de la cuenta y
acepta los lineamientos de uso detallados; el Director/Jefe de Área aprueba la creación de la cuenta
solicitada. La DTIC ejecutará la creación una vez completadas las firmas.</p>

<?= $paginaFirmasHtml ?? '' ?>

</body>
</html>
<?php
return ob_get_clean();
