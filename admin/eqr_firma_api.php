<?php
/**
 * API JSON de la firma del ADMINISTRADOR (sesion de admin + CSRF).
 * Acciones: estado | preparar | completar
 * Solo opera sobre la PRIMERA firma (rol administrador) del tramite.
 */
require __DIR__ . '/eqr_common.php';
require_once __DIR__ . '/../includes/eqr/flujo.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function eqrApiResponder(array $d, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$a = (string)($_GET['a'] ?? '');
$tramite = eqrTramitePorId($db, $id);
if (!$tramite) { eqrApiResponder(['ok' => false, 'mensaje' => 'Trámite no encontrado.'], 404); }

$body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
    $csrf = (string)($body['csrf'] ?? '');
    if ($csrf === '' || !hash_equals(eqrCsrfToken(), $csrf)) {
        eqrApiResponder(['ok' => false, 'mensaje' => 'Token de seguridad inválido. Recargue la página.'], 403);
    }
}

$rolesLegibles = [
    'administrador' => 'Administrador del contrato',
    'custodio_anterior' => 'Custodio anterior (entrega)',
    'nuevo_custodio' => 'Nuevo custodio (recibe)',
    'custodio' => 'Custodio que devuelve',
    'jefe_inmediato' => 'Jefe inmediato',
];

$pendiente = eqrFirmaPendiente($db, $id);
$esTurnoAdmin = $pendiente && $pendiente['rol'] === 'administrador'
    && in_array($tramite['estado'], ['borrador', 'en_firma'], true);

switch ($a) {
    case 'estado':
        $firmas = eqrFirmasDeTramite($db, $id);
        $lista = [];
        foreach ($firmas as $f) {
            $lista[] = [
                'orden' => (int)$f['orden'], 'rol_legible' => $rolesLegibles[$f['rol']] ?? $f['rol'],
                'nombre' => $f['nombre'], 'cedula' => $f['cedula'],
                'estado' => $f['estado'], 'fecha_firma' => $f['fecha_firma'],
            ];
        }
        $aviso = null;
        if (!$esTurnoAdmin && $pendiente) {
            $aviso = 'El turno de firma actual es de ' . $pendiente['nombre']
                . ' (le llegó su enlace por correo). Desde aquí usted solo puede ver el avance.';
        }
        eqrApiResponder(['ok' => true, 'firmantes' => $lista,
            'puede_firmar' => $esTurnoAdmin,
            'completo' => $tramite['estado'] === 'completado',
            'aviso' => $aviso]);

    case 'preparar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { eqrApiResponder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if (!$esTurnoAdmin) { eqrApiResponder(['ok' => false, 'mensaje' => 'No es el turno de la firma del administrador.'], 400); }
        try {
            $prep = eqrPrepararFirma($tramite, $pendiente);
            eqrApiResponder(['ok' => true, 'bytesB64' => $prep['bytesB64']]);
        } catch (Throwable $e) {
            eqrApiResponder(['ok' => false, 'mensaje' => 'Error al preparar la firma: ' . $e->getMessage()], 500);
        }

    case 'completar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { eqrApiResponder(['ok' => false, 'mensaje' => 'POST requerido'], 405); }
        if (!$esTurnoAdmin) { eqrApiResponder(['ok' => false, 'mensaje' => 'No es el turno de la firma del administrador.'], 400); }
        $firmaHex = (string)($body['firmaHex'] ?? '');
        if ($firmaHex === '' || !preg_match('/^[0-9a-fA-F]+$/', $firmaHex)) {
            eqrApiResponder(['ok' => false, 'mensaje' => 'Firma ausente o inválida.'], 400);
        }
        try {
            $actor = 'admin:' . ($_SESSION['admin_nombre'] ?? 'desconocido');
            $r = eqrCompletarFirma($db, $tramite, $pendiente, $firmaHex, $actor);
            eqrApiResponder($r, $r['ok'] ? 200 : 422);
        } catch (Throwable $e) {
            eqrApiResponder(['ok' => false, 'mensaje' => 'Error al completar la firma: ' . $e->getMessage()], 500);
        }

    default:
        eqrApiResponder(['ok' => false, 'mensaje' => 'Acción desconocida.'], 400);
}
