<?php
/**
 * API JSON de la pagina publica de firma (autenticada por el token del enlace).
 * Acciones: estado | preparar | completar | rechazar
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';

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
$ctx = eqrFirmaPorToken($db, $token);
if (!$ctx) {
    eqrFlujoBitacora($db, null, null, 'token_invalido', ['api' => 1], 'publico');
    responder(['ok' => false, 'mensaje' => 'El enlace ya no es válido.'], 403);
}
$tramite = $ctx['tramite'];
$firma = $ctx['firma'];
$problema = eqrTokenProblema($firma, $tramite);
$actor = 'firmante:' . $firma['cedula'];

$rolesLegibles = [
    'administrador' => 'Administrador del contrato',
    'custodio_anterior' => 'Custodio anterior (entrega)',
    'nuevo_custodio' => 'Nuevo custodio (recibe)',
    'custodio' => 'Custodio que devuelve',
    'jefe_inmediato' => 'Jefe inmediato',
];

$a = (string)($_GET['a'] ?? '');
switch ($a) {
    case 'estado':
        $lista = [];
        foreach (eqrFirmasDeTramite($db, (int)$tramite['id']) as $f) {
            $lista[] = [
                'orden' => (int)$f['orden'], 'rol_legible' => $rolesLegibles[$f['rol']] ?? $f['rol'],
                'nombre' => $f['nombre'], 'cedula' => $f['cedula'],
                'estado' => $f['estado'], 'fecha_firma' => $f['fecha_firma'],
            ];
        }
        responder(['ok' => true, 'firmantes' => $lista,
            'puede_firmar' => $problema === null,
            'completo' => $tramite['estado'] === 'completado',
            'aviso' => $problema]);

    case 'preparar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if ($problema !== null) { responder(['ok' => false, 'mensaje' => $problema], 400); }
        try {
            $prep = eqrPrepararFirma($tramite, $firma);
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
            $r = eqrCompletarFirma($db, $tramite, $firma, $firmaHex, $actor);
            responder($r, $r['ok'] ? 200 : 422);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al completar la firma: ' . $e->getMessage()], 500);
        }

    case 'rechazar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { responder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if ($problema !== null) { responder(['ok' => false, 'mensaje' => $problema], 400); }
        $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $motivo = trim((string)($body['motivo'] ?? ''));
        if (mb_strlen($motivo) < 5) { responder(['ok' => false, 'mensaje' => 'Escriba el motivo del rechazo.'], 400); }
        try {
            eqrRechazarFirma($db, $tramite, $firma, mb_substr($motivo, 0, 500), $actor);
            responder(['ok' => true, 'mensaje' => 'Trámite rechazado. Se notificó al administrador del contrato.']);
        } catch (Throwable $e) {
            responder(['ok' => false, 'mensaje' => 'Error al rechazar: ' . $e->getMessage()], 500);
        }

    default:
        responder(['ok' => false, 'mensaje' => 'Acción desconocida.'], 400);
}
