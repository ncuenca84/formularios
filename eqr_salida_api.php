<?php
/**
 * API publica del formulario 7: verifica el par cedula + codigo del equipo.
 * Respuesta NEUTRAL si no coincide (no revela a quien pertenece el equipo).
 * Limite de intentos por IP.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/publico.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function salidaApiResponder(array $d, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    salidaApiResponder(['ok' => false, 'mensaje' => 'POST requerido'], 405);
}
$body = json_decode((string)file_get_contents('php://input'), true) ?: [];
if (!eqrPubCsrfOk($body['csrf'] ?? null)) {
    salidaApiResponder(['ok' => false, 'mensaje' => 'Sesión caducada: recargue la página.'], 403);
}

$db = getDB();
if (eqrPubIpBloqueada($db, 'salida_verificacion_fallida')) {
    salidaApiResponder(['ok' => false,
        'mensaje' => 'Demasiados intentos desde su conexión. Espere unos minutos e intente de nuevo.'], 429);
}

$cedula = preg_replace('/\D/', '', (string)($body['cedula'] ?? ''));
$codigo = strtoupper(trim((string)($body['codigo'] ?? '')));
$equipo = eqrEquipoDeCustodio($db, $cedula, $codigo);
if (!$equipo) {
    eqrPubIntentoFallido($db, 'salida_verificacion_fallida', ['codigo' => substr($codigo, 0, 20)]);
    salidaApiResponder(['ok' => false,
        'mensaje' => 'Los datos no coinciden con un equipo a su cargo. Verifique su cédula y la serie del equipo (consta en su acta de entrega, ej.: J6F8LG3) o el código REN-….']);
}

salidaApiResponder([
    'ok' => true,
    'equipo' => [
        'codigo' => $equipo['codigo_interno'],
        'tipo' => $equipo['tipo'],
        'marca' => $equipo['marca'],
        'modelo' => $equipo['modelo'],
        'serie' => $equipo['serie'],
        'cargador' => (bool)$equipo['cargador_entregado'],
        'custodio' => $equipo['custodio_nombre'],
        'tiene_correo' => trim((string)$equipo['custodio_correo']) !== '',
    ],
    'problema' => eqrSalidaProblema($db, $equipo),
]);
