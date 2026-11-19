<?php
/**
 * PDF: documentos del contrato de arrendamiento (clausulas sexta y decima quinta).
 * Recibe: $contrato (fila eqr_contratos), $datosDoc, $config, $meta,
 *         $logoDataUri, $logoSecundarioDataUri.
 * Tipos: acta_inicial | acta_parcial | acta_definitiva | informe_pago
 */

$colorPrimario = $config['color_primario'] ?? '#003366';

$tipo = $datosDoc['tipo_doc'];
$titulos = [
    'acta_inicial'    => 'ACTA DE ENTREGA-RECEPCIÓN INICIAL DE LOS EQUIPOS EN ARRENDAMIENTO',
    'acta_parcial'    => 'ACTA DE ENTREGA-RECEPCIÓN PARCIAL (MENSUAL) DEL SERVICIO DE ARRENDAMIENTO',
    'acta_definitiva' => 'ACTA DE ENTREGA-RECEPCIÓN DEFINITIVA DEL SERVICIO DE ARRENDAMIENTO',
    'informe_pago'    => 'INFORME DE CONFORMIDAD DEL SERVICIO Y SOLICITUD DE PAGO',
];
$metaDoc = $meta;
$metaDoc['titulo_encabezado'] = $titulos[$tipo] ?? $datosDoc['titulo_doc'];
$encabezadoInstitucional = renderPdfEncabezadoInstitucional($config, $metaDoc, $logoDataUri, $logoSecundarioDataUri);

$mesesEs = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
if (!function_exists('eqrFechaLarga')) {
    function eqrFechaLarga(string $f, array $meses): string {
        $ts = strtotime($f);
        if ($f === '' || $ts === false) { return $f; }
        return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
    }
}
if (!function_exists('eqrFechaCorta')) {
    function eqrFechaCorta(string $f): string {
        $ts = strtotime($f);
        return ($f !== '' && $ts !== false) ? date('d/m/Y', $ts) : $f;
    }
}
$usd = fn(float $v): string => 'USD ' . number_format($v, 2, ',', '.');

$c = array_map(fn($v) => htmlspecialchars((string)($v ?? '')), $contrato);
$d = array_map(fn($v) => htmlspecialchars((string)($v ?? '')), $datosDoc);

$cantidad = (int)$datosDoc['cantidad_equipos'];
$precioUnit = (float)$contrato['precio_unitario'];
$valorMensual = $cantidad * $precioUnit;

$fechaLarga = eqrFechaLarga($datosDoc['fecha_doc'], $mesesEs);
$periodoTxt = '';
if ($datosDoc['periodo_desde'] !== '' || $datosDoc['periodo_hasta'] !== '') {
    $periodoTxt = 'del ' . eqrFechaCorta($datosDoc['periodo_desde']) . ' al ' . eqrFechaCorta($datosDoc['periodo_hasta']);
}
$numeroDocTxt = $d['numero_doc'] !== '' ? ' Nro. ' . $d['numero_doc'] : '';

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 42mm 15mm 28mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; line-height: 1.45; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 10px 0 5px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .dt td, .dt th { padding: 4px 8px; border: 1px solid #ddd; font-size: 8.5pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 32%; }
    .dt th { background: #eef2f7; color: <?= $colorPrimario ?>; }
    .intro, .parrafo { text-align: justify; font-size: 8.8pt; margin-bottom: 6px; }
    .nota { font-size: 7.8pt; text-align: justify; margin: 6px 0; padding: 6px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .firmas-table { width: 100%; margin-top: 18px; border-collapse: collapse; }
    .firmas-table td { text-align: center; vertical-align: bottom; padding: 5px 10px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 3px; font-size: 8pt; margin-top: 75px; }
    .firma-header { background: #eef2f7; font-weight: 600; font-size: 8pt; color: <?= $colorPrimario ?>; padding: 4px; }
    .centrado { text-align: center; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

<p class="intro">
En la ciudad de <?= $d['lugar'] ?>, a los <?= htmlspecialchars($fechaLarga) ?>,
<?php if ($tipo === 'informe_pago'): ?>
    la suscrita <strong><?= $c['admin_nombre'] ?></strong>, <?= $c['admin_cargo'] ?>, en su calidad de
    Administradora del Contrato Nro. <strong><?= $c['numero'] ?></strong>
    (proceso <?= $c['proceso'] ?>), cuyo objeto es el
    &quot;<?= $c['objeto'] ?>&quot;, suscrito con
    <strong><?= $c['proveedor'] ?></strong> (RUC <?= $c['contratista_ruc'] ?>),
    emite el presente informe de conformidad del servicio<?= $numeroDocTxt ?>
    <?= $periodoTxt !== '' ? 'correspondiente al período ' . htmlspecialchars($periodoTxt) : '' ?>,
    conforme la cláusula sexta del contrato.
<?php else: ?>
    comparecen, por una parte, la Agencia de Regulación y Control de Electricidad — ARCONEL, a través de
    <strong><?= $c['admin_nombre'] ?></strong>, <?= $c['admin_cargo'] ?>, en su calidad de
    Administradora del Contrato; y, por otra, <strong><?= $c['proveedor'] ?></strong>
    (RUC <?= $c['contratista_ruc'] ?>), representada por
    <strong><?= $c['contratista_representante'] ?></strong>, Representante Legal, con el objeto de suscribir
    la presente acta<?= $numeroDocTxt ?> dentro del Contrato Nro. <strong><?= $c['numero'] ?></strong>
    (proceso <?= $c['proceso'] ?>), cuyo objeto es el &quot;<?= $c['objeto'] ?>&quot;,
    conforme sus cláusulas sexta y décima quinta.
<?php endif; ?>
</p>

<div class="seccion">1. DATOS DEL CONTRATO</div>
<table class="dt">
    <tr><td class="lb">Contrato Nro.:</td><td><?= $c['numero'] ?></td></tr>
    <tr><td class="lb">Proceso:</td><td><?= $c['proceso'] ?></td></tr>
    <tr><td class="lb">Contratista:</td><td><?= $c['proveedor'] ?> — RUC <?= $c['contratista_ruc'] ?></td></tr>
    <tr><td class="lb">Vigencia:</td><td><?= eqrFechaCorta((string)$contrato['fecha_inicio']) ?> al <?= eqrFechaCorta((string)$contrato['fecha_fin']) ?> (<?= (int)$contrato['plazo_dias'] ?> días)</td></tr>
    <tr><td class="lb">Valor unitario mensual:</td><td><?= $usd($precioUnit) ?> por equipo, más IVA</td></tr>
    <tr><td class="lb">Monto máximo del contrato:</td><td><?= $usd((float)$contrato['valor_total']) ?>, más IVA</td></tr>
</table>

<?php if ($tipo === 'acta_inicial'): ?>

<div class="seccion">2. VERIFICACIÓN DE LA RECEPCIÓN INICIAL</div>
<p class="parrafo">De conformidad con la cláusula décima quinta del contrato, se verificó la entrega de
<strong><?= $cantidad ?></strong> equipos de cómputo, con el siguiente resultado:</p>
<table class="dt">
    <tr><th>Aspecto verificado</th><th style="width:20%;">Resultado</th></tr>
    <tr><td>Cantidad de equipos entregados (<?= $cantidad ?> de <?= (int)$contrato['total_equipos'] ?>)</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Estado físico y funcionamiento de los equipos</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Especificaciones técnicas conforme los términos de referencia</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Licenciamiento del sistema operativo (Windows 11 Pro)</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Inventario detallado (marca, modelo, serie, procesador, RAM, almacenamiento, pantalla, accesorios)</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Documentación que acredita la antigüedad o fecha de adquisición</td><td class="centrado">CUMPLE</td></tr>
</table>
<p class="parrafo">Con la suscripción de la presente acta inicia la fase de prestación efectiva del servicio
(365 días calendario) y la generación de pagos, y se habilita el primer pago conforme la cláusula sexta.</p>

<?php elseif ($tipo === 'acta_parcial'): ?>

<div class="seccion">2. SERVICIO PRESTADO EN EL PERÍODO</div>
<table class="dt">
    <tr><td class="lb">Período del servicio:</td><td><?= htmlspecialchars($periodoTxt ?: 'No indicado') ?></td></tr>
    <tr><td class="lb">Equipos efectivamente arrendados y disponibles:</td><td><?= $cantidad ?></td></tr>
    <tr><td class="lb">Valor del período:</td><td><?= $cantidad ?> equipos × <?= $usd($precioUnit) ?> = <strong><?= $usd($valorMensual) ?></strong>, más IVA</td></tr>
</table>
<p class="parrafo">La presente acta se sustenta en el informe mensual del contratista y en el informe de
conformidad del Administrador del Contrato correspondientes al período indicado.</p>
<div class="nota">Esta acta no implica recepción definitiva ni libera al contratista de sus obligaciones de
soporte, mantenimiento, reposición, confidencialidad o garantía (cláusula décima quinta del contrato).</div>

<?php elseif ($tipo === 'acta_definitiva'): ?>

<div class="seccion">2. CIERRE DEL CONTRATO</div>
<p class="parrafo">Habiendo concluido el plazo contractual, las partes dejan constancia de que:</p>
<table class="dt">
    <tr><th>Aspecto verificado</th><th style="width:20%;">Resultado</th></tr>
    <tr><td>Retiro de la totalidad de los equipos arrendados (<?= $cantidad ?> equipos)</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Borrado seguro de la información institucional ejecutado bajo coordinación de la DTIC</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Entrega de los certificados de borrado (marca, modelo, serie, fecha y método)</td><td class="centrado">CUMPLE</td></tr>
    <tr><td>Cumplimiento de las obligaciones contractuales durante la vigencia</td><td class="centrado">CUMPLE</td></tr>
</table>
<p class="parrafo">Con la suscripción de la presente acta de entrega-recepción definitiva se da por
concluida la ejecución del Contrato Nro. <?= $c['numero'] ?>, sin perjuicio de las obligaciones que por su
naturaleza subsistan (confidencialidad y protección de datos personales).</p>

<?php else: /* informe_pago */ ?>

<div class="seccion">2. CONFORMIDAD DEL SERVICIO</div>
<table class="dt">
    <tr><td class="lb">Período facturado:</td><td><?= htmlspecialchars($periodoTxt ?: 'No indicado') ?></td></tr>
    <tr><td class="lb">Equipos efectivamente arrendados y disponibles:</td><td><?= $cantidad ?></td></tr>
    <tr><td class="lb">Niveles de servicio (SLA):</td>
        <td><?= $datosDoc['sla_cumplido'] === 'si' ? 'Cumplidos, sin observaciones' : 'Cumplidos, con las observaciones que se detallan' ?></td></tr>
    <tr><td class="lb">Factura del proveedor:</td><td><?= $d['nro_factura'] !== '' ? 'Nro. ' . $d['nro_factura'] : 'Por presentar' ?></td></tr>
</table>

<div class="seccion">3. DOCUMENTOS HABILITANTES RECIBIDOS DEL PROVEEDOR</div>
<table class="dt">
    <tr><td>Factura del período</td><td>Informe mensual de cumplimiento del servicio (SLA, incidencias y atenciones)</td></tr>
    <tr><td>Copia de RUC actualizado</td><td>Garantías técnicas vigentes</td></tr>
</table>

<div class="seccion">4. VALOR A PAGAR</div>
<table class="dt">
    <tr><td class="lb">Cálculo:</td><td><?= $cantidad ?> equipos × <?= $usd($precioUnit) ?> (valor unitario mensual)</td></tr>
    <tr><td class="lb">Valor del período:</td><td><strong><?= $usd($valorMensual) ?></strong>, más IVA</td></tr>
    <tr><td class="lb">Partida presupuestaria:</td><td>530703 — Arrendamiento de Equipos Informáticos</td></tr>
</table>

<div class="seccion">5. CONCLUSIÓN Y SOLICITUD</div>
<p class="parrafo">Se deja constancia de que el servicio de arrendamiento fue prestado de manera efectiva
durante el período indicado, con la disponibilidad de los equipos y el cumplimiento de los niveles de
servicio establecidos. En consecuencia, se emite el presente <strong>informe de conformidad</strong> y se
solicita a la Dirección Administrativa / Dirección Financiera continuar con el trámite de pago
correspondiente, conforme la cláusula sexta del contrato (el pago no excederá el término de treinta días
desde el cumplimiento de los requisitos).</p>

<?php endif; ?>

<?php if ($d['observaciones'] !== ''): ?>
<div class="seccion"><?= $tipo === 'informe_pago' ? '6' : '3' ?>. OBSERVACIONES</div>
<p class="parrafo"><?= nl2br($d['observaciones']) ?></p>
<?php endif; ?>

<?php if ($tipo === 'informe_pago'): ?>
<table class="firmas-table">
    <tr><td class="firma-header" style="width:100%;">ELABORADO Y APROBADO POR</td></tr>
    <tr><td>
        <div class="firma-linea">
            <strong><?= $c['admin_nombre'] ?></strong><br>
            <?= $c['admin_cargo'] ?><br>
            Administradora del Contrato Nro. <?= $c['numero'] ?><br>
            C.C. <?= $c['admin_cedula'] ?>
        </div>
    </td></tr>
</table>
<?php else: ?>
<table class="firmas-table">
    <tr>
        <td class="firma-header" style="width:50%;">POR LA ARCONEL</td>
        <td class="firma-header" style="width:50%;">POR LA CONTRATISTA</td>
    </tr>
    <tr>
        <td>
            <div class="firma-linea">
                <strong><?= $c['admin_nombre'] ?></strong><br>
                <?= $c['admin_cargo'] ?><br>
                Administradora del Contrato<br>
                C.C. <?= $c['admin_cedula'] ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
                <strong><?= $c['contratista_representante'] ?></strong><br>
                Representante Legal<br>
                <?= $c['proveedor'] ?><br>
                RUC <?= $c['contratista_ruc'] ?>
            </div>
        </td>
    </tr>
</table>
<?php endif; ?>

</body>
</html>
<?php
return ob_get_clean();
