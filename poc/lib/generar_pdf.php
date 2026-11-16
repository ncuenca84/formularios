<?php
/**
 * Genera el PDF de prueba de la Fase 0 con DomPDF: un acta simplificada
 * con TRES recuadros de firma en coordenadas conocidas.
 *
 * La correspondencia CSS->PDF: pagina A4 = 595.28 x 841.89 pt, @page margin 0,
 * recuadros con position:absolute en pt. En PDF el origen esta abajo-izquierda:
 *   y_pdf_bottom = 841.89 - top_css - alto ; y_pdf_top = 841.89 - top_css
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/** Coordenadas de los recuadros (pt). Compartidas por generar y firmar. */
function pocRecuadrosFirma(): array
{
    // [x_izq, top_css, ancho, alto] en pt
    $defs = [
        1 => [50,  600, 240, 72],   // administrador
        2 => [305, 600, 240, 72],   // custodio anterior
        3 => [50,  700, 240, 72],   // nuevo custodio
    ];
    $out = [];
    foreach ($defs as $n => [$x, $top, $w, $h]) {
        $out[$n] = [
            'css' => ['left' => $x, 'top' => $top, 'w' => $w, 'h' => $h],
            'rect' => [$x, round(841.89 - $top - $h, 2), $x + $w, round(841.89 - $top, 2)],
        ];
    }
    return $out;
}

function pocGenerarPdf(string $destino): void
{
    $r = pocRecuadrosFirma();
    $cajas = '';
    $tit = [1 => 'ADMINISTRADOR DEL CONTRATO', 2 => 'CUSTODIO ANTERIOR', 3 => 'NUEVO CUSTODIO'];
    foreach ($r as $n => $def) {
        $c = $def['css'];
        $cajas .= sprintf(
            '<div style="position:absolute; left:%dpt; top:%dpt; width:%dpt; height:%dpt;
                border:0.7pt dashed #888; font-size:7pt; color:#888; padding:2pt;">
                Espacio reservado: firma %d — %s</div>',
            $c['left'], $c['top'], $c['w'], $c['h'], $n, $tit[$n]
        );
    }

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        @page { margin: 0; }
        body { font-family: Helvetica, sans-serif; font-size: 10pt; margin: 0; }
        .contenido { padding: 40pt 50pt; }
        h1 { font-size: 13pt; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 10pt 0; }
        td { border: 0.7pt solid #999; padding: 4pt 6pt; font-size: 9pt; }
        .lb { background: #eef2f7; font-weight: bold; width: 30%; }
    </style></head><body>
    <div class="contenido">
        <h1>ACTA DE PRUEBA — FASE 0<br>FIRMA ELECTRÓNICA EN LÍNEA (PROTOTIPO)</h1>
        <p>Este documento es un prototipo técnico. Simula un acta de traspaso de un
        equipo de cómputo en arrendamiento, con tres firmas electrónicas incrementales:
        administrador del contrato, custodio anterior y nuevo custodio.</p>
        <table>
            <tr><td class="lb">Trámite</td><td>TRM-PRUEBA-0001 (traspaso)</td></tr>
            <tr><td class="lb">Equipo</td><td>Computador portátil DELL Latitude 5430 — Serie PRUEBA001</td></tr>
            <tr><td class="lb">Entrega</td><td>CUSTODIO ANTERIOR DE PRUEBA — C.C. 1710000002</td></tr>
            <tr><td class="lb">Recibe</td><td>CUSTODIO NUEVO DE PRUEBA — C.C. 1710000003</td></tr>
            <tr><td class="lb">Fecha de generación</td><td>' . date('Y-m-d H:i:s') . '</td></tr>
        </table>
        <p style="font-size:8pt; color:#555;">Los recuadros punteados marcan las zonas
        reservadas para las firmas visibles. Cada firma se agrega por actualización
        incremental sin alterar los bytes anteriores del archivo.</p>
    </div>
    ' . $cajas . '
    </body></html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    file_put_contents($destino, $dompdf->output());
}
