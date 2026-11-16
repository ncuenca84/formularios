<?php
/** Entrega el acta PDF al firmante con token vigente (para el visor de firmar.php). */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';

$db = getDB();
$ctx = eqrFirmaPorToken($db, (string)($_GET['t'] ?? ''));
if (!$ctx) {
    http_response_code(403);
    exit('Enlace no válido.');
}
$tramite = $ctx['tramite'];
if (in_array($tramite['estado'], ['anulado'], true)) {
    http_response_code(403);
    exit('El trámite fue anulado.');
}
$path = eqrRutaActaAbs($tramite);
if (!is_file($path)) {
    http_response_code(404);
    exit('Acta no encontrada.');
}
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="ACTA_' .
       preg_replace('/[^A-Za-z0-9\-]/', '_', $tramite['codigo']) . '.pdf"');
readfile($path);
