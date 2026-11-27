<?php
/**
 * API JSON de la pagina publica de firma (autenticada por el token del enlace).
 * Atiende los dos circuitos: actas de equipos (eqr_*) y formularios 1-6 (fir_*).
 * Acciones: estado | preparar | completar | rechazar
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';
require_once __DIR__ . '/includes/eqr/fir_flujo.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function responder(array $d, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDB();
$token = (string)($_GET['t'] ?? '');

$modo = null;
if (($ctx = eqrFirmaPorToken($db, $token)) !== null) {
    $modo = 'eqr';
    $firma = $ctx['firma'];
    $padre = $ctx['tramite'];
    $problema = eqrTokenProblema($firma, $padre);
    $completo = $padre['estado'] === 'completado';
    $firmas = eqrFirmasDeTramite($db, (int)$padre['id']);
} elseif (($ctx = firFirmaPorToken($db, $token)) !== null) {
    $modo = 'fir';
    $firma = $ctx['firma'];
    $padre = $ctx['documento'];
    $problema = firTokenProblema($firma, $padre);
    $completo = $padre['estado'] === 'completado';
    $firmas = firFirmasDe($db, (int)$padre['id']);
} else {
    eqrFlujoBitacora($db, null, null, 'token_invalido', ['api' => 1], 'publico');
    responder(['ok' => false, 'mensaje' => 'El enlace ya no es válido.'], 403);
}

$actor = 'firmante:' . $firma['cedula'];
$rolesLegibles = [
    'administrador' => 'Administrador del contrato',
    'custodio_anterior' => 'Custodio anterior (entrega)',
    'nuevo_custodio' => 'Nuevo custodio (recibe)',
    'custodio' => 'Custodio',
    'jefe_inmediato' => 'Jefe inmediato',
];

$a = (string)($_GET['a'] ?? '');
switch ($a) {
    case 'estado':
        $lista = [];
        foreach ($firmas as $f) {
            $lista[] = [
                'orden' => (int)$f['orden'],
                'rol_legible' => $modo === 'fir'
                    ? ($f['etiqueta'] !== '' ? $f['etiqueta'] : $f['rol'])
                    : ($rolesLegibles[$f['rol']] ?? $f['rol']),
                'nombre' => $f['nombre'], 'cedula' => $f['cedula'],
                'estado' => $f['estado'], 'fecha_firma' => $f['fecha_firma'],
            ];
        }
        responder(['ok' => true, 'firmantes' => $lista,
            'puede_firmar' => $problema === null,
            'completo' => $completo,
            'aviso' => $problema]);

    case 'preparar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if ($problema !== null) { responder(['ok' => false, 'mensaje' => $problema], 400); }
        try {
            $prep = $modo === 'eqr' ? eqrPrepararFirma($padre, $firma) : firPrepararFirma($padre, $firma);
            responder(['ok' => true, 'bytesB64' => $prep['bytesB64']]);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al preparar la firma: ' . $e->getMessage()], 500);
        }

    case 'completar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if ($problema !== null) { responder(['ok' => false, 'mensaje' => $problema], 400); }
        $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $firmaHex = (string)($body['firmaHex'] ?? '');
        if ($firmaHex === '' || !preg_match('/^[0-9a-fA-F]+$/', $firmaHex)) {
            responder(['ok' => false, 'mensaje' => 'Firma ausente o inválida.'], 400);
        }
        try {
            $r = $modo === 'eqr'
                ? eqrCompletarFirma($db, $padre, $firma, $firmaHex, $actor)
                : firCompletarFirma($db, $padre, $firma, $firmaHex, $actor);
            responder($r, $r['ok'] ? 200 : 422);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al completar la firma: ' . $e->getMessage()], 500);
        }

    case 'rechazar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if ($problema !== null) { responder(['ok' => false, 'mensaje' => $problema], 400); }
        if ((int)$firma['orden'] === 1) {
            responder(['ok' => false, 'mensaje' => 'Quien inicia el trámite no puede rechazarlo. Si ya no corresponde, simplemente no lo firme y pida su anulación.'], 400);
        }
        $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $motivo = trim((string)($body['motivo'] ?? ''));
        if (mb_strlen($motivo) < 5) { responder(['ok' => false, 'mensaje' => 'Escriba el motivo del rechazo.'], 400); }
        try {
            if ($modo === 'eqr') {
                eqrRechazarFirma($db, $padre, $firma, mb_substr($motivo, 0, 500), $actor);
                responder(['ok' => true, 'mensaje' => 'Trámite rechazado. Se notificó al administrador del contrato.']);
            }
            firRechazarFirma($db, $padre, $firma, mb_substr($motivo, 0, 500), $actor);
            responder(['ok' => true, 'mensaje' => 'Documento rechazado. Se notificó al solicitante y a la DTIC.']);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al rechazar: ' . $e->getMessage()], 500);
        }

    default:
        responder(['ok' => false, 'mensaje' => 'Acción desconocida.'], 400);
}
