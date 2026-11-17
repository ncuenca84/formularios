<?php
/**
 * Generacion del acta PDF de los tramites de equipos rentados
 * (asignacion, traspaso, devolucion), basada en docs/actas/ACTA01.DOC.
 *
 * REGLA DE ORO: el acta se genera UNA SOLA VEZ al crear el tramite.
 * Despues solo crece con firmas incrementales; nunca se regenera.
 *
 * Los recuadros de firma van en una ULTIMA PAGINA dedicada, en coordenadas
 * fijas conocidas por el codigo de firma (eqrRectsFirma).
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

/** Pagina A4 en puntos y margenes de la plantilla (42mm/15mm/28mm/15mm). */
const EQR_PAGINA_ALTO = 841.89;
const EQR_PAGINA_ANCHO = 595.28;
const EQR_MARGEN_SUP = 119.06;   // 42 mm
const EQR_MARGEN_IZQ = 42.52;    // 15 mm

/**
 * Recuadros de firma por orden de firmante, en la ULTIMA pagina del acta.
 * 'css'  = left/top EN PUNTOS dentro del area de contenido (para dibujarlos).
 * 'rect' = [x1,y1,x2,y2] en coordenadas PDF (origen abajo-izquierda) para el widget.
 */
function eqrRectsFirma(): array
{
    // [left_css, top_css, ancho, alto] respecto del area de contenido
    // Hasta 4 firmantes (cuadricula 2x2); las actas usan 1-3.
    // Tamano reducido ~24% a pedido del usuario (similar al sello de FirmaEC).
    $defs = [
        1 => [35,  150, 180, 61],
        2 => [295, 150, 180, 61],
        3 => [35,  280, 180, 61],
        4 => [295, 280, 180, 61],
    ];
    $out = [];
    foreach ($defs as $n => [$l, $t, $w, $h]) {
        $x1 = EQR_MARGEN_IZQ + $l;
        $yTop = EQR_PAGINA_ALTO - EQR_MARGEN_SUP - $t;
        $out[$n] = [
            'css' => ['left' => $l, 'top' => $t, 'w' => $w, 'h' => $h],
            'rect' => [round($x1, 2), round($yTop - $h, 2), round($x1 + $w, 2), round($yTop, 2)],
        ];
    }
    return $out;
}

/** Etiquetas de los recuadros de firma segun el tipo de tramite. */
function eqrEtiquetasFirma(string $tipo): array
{
    return match ($tipo) {
        'asignacion' => [1 => 'ENTREGA MATERIAL POR LA DTIC', 2 => 'RECIBE COMO USUARIO FINAL'],
        'traspaso'   => [1 => 'ADMINISTRADOR DEL CONTRATO (DTIC)', 2 => 'ENTREGA — CUSTODIO ANTERIOR', 3 => 'RECIBE — NUEVO CUSTODIO'],
        'devolucion' => [1 => 'RECIBE POR LA DTIC (ADMINISTRADOR DEL CONTRATO)', 2 => 'ENTREGA — CUSTODIO QUE DEVUELVE'],
        'salida'     => [1 => 'SOLICITA — CUSTODIO DEL EQUIPO', 2 => 'AUTORIZA — JEFE INMEDIATO'],
        default => [],
    };
}

/** Carpeta del acta de un tramite (fuera de la carpeta publica). */
function eqrActaDir(string $codigoTramite): string
{
    return __DIR__ . '/../../storage/actas/' . preg_replace('/[^A-Za-z0-9\-]/', '_', $codigoTramite);
}
function eqrActaPath(string $codigoTramite): string
{
    return eqrActaDir($codigoTramite) . '/acta.pdf';
}
function eqrActaStatePath(string $codigoTramite): string
{
    return eqrActaDir($codigoTramite) . '/acta.state.json';
}

/**
 * Genera el acta del tramite y la guarda en storage/actas/{codigo}/acta.pdf.
 * Devuelve ['path'=>..., 'hash'=>sha256].
 * Lanza excepcion si el acta ya existe (nunca se regenera).
 */
function eqrGenerarActa(array $tramite, array $equipo, array $contrato, array $firmantes): array
{
    $dir = eqrActaDir($tramite['codigo']);
    $path = eqrActaPath($tramite['codigo']);
    if (is_file($path)) {
        throw new RuntimeException('El acta del trámite ya existe y no se regenera.');
    }
    @mkdir($dir, 0775, true);

    $config = getAllConfig();
    $meta = getFormularioMeta(8);
    $logoDataUri = getLogoDataUri('logo_path');
    $logoSecundarioDataUri = getLogoDataUri('logo_secundario_path');
    $html = eqrActaHtml($tramite, $equipo, $contrato, $firmantes, $config, $meta,
                        $logoDataUri, $logoSecundarioDataUri);

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Helvetica');
    $options->set('isFontSubsettingEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $bytes = $dompdf->output();
    file_put_contents($path, $bytes);
    // copia base v0: referencia de continuidad de la primera firma
    file_put_contents($dir . '/v0.pdf', $bytes);
    return ['path' => $path, 'hash' => hash('sha256', $bytes)];
}

/** HTML completo del acta. */
function eqrActaHtml(array $tramite, array $equipo, array $contrato, array $firmantes,
                     array $config, array $meta, string $logoDataUri, string $logoSecundarioDataUri): string
{
    $tipo = $tramite['tipo'];
    if ($tipo === 'salida') {
        return eqrActaSalidaHtml($tramite, $equipo, $contrato, $firmantes, $config, $meta,
                                 $logoDataUri, $logoSecundarioDataUri);
    }
    $titulos = [
        'asignacion' => 'ACTA DE ASIGNACIÓN, ENTREGA Y RECEPCIÓN DE EQUIPO DE CÓMPUTO EN ARRENDAMIENTO',
        'traspaso'   => 'ACTA DE TRASPASO DE EQUIPO DE CÓMPUTO EN ARRENDAMIENTO',
        'devolucion' => 'ACTA DE DEVOLUCIÓN DE EQUIPO DE CÓMPUTO EN ARRENDAMIENTO',
    ];
    $metaDoc = $meta;
    if (!empty($titulos[$tipo])) { $metaDoc['titulo_encabezado'] = $titulos[$tipo]; }
    $encabezado = renderPdfEncabezadoInstitucional($config, $metaDoc, $logoDataUri, $logoSecundarioDataUri);
    $colorPrimario = $config['color_primario'] ?? '#003366';

    $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    $parrafos = fn($txt) => nl2br($e($txt));

    $mesesEs = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime((string)$tramite['created_at']) ?: time();
    $fechaLarga = date('j', $ts) . ' de ' . $mesesEs[(int)date('n', $ts)] . ' de ' . date('Y', $ts);

    // ---- constancia segun tipo (textos nuevos de traspaso/devolucion:
    //      derivados del acta de asignacion; revisar con el area legal) ----
    $constancia = match ($tipo) {
        'asignacion' => 'En la ciudad de Quito, a los ' . $e($fechaLarga) . ', la Dirección de Tecnologías de la '
            . 'Información y Comunicación (DTIC), a través de ' . $e($tramite['admin_contrato_nombre'])
            . ', en su calidad de ' . $e($tramite['admin_contrato_calidad'] ?: 'Administrador del Contrato')
            . ', hace la entrega formal del equipo de cómputo en arrendamiento detallado en la presente acta, '
            . 'dentro del Contrato Nro. ' . $e($contrato['numero']) . ', a '
            . $e($tramite['recibe_nombre']) . ', con cédula de ciudadanía Nro. ' . $e($tramite['recibe_cedula'])
            . ', quien lo recibe en calidad de usuario final y custodio, en las condiciones físicas y operativas '
            . 'que constan en este documento.',
        'traspaso' => 'En la ciudad de Quito, a los ' . $e($fechaLarga) . ', con la autorización de la Dirección de '
            . 'Tecnologías de la Información y Comunicación (DTIC), a través de ' . $e($tramite['admin_contrato_nombre'])
            . ', en su calidad de ' . $e($tramite['admin_contrato_calidad'] ?: 'Administrador del Contrato')
            . ', se realiza el traspaso del equipo de cómputo en arrendamiento detallado en la presente acta, '
            . 'dentro del Contrato Nro. ' . $e($contrato['numero']) . ': lo entrega '
            . $e($tramite['entrega_nombre']) . ' (C.C. ' . $e($tramite['entrega_cedula']) . '), custodio saliente, '
            . 'y lo recibe ' . $e($tramite['recibe_nombre']) . ' (C.C. ' . $e($tramite['recibe_cedula']) . '), '
            . 'quien asume desde la suscripción de esta acta la calidad de usuario final y custodio del equipo, '
            . 'en las condiciones físicas y operativas que constan en este documento.',
        'devolucion' => 'En la ciudad de Quito, a los ' . $e($fechaLarga) . ', '
            . $e($tramite['entrega_nombre']) . ', con cédula de ciudadanía Nro. ' . $e($tramite['entrega_cedula'])
            . ', devuelve a la Dirección de Tecnologías de la Información y Comunicación (DTIC) el equipo de '
            . 'cómputo en arrendamiento detallado en la presente acta, dentro del Contrato Nro. '
            . $e($contrato['numero']) . '. Lo recibe ' . $e($tramite['admin_contrato_nombre'])
            . ', en su calidad de ' . $e($tramite['admin_contrato_calidad'] ?: 'Administrador del Contrato')
            . ', en las condiciones físicas y operativas que constan en este documento. Con la suscripción de '
            . 'esta acta cesa la custodia del usuario sobre el equipo, el cual queda a disposición de la DTIC.',
        default => '',
    };

    // ---- datos de la persona usuaria (segun el tipo) ----
    $usuario = $tipo === 'devolucion'
        ? ['titulo' => '1. DATOS DEL USUARIO QUE DEVUELVE', 'pref' => 'entrega_']
        : ['titulo' => '1. DATOS DEL USUARIO FINAL', 'pref' => 'recibe_'];
    $p = $usuario['pref'];

    $filaAnterior = '';
    if ($tipo === 'traspaso') {
        $filaAnterior = '<tr><td class="lb">Custodio anterior (entrega):</td><td colspan="3">'
            . $e($tramite['entrega_nombre']) . ' — C.C. ' . $e($tramite['entrega_cedula'])
            . ($tramite['entrega_area'] ? ' — ' . $e($tramite['entrega_area']) : '') . '</td></tr>';
    }

    // ---- recuadros de firma (ultima pagina) ----
    $rects = eqrRectsFirma();
    $etiquetas = eqrEtiquetasFirma($tipo);
    if (str_starts_with((string)$tramite['admin_contrato_calidad'], 'Delegado')) {
        $etiquetas[1] = match ($tipo) {
            'asignacion' => 'ENTREGA MATERIAL POR LA DTIC (DELEGADO)',
            'devolucion' => 'RECIBE POR LA DTIC (DELEGADO DEL ADMINISTRADOR)',
            default => 'POR LA DTIC (DELEGADO DEL ADMINISTRADOR DEL CONTRATO)',
        };
    }
    $cajas = '';
    foreach ($firmantes as $f) {
        $n = (int)$f['orden'];
        $c = $rects[$n]['css'];
        $cajas .= sprintf(
            '<div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; height:%.2fpt;
                 border:0.8pt solid #777;"></div>
             <div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; text-align:center;
                 font-size:7.5pt; font-weight:bold; color:#333;">%s</div>
             <div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; text-align:center;
                 font-size:8pt; color:#333;">%s<br>C.C. %s%s</div>',
            $c['left'], $c['top'], $c['w'], $c['h'],
            $c['left'], $c['top'] + $c['h'] + 4, $c['w'], $e($etiquetas[$n] ?? ''),
            $c['left'], $c['top'] + $c['h'] + 26, $c['w'],
            $e($f['nombre']), $e($f['cedula']),
            $f['cargo'] !== '' ? '<br>' . $e($f['cargo']) : ''
        );
    }

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
    .dt td { padding: 3px 7px; border: 1px solid #ddd; font-size: 8.5pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .clausulas { font-size: 8pt; text-align: justify; margin: 4px 0 8px; }
    .intro { text-align: justify; font-size: 8.8pt; margin-bottom: 6px; }
    .codigo-tramite { text-align: right; font-size: 8pt; color: #555; margin-bottom: 4px; }
</style>
</head>
<body>

<?= $encabezado ?>

<div class="codigo-tramite">Trámite Nro. <strong><?= $e($tramite['codigo']) ?></strong> ·
    Contrato <?= $e($contrato['numero']) ?></div>

<div class="seccion"><?= $e($usuario['titulo']) ?></div>
<table class="dt">
    <tr><td class="lb">Apellidos y nombres:</td><td colspan="3"><?= $e($tramite[$p . 'nombre']) ?></td></tr>
    <tr><td class="lb">Cédula:</td><td><?= $e($tramite[$p . 'cedula']) ?></td>
        <td class="lb">Correo institucional:</td><td><?= $e($tramite[$p . 'correo']) ?></td></tr>
    <tr><td class="lb">Cargo:</td><td><?= $e($tramite[$p . 'cargo']) ?></td>
        <td class="lb">Coordinación / Dirección:</td><td><?= $e($tramite[$p . 'area']) ?></td></tr>
    <?= $filaAnterior ?>
</table>

<div class="seccion">2. DATOS DEL EQUIPO</div>
<table class="dt">
    <tr><td class="lb">Código interno:</td><td><?= $e($equipo['codigo_interno']) ?></td>
        <td class="lb">Tipo:</td><td><?= $e($equipo['tipo']) ?></td></tr>
    <tr><td class="lb">Marca / Modelo:</td><td><?= $e($equipo['marca']) ?> <?= $e($equipo['modelo']) ?></td>
        <td class="lb">Serie:</td><td><?= $e($equipo['serie']) ?></td></tr>
    <tr><td class="lb">Procesador:</td><td><?= $e($equipo['procesador']) ?></td>
        <td class="lb">Memoria RAM:</td><td><?= $e($equipo['ram']) ?></td></tr>
    <tr><td class="lb">Almacenamiento:</td><td><?= $e($equipo['almacenamiento']) ?></td>
        <td class="lb">Pantalla:</td><td><?= $e($equipo['pantalla']) ?></td></tr>
    <tr><td class="lb">Cargador:</td><td><?= !empty($equipo['cargador_entregado']) ? 'Sí' : 'No' ?>
            <?= $equipo['serie_cargador'] !== '' ? '— Serie ' . $e($equipo['serie_cargador']) : '' ?></td>
        <td class="lb">Contrato:</td><td><?= $e($contrato['numero']) ?></td></tr>
    <tr><td class="lb">Estado físico:</td><td colspan="3"><?= $e($tramite['estado_fisico_reportado'] ?: $equipo['estado_fisico']) ?></td></tr>
    <tr><td class="lb">Accesorios:</td><td colspan="3"><?= $e($tramite['accesorios']) ?></td></tr>
    <?php if (trim((string)$tramite['observaciones']) !== ''): ?>
    <tr><td class="lb">Observaciones:</td><td colspan="3"><?= $e($tramite['observaciones']) ?></td></tr>
    <?php endif; ?>
</table>

<div class="seccion">3. CONSTANCIA DE <?= $tipo === 'devolucion' ? 'DEVOLUCIÓN Y RECEPCIÓN' : 'ENTREGA Y RECEPCIÓN' ?></div>
<p class="intro"><?= $constancia ?></p>

<div class="seccion">4. COMPROMISOS DEL USUARIO</div>
<div class="clausulas"><?= $parrafos($contrato['texto_compromisos']) ?></div>

<div class="seccion">5. CONSIDERACIONES SOBRE EL EQUIPO EN ARRENDAMIENTO</div>
<div class="clausulas"><?= $parrafos($contrato['texto_consideraciones']) ?></div>

<div class="seccion">6. ACEPTACIÓN</div>
<div class="clausulas"><?= $parrafos($contrato['texto_aceptacion']) ?></div>

<!-- ============ ULTIMA PAGINA: FIRMAS ELECTRONICAS ============ -->
<div style="page-break-before: always;">
    <div class="seccion">7. FIRMAS ELECTRÓNICAS</div>
    <p style="font-size:8pt; color:#444; text-align:justify;">
        Trámite Nro. <strong><?= $e($tramite['codigo']) ?></strong>.
        Las firmas electrónicas de este documento se estampan por actualización incremental
        en los recuadros reservados a continuación, en el orden establecido, y se validan
        únicamente en FirmaEC.
    </p>
    <?= $cajas ?>
</div>

</body>
</html>
    <?php
    return ob_get_clean();
}

/**
 * Acta de SALIDA de equipo (formulario 7), basada en templates/pdf_form7.php.
 * Dos firmantes: custodio (solicita) y jefe inmediato (autoriza).
 */
function eqrActaSalidaHtml(array $tramite, array $equipo, array $contrato, array $firmantes,
                           array $config, array $meta, string $logoDataUri, string $logoSecundarioDataUri): string
{
    $metaDoc = $meta;
    $metaDoc['titulo_encabezado'] = 'AUTORIZACIÓN DE SALIDA DE EQUIPO DE CÓMPUTO EN ARRENDAMIENTO';
    $encabezado = renderPdfEncabezadoInstitucional($config, $metaDoc, $logoDataUri, $logoSecundarioDataUri);
    $colorPrimario = $config['color_primario'] ?? '#003366';
    $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

    $fmtFecha = function ($f) {
        $ts = strtotime((string)$f);
        return ($f && $ts) ? date('d/m/Y', $ts) : (string)$f;
    };
    $retorno = $tramite['salida_fecha_retorno'] ? $fmtFecha($tramite['salida_fecha_retorno']) : 'Indefinida';

    // recuadros de firma (2) en la ultima pagina
    $rects = eqrRectsFirma();
    $etiquetas = eqrEtiquetasFirma('salida');
    $cajas = '';
    foreach ($firmantes as $f) {
        $n = (int)$f['orden'];
        $c = $rects[$n]['css'];
        $cajas .= sprintf(
            '<div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; height:%.2fpt;
                 border:0.8pt solid #777;"></div>
             <div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; text-align:center;
                 font-size:7.5pt; font-weight:bold; color:#333;">%s</div>
             <div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; text-align:center;
                 font-size:8pt; color:#333;">%s<br>C.C. %s%s</div>',
            $c['left'], $c['top'], $c['w'], $c['h'],
            $c['left'], $c['top'] + $c['h'] + 4, $c['w'], $e($etiquetas[$n] ?? ''),
            $c['left'], $c['top'] + $c['h'] + 26, $c['w'],
            $e($f['nombre']), $e($f['cedula']),
            $f['cargo'] !== '' ? '<br>' . $e($f['cargo']) : ''
        );
    }

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
    .dt td { padding: 3px 7px; border: 1px solid #ddd; font-size: 8.5pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .intro { text-align: justify; font-size: 8.8pt; margin-bottom: 6px; }
    .compromisos { font-size: 8pt; text-align: justify; margin: 6px 0; padding: 6px 6px 6px 2px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .compromisos li { margin-bottom: 3px; }
    .codigo-tramite { text-align: right; font-size: 8pt; color: #555; margin-bottom: 4px; }
</style>
</head>
<body>

<?= $encabezado ?>

<div class="codigo-tramite">Trámite Nro. <strong><?= $e($tramite['codigo']) ?></strong> ·
    Contrato <?= $e($contrato['numero']) ?></div>

<p class="intro">Mediante el presente documento se registra y autoriza la salida del equipo de cómputo en
arrendamiento detallado a continuación fuera de las instalaciones de la Agencia de Regulación y Control de
Electricidad — ARCONEL, conforme a lo previsto en el acta de asignación, entrega y recepción del equipo.
El equipo se encuentra bajo la custodia del usuario solicitante, quien debe contar con esta autorización
firmada antes de retirarlo.</p>

<div class="seccion">1. DATOS DEL CUSTODIO (USUARIO FINAL)</div>
<table class="dt">
    <tr><td class="lb">Apellidos y nombres:</td><td colspan="3"><?= $e($tramite['entrega_nombre']) ?></td></tr>
    <tr><td class="lb">Cédula:</td><td><?= $e($tramite['entrega_cedula']) ?></td>
        <td class="lb">Correo institucional:</td><td><?= $e($tramite['entrega_correo']) ?></td></tr>
    <tr><td class="lb">Cargo:</td><td><?= $e($tramite['entrega_cargo']) ?></td>
        <td class="lb">Coordinación / Dirección:</td><td><?= $e($tramite['entrega_area']) ?></td></tr>
</table>

<div class="seccion">2. DATOS DEL EQUIPO</div>
<table class="dt">
    <tr><td class="lb">Código interno:</td><td><?= $e($equipo['codigo_interno']) ?></td>
        <td class="lb">Tipo:</td><td><?= $e($equipo['tipo']) ?></td></tr>
    <tr><td class="lb">Marca / Modelo:</td><td><?= $e($equipo['marca']) ?> <?= $e($equipo['modelo']) ?></td>
        <td class="lb">Serie:</td><td><?= $e($equipo['serie']) ?></td></tr>
    <tr><td class="lb">Cargador:</td><td><?= !empty($equipo['cargador_entregado']) ? 'Sí' : 'No' ?></td>
        <td class="lb">Otros accesorios:</td><td><?= $e($tramite['accesorios']) ?></td></tr>
    <?php if (trim((string)$tramite['observaciones']) !== ''): ?>
    <tr><td class="lb">Observaciones:</td><td colspan="3"><?= $e($tramite['observaciones']) ?></td></tr>
    <?php endif; ?>
</table>

<div class="seccion">3. MOTIVO Y PERÍODO DE LA SALIDA</div>
<table class="dt">
    <tr><td class="lb">Motivo de la salida:</td><td colspan="3"><?= $e($tramite['salida_motivo']) ?></td></tr>
    <tr><td class="lb">Fecha de salida:</td><td><?= $e($fmtFecha($tramite['salida_fecha'])) ?></td>
        <td class="lb">Fecha estimada de retorno:</td><td><?= $e($retorno) ?></td></tr>
    <tr><td class="lb">Lugar donde estará el equipo:</td><td colspan="3"><?= $e($tramite['salida_lugar']) ?></td></tr>
</table>

<div class="seccion">4. COMPROMISOS DEL USUARIO</div>
<div class="compromisos">
<ul style="margin:0;padding-left:15px;">
    <li>Utilizar el equipo exclusivamente para el cumplimiento de sus actividades institucionales.</li>
    <li>Mantener el equipo bajo condiciones adecuadas de cuidado, custodia y conservación, evitando acciones que puedan ocasionar daño, pérdida o afectación a su funcionamiento.</li>
    <li>No dejar el equipo desatendido en vehículos, lugares públicos o zonas vulnerables que representen un riesgo previsible de pérdida, hurto, robo o daño.</li>
    <li>Cumplir las políticas institucionales de seguridad de la información, buenas prácticas informáticas y normativas sobre confidencialidad y protección de datos vigentes en la ARCONEL.</li>
    <li>No abrir, reparar, modificar el hardware ni alterar sellos de garantía. Toda atención técnica deberá canalizarse formal y exclusivamente a través de la DTIC.</li>
    <li>En caso de falla o daño del equipo, reportar inmediatamente la novedad a la DTIC mediante la Mesa de Ayuda (soporte@arconel.gob.ec) y entregar el equipo para su revisión.</li>
    <li>En caso de producirse un siniestro de robo o asalto, notificar de forma inmediata a la DTIC y a su jefe inmediato, presentar la denuncia correspondiente ante la Fiscalía General del Estado o autoridad competente dentro de los plazos legales, y remitir copia certificada de la misma.</li>
    <li>Devolver el equipo y la totalidad de sus accesorios a la DTIC al finalizar el período de salida, en condiciones físicas y operativas equivalentes a las registradas, salvo el desgaste normal derivado de su uso adecuado.</li>
</ul>
</div>

<div class="seccion">5. ACEPTACIÓN Y AUTORIZACIÓN</div>
<p class="intro">El custodio declara conocer y aceptar los compromisos detallados en el presente documento y
asume la responsabilidad del cuidado, buen uso, custodia y conservación del equipo mientras permanezca
fuera de las instalaciones de la ARCONEL. El jefe inmediato autoriza la salida del equipo para el motivo y
período indicados. Esta autorización no modifica las responsabilidades establecidas en el acta de
asignación, entrega y recepción del equipo ni la custodia registrada en el sistema.</p>

<!-- ============ ULTIMA PAGINA: FIRMAS ELECTRONICAS ============ -->
<div style="page-break-before: always;">
    <div class="seccion">6. FIRMAS ELECTRÓNICAS</div>
    <p style="font-size:8pt; color:#444; text-align:justify;">
        Trámite Nro. <strong><?= $e($tramite['codigo']) ?></strong>.
        Las firmas electrónicas de este documento se estampan por actualización incremental
        en los recuadros reservados a continuación, en el orden establecido: primero el custodio
        que solicita la salida y luego el jefe inmediato que la autoriza.
    </p>
    <?= $cajas ?>
</div>

</body>
</html>
    <?php
    return ob_get_clean();
}
