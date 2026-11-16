<?php
/**
 * API del prototipo de firma (Fase 0). Acciones JSON.
 * Flujo fijo de demo: 3 firmantes en orden con cedulas conocidas.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/lib/PdfSigner.php';
require_once __DIR__ . '/lib/CmsVerifier.php';
require_once __DIR__ . '/lib/generar_pdf.php';

const POC_OUT = __DIR__ . '/out';
const POC_PDF = POC_OUT . '/acta_demo.pdf';
const POC_STATE = POC_OUT . '/acta_demo.state.json';
const POC_FLUJO = POC_OUT . '/flujo.json';

/** Secuencia de demo: rol, nombre, cedula esperada y recuadro. */
function pocFirmantes(): array
{
    $r = pocRecuadrosFirma();
    return [
        1 => ['rol' => 'Administrador del contrato', 'nombre' => 'Administrador Contrato de Prueba',
              'cedula' => '1710000001', 'rect' => $r[1]['rect'], 'p12' => 'firmante1_admin.p12'],
        2 => ['rol' => 'Custodio anterior (entrega)', 'nombre' => 'Custodio Anterior de Prueba',
              'cedula' => '1710000002', 'rect' => $r[2]['rect'], 'p12' => 'firmante2_anterior.p12'],
        3 => ['rol' => 'Nuevo custodio (recibe)', 'nombre' => 'Custodio Nuevo de Prueba',
              'cedula' => '1710000003', 'rect' => $r[3]['rect'], 'p12' => 'firmante3_nuevo.p12'],
    ];
}

function flujo(): array
{
    if (is_file(POC_FLUJO)) {
        $f = json_decode((string)file_get_contents(POC_FLUJO), true);
        if (is_array($f)) { return $f; }
    }
    return ['paso' => 0, 'firmados' => []];   // paso 0 = sin PDF
}
function guardarFlujo(array $f): void
{
    file_put_contents(POC_FLUJO, json_encode($f));
}
function responder(array $d, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$a = $_GET['a'] ?? $_POST['a'] ?? '';
$firmantes = pocFirmantes();
$f = flujo();

switch ($a) {
    // ---------------------------------------------------------------- estado
    case 'estado':
        $lista = [];
        foreach ($firmantes as $n => $fi) {
            $estado = in_array($n, $f['firmados'], true) ? 'firmado'
                : (($f['paso'] + 1) === $n && $f['paso'] < 3 && is_file(POC_PDF) ? 'turno' : 'en_espera');
            $lista[] = ['n' => $n, 'rol' => $fi['rol'], 'nombre' => $fi['nombre'],
                        'cedula' => $fi['cedula'], 'p12' => $fi['p12'], 'estado' => $estado];
        }
        responder([
            'ok' => true,
            'pdf' => is_file(POC_PDF),
            'paso' => $f['paso'],
            'completo' => $f['paso'] >= 3,
            'firmantes' => $lista,
        ]);

    // ------------------------------------------------------------- reiniciar
    case 'reiniciar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        @mkdir(POC_OUT, 0775, true);
        array_map('unlink', glob(POC_OUT . '/*') ?: []);
        pocGenerarPdf(POC_PDF);
        guardarFlujo(['paso' => 0, 'firmados' => []]);
        copy(POC_PDF, POC_OUT . '/base_v0.pdf');
        responder(['ok' => true, 'mensaje' => 'Acta de prueba generada.']);

    // -------------------------------------------------------------- preparar
    case 'preparar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if (!is_file(POC_PDF)) { responder(['ok' => false, 'mensaje' => 'Primero genere el acta.'], 400); }
        $n = $f['paso'] + 1;
        if ($n > 3) { responder(['ok' => false, 'mensaje' => 'El acta ya tiene las 3 firmas.'], 400); }
        $fi = $firmantes[$n];
        try {
            $signer = new PdfSigner();
            $prep = $signer->preparar(POC_PDF, POC_STATE, [
                'nombre' => $fi['nombre'], 'cedula' => $fi['cedula'],
                'rol' => $fi['rol'], 'rect' => $fi['rect'],
            ]);
            responder([
                'ok' => true,
                'firmante' => ['n' => $n, 'rol' => $fi['rol'], 'nombre' => $fi['nombre'], 'cedula' => $fi['cedula']],
                'bytesB64' => base64_encode($prep['bytesAFirmar']),
            ]);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al preparar: ' . $e->getMessage()], 500);
        }

    // ------------------------------------------------------------- completar
    case 'completar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $firmaHex = (string)($body['firmaHex'] ?? '');
        if ($firmaHex === '' || !preg_match('/^[0-9a-fA-F]+$/', $firmaHex)) {
            responder(['ok' => false, 'mensaje' => 'Firma ausente o invalida.'], 400);
        }
        $n = $f['paso'] + 1;
        if ($n > 3) { responder(['ok' => false, 'mensaje' => 'El acta ya esta completa.'], 400); }
        $fi = $firmantes[$n];
        try {
            $signer = new PdfSigner();
            $verifier = new CmsVerifier();
            $signed = $signer->completar(POC_STATE, $firmaHex);

            $anterior = file_get_contents($n === 1 ? POC_OUT . '/base_v0.pdf' : POC_PDF);
            $res = $verifier->verificarDocumento((string)file_get_contents($signed), $anterior, $fi['cedula']);
            if (!$res['ok']) {
                @unlink($signed);
                responder(['ok' => false, 'mensaje' => 'La firma fue RECHAZADA por la verificacion del servidor.',
                           'errores' => $res['errores']], 422);
            }
            $signer->aceptar(POC_STATE, $signed, POC_PDF);
            $f['paso'] = $n;
            $f['firmados'][] = $n;
            guardarFlujo($f);
            responder(['ok' => true,
                'mensaje' => 'Firma ' . $n . ' aceptada: ' . ($res['cert']['cn'] ?? ''),
                'advertencias' => $res['advertencias'],
                'completo' => $n >= 3]);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al completar: ' . $e->getMessage()], 500);
        }

    // ------------------------------------------------------------- verificar
    case 'verificar':
        if (!is_file(POC_PDF)) { responder(['ok' => false, 'mensaje' => 'No hay acta.'], 400); }
        $verifier = new CmsVerifier();
        $pdf = (string)file_get_contents(POC_PDF);
        $firmas = $verifier->extraerFirmas($pdf);
        $det = [];
        foreach ($firmas as $i => $fir) {
            $r = $verifier->verificarIntegridad($pdf, $fir);
            $det[] = [
                'n' => $i + 1,
                'integra' => $r['ok'],
                'cert' => $r['ok'] ? $verifier->datosCert((string)$r['certPem']) : null,
                'error' => $r['error'],
            ];
        }
        responder(['ok' => true, 'firmas' => $det,
                   'sha256' => hash('sha256', $pdf), 'bytes' => strlen($pdf)]);

    default:
        responder(['ok' => false, 'mensaje' => 'Accion desconocida.'], 400);
}
