<?php
/** Entrega el PDF (acta de equipo o formulario) al firmante con token vigente. */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';
require_once __DIR__ . '/includes/eqr/fir_flujo.php';

$db = getDB();
$token = (string)($_GET['t'] ?? '');

$path = null;
$codigo = null;
if (($ctx = eqrFirmaPorToken($db, $token)) !== null) {
    if ($ctx['tramite']['estado'] === 'anulado') { http_response_code(403); exit('El trámite fue anulado.'); }
    $path = eqrRutaActaAbs($ctx['tramite']);
    $codigo = $ctx['tramite']['codigo'];
} elseif (($ctx = firFirmaPorToken($db, $token)) !== null) {
    if ($ctx['documento']['estado'] === 'anulado') { http_response_code(403); exit('El documento fue anulado.'); }
    $path = firDocPath($ctx['documento']['codigo']);
    $codigo = $ctx['documento']['codigo'];
} else {
    http_response_code(403);
    exit('Enlace no válido.');
}

if (!is_file($path)) {
    http_response_code(404);
    exit('Documento no encontrado.');
}
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' .
       preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$codigo) . '.pdf"');
readfile($path);
